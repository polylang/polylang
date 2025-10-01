<?php

/**
 * This test was moved from `Model_Test` class and isoloted in its own test suite
 * to avoid to load Polylang API before registering the custom table as it's done in production.
 * Polylang API could be required by other tests by calling `self::require_api()` method.
 * /!\ This make it loaded for the whole running PHPUnit process.
 * The goal is to check any use of Polylang API functions which will trigger a fatal error.
 *
 * @ticket #2713 {@see https://github.com/polylang/polylang-pro/issues/2713}
 */
class Model_Maybe_Create_Language_Terms_Test extends PLL_UnitTestCase {

	public function test_maybe_create_language_terms() {
		// Translatable custom table.
		require_once PLL_TEST_DATA_DIR . 'translatable.php';

		$foo = new PLLTest_Translatable( self::$model );
		$tax = $foo->get_tax_language();
		self::$model->translatable_objects->register( $foo );

		// Languages we'll work with.
		self::create_language( 'es_ES' );
		self::create_language( 'de_DE' );

		// Get the term_ids to delete.
		$term_ids = array();
		foreach ( self::$model->get_languages_list() as $language ) {
			if ( 'es' === $language->slug || 'de' === $language->slug ) {
				$term_ids[] = $language->get_tax_prop( $tax, 'term_id' );
			}
		}
		$term_ids = array_filter( $term_ids );
		$this->assertCount( 2, $term_ids, "Expected to have 1 '$tax' term_id per new language." );

		// Delete terms.
		foreach ( $term_ids as $term_id ) {
			wp_delete_term( $term_id, $tax );
		}

		self::$model->clean_languages_cache();
		$links_model = self::$model->get_links_model();
		$GLOBALS['polylang'] = new PLL_Admin( $links_model );

		// Make sure the terms are deleted.
		foreach ( self::$model->get_languages_list() as $language ) {
			if ( 'es' === $language->slug || 'de' === $language->slug ) {
				$this->assertSame( 0, $language->get_tax_prop( $tax, 'term_id' ), "Expected to have no '$tax' term_ids for the new languages." );
				$this->assertSame( 0, $language->get_tax_prop( $tax, 'term_taxonomy_id' ), "Expected to have no '$tax' term_taxonomy_ids for the new languages." );
			}
		}

		// Re-create missing terms.
		self::$model->maybe_create_language_terms();

		$this->assertSame( array( 'foo_language' ), get_option( 'pll_language_taxonomies' ) );

		// Make sure the terms are re-created.
		$tt_ids = array();
		$slugs  = array();
		foreach ( self::$model->get_languages_list() as $language ) {
			if ( 'es' === $language->slug || 'de' === $language->slug ) {
				$tt_id             = $language->get_tax_prop( $tax, 'term_taxonomy_id' );
				$term_id           = $language->get_tax_prop( $tax, 'term_id' );
				$tt_ids[]          = $tt_id;
				$slugs[ $term_id ] = "pll_{$language->slug}";
				$this->assertNotSame( 0, $term_id, "Expected to have new '$tax' term_ids for the new languages." );
				$this->assertNotSame( 0, $tt_id, "Expected to have new '$tax' term_taxonomy_ids for the new languages." );
			}
		}
		$terms = get_terms(
			array(
				'taxonomy'         => $tax,
				'hide_empty'       => false,
				'fields'           => 'id=>slug',
				'term_taxonomy_id' => $tt_ids,
			)
		);
		$this->assertSameSetsWithIndex( $slugs, $terms );
	}
}
