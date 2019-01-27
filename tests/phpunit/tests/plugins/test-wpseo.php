<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( file_exists( $_tests_dir . '/../wordpress-seo/wp-seo.php' ) ) {

	require_once $_tests_dir . '/../wordpress-seo/inc/sitemaps/class-sitemaps.php';

	/**
	 * Copied from WPSEO unit tests
	 */
	class WPSEO_Sitemaps_Double extends WPSEO_Sitemaps {
		/**
		 * Overwrite sitemap_close() so we don't die on outputting the sitemap
		 */
		function sitemap_close() {
			remove_all_actions( 'wp_footer' );
		}

		/**
		 * Cleans out the sitemap variable
		 */
		public function reset() {
			$this->sitemap     = false;
			$this->bad_sitemap = false;
		}
	}

	class WPSEO_Test extends PLL_UnitTestCase {
		protected $structure = '/%postname%/';
		protected $hosts;

		static function wpSetUpBeforeClass() {
			parent::wpSetUpBeforeClass();

			self::create_language( 'en_US' );
			self::create_language( 'fr_FR' );

			$_SERVER['SERVER_NAME'] = 'example.org';
		}

		function setUp() {
			parent::setUp();

			global $_tests_dir;
			require_once $_tests_dir . '/../wordpress-seo/wp-seo.php';

			require_once PLL_INC . '/api.php';
			$GLOBALS['polylang'] = &self::$polylang; // we still use the global $polylang

			self::$polylang->options['hide_default'] = 0;

			_wpseo_activate();
			$GLOBALS['wpseo_sitemaps'] = new WPSEO_Sitemaps();
			add_action( 'pll_init', array( new PLL_WPSEO(), 'init' ) ); // Load the compatibility layer
			WPSEO_Frontend::get_instance();

			self::$polylang = new PLL_Frontend( self::$polylang->links_model );
			self::$polylang->init();
		}

		function tearDown() {
			parent::tearDown();

			unset( $GLOBALS['polylang'] );
		}

		function test_opengraph() {
			// create posts to get something  on home page
			$en = $this->factory->post->create();
			self::$polylang->model->post->set_language( $en, 'en' );

			$fr = $this->factory->post->create();
			self::$polylang->model->post->set_language( $fr, 'fr' );

			$this->go_to( home_url( '/?lang=fr' ) );
			self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );

			do_action( 'pll_language_defined' );
			do_action_ref_array( 'pll_init', array( &self::$polylang ) );
			do_action( 'template_redirect' ); // for home_url filter
			wpseo_frontend_head_init();

			ob_start();
			do_action( 'wp_head' );
			$output = ob_get_clean();

			$this->assertNotFalse( strpos( $output, '<meta property="og:locale" content="fr_FR" />' ) ); // test WPSEO just in case
			$this->assertFalse( strpos( $output, '<meta property="og:locale:alternate" content="fr_FR" />' ) ); // only for alternate languages
			$this->assertNotFalse( strpos( $output, '<meta property="og:locale:alternate" content="en_US" />' ) );
			$this->assertNotFalse( strpos( $output, '<link rel="canonical" href="http://example.org/?lang=fr" />' ) ); // covers pll_home_url_white_list
		}

		function test_post_sitemap_for_code_in_url() {
			$en = $this->factory->post->create();
			self::$polylang->model->post->set_language( $en, 'en' );

			$fr = $this->factory->post->create();
			self::$polylang->model->post->set_language( $fr, 'fr' );

			do_action_ref_array( 'pll_init', array( &self::$polylang ) );

			$sm = new WPSEO_Sitemaps_Double();
			set_query_var( 'sitemap', 'post' );

			ob_start();
			$sm->redirect( $GLOBALS['wp_the_query'] );
			$output = ob_get_clean();

			$en = htmlspecialchars( get_permalink( $en ) ); // WPSEO uses htmlspecialchars
			$fr = htmlspecialchars( get_permalink( $fr ) );

			// the sitemap must contain all languages
			$this->assertNotFalse( strpos( $output, "<loc>$en</loc>" ) );
			$this->assertNotFalse( strpos( $output, "<loc>$fr</loc>" ) );

			// bug fixed in v1.9
			// the sitemap must contain the home urls in all languages
			$this->assertNotFalse( strpos( $output, '<loc>' . home_url( '/?lang=en' ) . '</loc>' ) );
			$this->assertNotFalse( strpos( $output, '<loc>' . home_url( '/?lang=fr' ) . '</loc>' ) );
		}

		function test_category_sitemap_for_code_in_url() {
			$en = $this->factory->category->create();
			self::$polylang->model->term->set_language( $en, 'en' );
			$post_id = $this->factory->post->create(); // by default, the sitemap hides empty categories
			self::$polylang->model->post->set_language( $post_id, 'en' );
			wp_set_post_terms( $post_id, array( $en ), 'category' );

			$fr = $this->factory->category->create();
			self::$polylang->model->term->set_language( $fr, 'fr' );
			$post_id = $this->factory->post->create();
			self::$polylang->model->post->set_language( $post_id, 'fr' );
			wp_set_post_terms( $post_id, array( $fr ), 'category' );

			do_action_ref_array( 'pll_init', array( &self::$polylang ) );

			$sm = new WPSEO_Sitemaps_Double();
			set_query_var( 'sitemap', 'category' );
			$GLOBALS['wp_query']->query['sitemap'] = 'category'; // FIXME isn't that too hacky?

			ob_start();
			$sm->redirect( $GLOBALS['wp_the_query'] );
			$output = ob_get_clean();

			$en = htmlspecialchars( get_category_link( $en ) ); // WPSEO uses htmlspecialchars
			$fr = htmlspecialchars( get_category_link( $fr ) );

			// the sitemap must contain all languages
			$this->assertNotFalse( strpos( $output, "<loc>$en</loc>" ) );
			$this->assertNotFalse( strpos( $output, "<loc>$fr</loc>" ) );
		}

		function test_post_sitemap_for_subdomains() {
			// FIXME The test works alone but static vars in Yoast SEO make it conclict with test_post_sitemap_for_code_in_url()
			// See https://github.com/Yoast/wordpress-seo/issues/6926
			$this->markTestSkipped();

			global $wp_rewrite;

			// setup subdomains
			$this->hosts = array(
				'en' => 'http://example.org',
				'fr' => 'http://fr.example.org',
			);

			self::$polylang->options['hide_default'] = 1;
			self::$polylang->options['force_lang'] = 2;

			// switch to pretty permalinks
			$wp_rewrite->init();
			$wp_rewrite->set_permalink_structure( $this->structure );

			self::$polylang->model->post->register_taxonomy(); // needs this for 'lang' query var
			create_initial_taxonomies();
			self::$polylang->links_model = self::$polylang->model->get_links_model();

			$wp_rewrite->flush_rules();

			// init frontend
			self::$polylang = new PLL_Frontend( self::$polylang->links_model );
			self::$polylang->init();
			self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
			do_action( 'pll_language_defined' );

			// de-activate cache for links
			self::$polylang->links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
			self::$polylang->links->cache->method( 'get' )->willReturn( false );
			self::$polylang->filters_links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
			self::$polylang->filters_links->cache->method( 'get' )->willReturn( false );

			// create posts
			$en = $this->factory->post->create();
			self::$polylang->model->post->set_language( $en, 'en' );

			$fr = $this->factory->post->create();
			self::$polylang->model->post->set_language( $fr, 'fr' );

			$this->go_to( $this->hosts['fr'] . '/post-sitemap.xml' );
			$sm = new WPSEO_Sitemaps_Double();
			set_query_var( 'sitemap', 'post' );

			ob_start();
			$sm->redirect( $GLOBALS['wp_the_query'] );
			$output = ob_get_clean();

			$en = htmlspecialchars( get_permalink( $en ) ); // WPSEO uses htmlspecialchars
			$fr = htmlspecialchars( get_permalink( $fr ) );

			// the sitemap must contain only one language
			$this->assertNotFalse( strpos( $output, '<loc>' . $this->hosts['fr'] . '</loc>' ) );
			$this->assertNotFalse( strpos( $output, "<loc>$fr</loc>" ) );
			$this->assertFalse( strpos( $output, "<loc>$en</loc>" ) );
		}
	}

} // file_exists
