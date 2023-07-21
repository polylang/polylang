<?php

class CRUD_Posts_Test extends PLL_UnitTestCase {
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	public function set_up() {
		parent::set_up();

		register_taxonomy( 'custom_tax', 'post' );

		$options                = array_merge(
			PLL_Install::get_default_options(),
			array(
				'default_lang' => 'en',
				'taxonomies'   => array( 'custom_tax' => 'custom_tax' ),
			)
		);
		$model                  = new PLL_Admin_Model( $options );
		$links_model            = new PLL_Links_Default( $model );
		$this->pll_admin        = new PLL_Admin( $links_model );
		$this->pll_admin->posts = new PLL_CRUD_Posts( $this->pll_admin );
		$this->pll_admin->terms = new PLL_CRUD_Terms( $this->pll_admin );
	}

	public function tear_down() {
		parent::tear_down();

		_unregister_taxonomy( 'custom_tax' );
	}

	/**
	 * @testWith ["post_tag", "tags_input"]
	 *           ["category", "post_category"]
	 *           ["custom_tax", "tax_input"]
	 *
	 * @param string $taxonomy     Taxonomy to test.
	 * @param string $post_tax_arg Post argument key used regarding the taxonomy in `wp_update_post()`.
	 */
	public function test_language_change_with_taxonomy( $taxonomy, $post_tax_arg ) {
		// Fixtures.
		$term_en = self::factory()->term->create( array( 'taxonomy' => $taxonomy ) );
		$this->pll_admin->model->term->set_language( $term_en, 'en' );
		$term_fr = self::factory()->term->create( array( 'taxonomy' => $taxonomy ) );
		$this->pll_admin->model->term->set_language( $term_fr, 'fr' );
		$this->pll_admin->model->term->save_translations(
			$term_en,
			array(
				'en' => $term_en,
				'fr' => $term_fr,
			)
		);

		$term_input = in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ? array( $term_en ) : array( $taxonomy => array( $term_en ) );  // Special case fo custom taxonomies.
		$post       = self::factory()->post->create_and_get( array( $post_tax_arg => $term_input ) );
		$this->pll_admin->model->post->set_language( $post->ID, 'en' );

		// Change post language and update it.
		$this->pll_admin->model->post->set_language( $post->ID, 'fr' );
		$postarr = $post->to_array();

		// Pass the term in previous language on purpose.
		if ( ! in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
			wp_set_current_user( 1 ); // Current user should have the proper capability to set custom taxonomy terms.
			$postarr[ $post_tax_arg ] = array( $taxonomy => array( $term_en ) ); // Special case fo custom taxonomies wich expects an array of array.
		} elseif ( 'post_tag' === $taxonomy ) {
			$postarr[ $post_tax_arg ] = array( get_term( $term_en, $taxonomy )->name ); // Special case for tags where `wp_update_post()` removes existing one by names.
		} else {
			$postarr[ $post_tax_arg ] = array( $term_en ); // Pass the term in previous language on purpose.
		}

		$result = wp_update_post( $postarr );

		$this->assertSame( $post->ID, $result, 'The post should be well updated.' );
		$this->assert_has_language( $post, 'fr', 'The post language should be French.' );

		$post  = get_post( $post->ID );
		$terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );

		$this->assertNotWPError( $terms );
		$this->assertCount( 1, $terms, "The post should have only one {$taxonomy}." );
		$this->assertSame( $term_fr, reset( $terms ), "The {$taxonomy} should have been translated." );
	}
}
