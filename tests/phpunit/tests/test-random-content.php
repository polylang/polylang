<?php

class Random_Test extends PLL_UnitTestCase {
	use PLL_Handle_WP_Redirect_Trait;

	private const CUSTOM_CPT = 'custom-random-cpt';
	private const CUSTOM_TAX = 'custom-random-tax';

	private static $post_en;
	private static $custom_post_id;

	private static $cat_en;
	private static $tag_en;
	private static $custom_term_en;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::require_api();

		self::generate_shared_fixtures( $factory );
		self::$model->languages->clean_cache();
	}

	public function set_up() {
		parent::set_up();

		if ( ! function_exists( 'wp_is_random_content_redirect_enabled' ) ) {
			// Backward compatibility with WordPress < 7.2
			$this->markTestSkipped( 'This test requires WordPress 7.2 or higher' );
		}

		$this->options = self::create_options(
			array(
				'default_lang' => 'en',
				'hide_default' => 0,
				'post_types'   => array( self::CUSTOM_CPT ),
				'taxonomies'   => array( self::CUSTOM_TAX ),
			)
		);

		$GLOBALS['polylang'] = &$this->pll_env;

		$GLOBALS['wp_rewrite']->init();
		$GLOBALS['wp_rewrite']->extra_rules_top = array();
		$GLOBALS['wp_rewrite']->set_permalink_structure( '/%postname%/' );
	}

	public function tear_down() {
		_unregister_post_type( self::CUSTOM_CPT );
		_unregister_taxonomy( self::CUSTOM_TAX );

		parent::tear_down();
	}

	/**
	 * @testWith ["en/random/", "en/a-post-in-en/"]
	 *           ["en/random/author/admin/", "en/a-post-in-en/"]
	 *           ["en/random/category/test-cat/", "en/a-post-in-en/"]
	 *           ["en/random/tag/test-tag/", "en/a-post-in-en/"]
	 *           ["en/random/custom-random-tax/test-custom-term/", "en/a-post-in-en/"]
	 *           ["en/random/custom-random-cpt/", "en/custom-random-cpt/a-custom-post-in-en/"]
	 *
	 * @param string $from_uri
	 * @param string $to_uri
	 * @return void
	 */
	public function test_should_redirect_to_random_post( string $from_uri, string $to_uri ): void {
		$this->assertTrue( wp_is_random_content_redirect_enabled() );
		$this->assert_redirect(
			array( $this, 'go_to' ),
			array( home_url( $from_uri ) )
		);
		$this->assert_redirect_location( "http://example.org/{$to_uri}" );
		$this->assert_redirect_status( 302 );
	}

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	protected static function generate_shared_fixtures( WP_UnitTest_Factory $factory ) {
		self::register_post_types_and_taxonomies();

		// Post, custom CPT.
		self::$post_en = $factory->post->create( array( 'post_title' => 'a-post-in-en', 'post_author' => 1 ) );
		self::$model->post->set_language( self::$post_en, 'en' );

		self::$custom_post_id = $factory->post->create( array( 'post_type' => self::CUSTOM_CPT, 'post_title' => 'a-custom-post-in-en', 'post_author' => 1 ) );
		self::$model->post->set_language( self::$custom_post_id, 'en' );

		// Category, tag, custom tax.
		self::$cat_en = $factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test-cat' ) );
		self::$model->term->set_language( self::$cat_en, 'en' );
		wp_set_post_terms( self::$post_en, array( self::$cat_en ), 'category', false );

		self::$tag_en = $factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'test-tag' ) );
		self::$model->term->set_language( self::$tag_en, 'en' );
		wp_set_post_terms( self::$post_en, array( self::$tag_en ), 'post_tag', false );

		self::$custom_term_en = self::factory()->term->create( array( 'taxonomy' => self::CUSTOM_TAX, 'name' => 'test-custom-term' ) );
		self::$model->term->set_language( self::$custom_term_en, 'en' );
		wp_set_post_terms( self::$post_en, array( self::$custom_term_en ), self::CUSTOM_TAX, false );
	}

	/**
	 * Setups the Polylang environment before testing redirects.
	 *
	 * Mostly copied from `PLL_UnitTestCase_Canonical::assertCanonical()`.
	 *
	 * @param string $test_url
	 * @return void
	 */
	public function go_to( $test_url ) {
		global $wp_rewrite;

		if ( did_action( 'pll_language_defined' ) ) {
			$this->fail( 'Tests MUST have only one call to Random_Test::go_to() per test.' );
		}

		// Needed by {@see pll_requested_url()}.
		$_SERVER['REQUEST_URI'] = $test_url;

		$model = new PLL_Model( $this->options );

		self::register_post_types_and_taxonomies();

		// Reset the links model according to the permalink structure.
		$links_model    = $model->get_links_model();
		$this->pll_env = new PLL_Frontend( $links_model );
		$this->pll_env->init();
		do_action_ref_array( 'pll_init', array( &$this->pll_env ) );

		$wp_rewrite->flush_rules();

		parent::go_to( $test_url );
		do_action( 'template_redirect' );
	}

	protected static function register_post_types_and_taxonomies() {
		create_initial_taxonomies();
		register_post_type(
			self::CUSTOM_CPT,
			array(
				'public'      => true,
				'has_archive' => true,
			)
		);
		register_taxonomy(
			self::CUSTOM_TAX,
			'post',
			array(
				'public'  => true,
				'rewrite' => true,
			)
		);
	}
}
