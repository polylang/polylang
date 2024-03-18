<?php

class Model_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once POLYLANG_DIR . '/include/api.php';
	}

	public function test_languages_list() {
		$this->assertSame( array( 'en', 'fr' ), self::$model->get_languages_list( array( 'fields' => 'slug' ) ) );
		$this->assertSame( array( 'English', 'Français' ), self::$model->get_languages_list( array( 'fields' => 'name' ) ) );
		$this->assertSame( array(), self::$model->get_languages_list( array( 'hide_empty' => true ) ) );

		$post_id = self::factory()->post->create();
		self::$model->post->set_language( $post_id, 'en' );

		$this->assertSame( array( 'en' ), self::$model->get_languages_list( array( 'fields' => 'slug', 'hide_empty' => true ) ) );
		$this->assertSame( array( 'fr' ), self::$model->get_languages_list( array( 'fields' => 'slug', 'hide_default' => true ) ) );
		$this->assertSame( array(), self::$model->get_languages_list( array( 'fields' => 'slug', 'hide_default' => true, 'hide_empty' => true ) ) );
	}

	public function test_languages_list_order() {
		$languages = array(
			'it_IT' => array(
				'term_group' => 17,
			),
			'es_ES' => array(
				'term_group' => 6,
			),
		);

		foreach ( $languages as $locale => $data ) {
			self::create_language( $locale, $data );
		}

		$languages = self::$model->get_languages_list( array( 'fields' => 'slug' ) );
		$expected  = array( 'en', 'fr', 'es', 'it' );

		$this->assertSame( $expected, $languages, 'Expected the languages to be ordered by term_group and term_id.' );
	}

	public function test_term_exists() {
		$parent = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'parent' ) );
		self::$model->term->set_language( $parent, 'en' );
		$child = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'child', 'parent' => $parent ) );
		self::$model->term->set_language( $child, 'en' );

		$this->assertEquals( $parent, self::$model->term_exists( 'parent', 'category', 0, 'en' ) );
		$this->assertEquals( $child, self::$model->term_exists( 'child', 'category', 0, 'en' ) );
		$this->assertEquals( $child, self::$model->term_exists( 'child', 'category', $parent, 'en' ) );
		$this->assertEmpty( self::$model->term_exists( 'parent', 'category', 0, 'fr' ) );
		$this->assertEmpty( self::$model->term_exists( 'child', 'category', 0, 'fr' ) );
		$this->assertEmpty( self::$model->term_exists( 'child', 'category', $parent, 'fr' ) );
	}

	/**
	 * Bug fixed in 2.7
	 */
	public function test_term_exists_with_special_character() {
		$term = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'Cook & eat' ) );
		self::$model->term->set_language( $term, 'en' );
		$this->assertEquals( $term, self::$model->term_exists( 'Cook & eat', 'category', 0, 'en' ) );
	}

	public function test_count_posts() {
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$en = self::factory()->post->create( array( 'post_date' => '2007-09-04 00:00:00', 'post_author' => 1 ) );
		set_post_format( $en, 'aside' );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$fr = self::factory()->post->create( array( 'post_date' => '2007-09-04 00:00:00', 'post_author' => 1, 'post_status' => 'draft' ) );
		self::$model->post->set_language( $fr, 'fr' );

		$fr = self::factory()->post->create( array( 'post_date' => '2007-09-04 00:00:00', 'post_author' => 1 ) );
		set_post_format( $fr, 'aside' );
		self::$model->post->set_language( $fr, 'fr' );

		$language = self::$model->get_language( 'fr' );
		$this->assertEquals( 2, self::$model->count_posts( $language ) );
		$this->assertEquals( 1, self::$model->count_posts( $language, array( 'post_format' => 'post-format-aside' ) ) );
		$this->assertEquals( 1, self::$model->count_posts( $language, array( 'year' => 2007 ) ) );
		$this->assertEquals( 1, self::$model->count_posts( $language, array( 'year' => 2007, 'monthnum' => 9 ) ) );
		$this->assertEquals( 1, self::$model->count_posts( $language, array( 'year' => 2007, 'monthnum' => 9, 'day' => 4 ) ) );
		$this->assertEquals( 1, self::$model->count_posts( $language, array( 'm' => 2007 ) ) );
		$this->assertEquals( 1, self::$model->count_posts( $language, array( 'm' => 200709 ) ) );
		$this->assertEquals( 1, self::$model->count_posts( $language, array( 'm' => 20070904 ) ) );
		$this->assertEquals( 1, self::$model->count_posts( $language, array( 'author' => 1 ) ) );
		$this->assertEquals( 1, self::$model->count_posts( $language, array( 'author_name' => 'admin' ) ) );

		// Bug fixed in version 2.2.6
		$this->assertEquals( 2, self::$model->count_posts( $language, array( 'post_type' => array( 'post', 'page' ) ) ) );
	}

	public function test_translated_post_types() {
		// deactivate the cache
		self::$model->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		self::$model->cache->method( 'get' )->willReturn( false );

		self::$model->options['media_support'] = 0;

		$this->assertTrue( self::$model->is_translated_post_type( 'post' ) );
		$this->assertTrue( self::$model->is_translated_post_type( 'page' ) );
		$this->assertFalse( self::$model->is_translated_post_type( 'nav_menu_item' ) );
		$this->assertFalse( self::$model->is_translated_post_type( 'attachment' ) );

		self::$model->options['media_support'] = 1;
		$this->assertTrue( self::$model->is_translated_post_type( 'attachment' ) );

		self::$model->cache = new PLL_Cache();
	}

	public function test_translated_taxonomies() {
		$this->assertTrue( self::$model->is_translated_taxonomy( 'category' ) );
		$this->assertTrue( self::$model->is_translated_taxonomy( 'post_tag' ) );
		$this->assertFalse( self::$model->is_translated_taxonomy( 'post_format' ) );
		$this->assertFalse( self::$model->is_translated_taxonomy( 'nav_menu' ) );
		$this->assertFalse( self::$model->is_translated_taxonomy( 'language' ) );
	}

	public function test_filtered_taxonomies() {
		$this->assertTrue( self::$model->is_filtered_taxonomy( 'post_format' ) );
		$this->assertFalse( self::$model->is_filtered_taxonomy( 'category' ) );
		$this->assertFalse( self::$model->is_filtered_taxonomy( 'post_tag' ) );
		$this->assertFalse( self::$model->is_filtered_taxonomy( 'nav_menu' ) );
		$this->assertFalse( self::$model->is_filtered_taxonomy( 'language' ) );
	}

	public function test_is_translated_post_type() {
		self::$model->options['post_types'] = array(
			'trcpt' => 'trcpt',
		);

		register_post_type( 'trcpt' ); // translated custom post type
		register_post_type( 'cpt' ); // *untranslated* custom post type

		$links_model = self::$model->get_links_model();
		$GLOBALS['polylang'] = new PLL_Admin( $links_model );

		$this->assertTrue( pll_is_translated_post_type( 'trcpt' ) );
		$this->assertFalse( pll_is_translated_post_type( 'cpt' ) );

		$this->assertTrue( pll_is_translated_post_type( array( 'trcpt' ) ) );
		$this->assertFalse( pll_is_translated_post_type( array( 'cpt' ) ) );

		$this->assertTrue( pll_is_translated_post_type( array( 'trcpt', 'cpt' ) ) );

		_unregister_post_type( 'cpt' );
		_unregister_post_type( 'trcpt' );
		unset( $GLOBALS['polylang'] );
	}

	public function test_is_translated_taxonomy() {
		self::$model->options['taxonomies'] = array(
			'trtax' => 'trtax',
		);

		register_taxonomy( 'trtax', 'post' ); // translated custom tax
		register_taxonomy( 'tax', 'post' ); // *untranslated* custom tax

		$links_model = self::$model->get_links_model();
		$GLOBALS['polylang'] = new PLL_Admin( $links_model );

		$this->assertTrue( pll_is_translated_taxonomy( 'trtax' ) );
		$this->assertFalse( pll_is_translated_taxonomy( 'tax' ) );

		$this->assertTrue( pll_is_translated_taxonomy( array( 'trtax' ) ) );
		$this->assertFalse( pll_is_translated_taxonomy( array( 'tax' ) ) );

		$this->assertTrue( pll_is_translated_taxonomy( array( 'trtax', 'tax' ) ) );

		_unregister_taxonomy( 'tax' );
		_unregister_taxonomy( 'trtax' );
		unset( $GLOBALS['polylang'] );
	}

	public function test_is_filtered_taxonomy() {
		$this->assertTrue( self::$model->is_filtered_taxonomy( array( 'post_format' ) ) );
		$this->assertFalse( self::$model->is_filtered_taxonomy( array( 'category' ) ) );
		$this->assertTrue( self::$model->is_filtered_taxonomy( array( 'post_format', 'category' ) ) );
	}

	/**
	 * Bug fixed in 3.2.6
	 */
	public function test_untranslated_media_when_post_type_wrongly_stored_in_option() {
		self::$model->options['post_types'] = array(
			'attachment' => 'attachment',
		);

		self::$model->options['media_support'] = 0;

		$this->assertFalse( self::$model->is_translated_post_type( 'attachment' ) );
	}

	public function test_maybe_create_language_terms() {
		// Translatable custom table.
		require_once PLL_TEST_DATA_DIR . 'translatable-foo.php';

		$foo = new PLLTest_Translatable_Foo( self::$model );
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

		// Make sure the terms are deleted.
		foreach ( self::$model->get_languages_list() as $language ) {
			if ( 'es' === $language->slug || 'de' === $language->slug ) {
				$this->assertSame( 0, $language->get_tax_prop( $tax, 'term_id' ), "Expected to have no '$tax' term_ids for the new languages." );
				$this->assertSame( 0, $language->get_tax_prop( $tax, 'term_taxonomy_id' ), "Expected to have no '$tax' term_taxonomy_ids for the new languages." );
			}
		}

		// Re-create missing terms.
		self::$model->maybe_create_language_terms();

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

	/**
	 * @ticket #1689
	 * @see https://github.com/polylang/polylang-pro/issues/1689
	 */
	public function test_dont_use_languages_list_format_older_than_3_4() {
		// Build the cache, so `get_transient()` will contain a valid value.
		self::$model->set_languages_ready();
		self::$model->get_languages_list();

		// Get the transient and break it.
		$languages = get_transient( 'pll_languages_list' );
		foreach ( $languages as &$language ) {
			unset( $language['term_props'] );
		}

		// Clear the cache then insert the broken transient.
		self::$model->clean_languages_cache();
		set_transient( 'pll_languages_list', $languages );

		// Test the list.
		$languages = self::$model->get_languages_list();

		$this->assertCount( 2, $languages, 'There should be 2 languages.' );

		foreach ( $languages as $language ) {
			$this->assertIsInt( $language->term_id, 'The language term_id should be an integer.' );
			$this->assertGreaterThan( 0, $language->term_id, 'The language term_id should be a positive integer.' );
			$this->assertSame( $language->term_id, $language->get_tax_prop( 'language', 'term_id' ), 'The tax prop term_id should contain the language term_id.' );
		}
	}
}
