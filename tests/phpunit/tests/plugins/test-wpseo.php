<?php

if ( file_exists( DIR_TESTROOT . '/../wordpress-seo/wp-seo.php' ) ) {

	require_once DIR_TESTROOT . '/../wordpress-seo/wp-seo.php';
	require_once DIR_TESTROOT . '/../wordpress-seo/inc/sitemaps/class-sitemaps.php';

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

			require_once PLL_INC . '/api.php';
			$GLOBALS['polylang'] = &self::$polylang; // we still use the global $polylang

			self::$polylang->options['hide_default'] = 0;

			_wpseo_activate();
			$GLOBALS['wpseo_sitemaps'] = new WPSEO_Sitemaps();
			$this->pll_seo = new PLL_WPSEO();
			add_action( 'pll_init', array( $this->pll_seo, 'init' ) ); // Load the compatibility layer

			self::$polylang = new PLL_Frontend( self::$polylang->links_model );
			self::$polylang->init();
		}

		function test_opengraph() {
			if ( version_compare( WPSEO_VERSION, '14.0', '<' ) ) {
				$this->markTestSkipped( 'This test requires Yoast SEO 14.0 or newer' );
			}

			require_once DIR_TESTROOT . '/../wordpress-seo/src/functions.php';

			// Create posts to get something on home page.
			$en = $this->factory->post->create();
			self::$polylang->model->post->set_language( $en, 'en' );

			$fr = $this->factory->post->create();
			self::$polylang->model->post->set_language( $fr, 'fr' );

			$this->go_to( home_url( '/?lang=fr' ) );
			self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );

			do_action_ref_array( 'pll_init', array( &self::$polylang ) );

			// We need to inform Yoast SEO that the migration is ok, otherwise the class Front_End_Integration is not loaded.
			update_option( 'yoast_migrations_free', array( 'version' => WPSEO_VERSION ) );
			YoastSEO();

			ob_start();
			do_action( 'wpseo_head' );
			$output = ob_get_clean();

			$this->assertNotFalse( strpos( $output, '<meta property="og:locale" content="fr_FR" />' ) ); // Test WPSEO just in case.
			$this->assertFalse( strpos( $output, '<meta property="og:locale:alternate" content="fr_FR" />' ) ); // Only for alternate languages.
			$this->assertNotFalse( strpos( $output, '<meta property="og:locale:alternate" content="en_US" />' ) );
		}

		function test_post_sitemap_for_code_in_url() {
			$en = $this->factory->post->create();
			self::$polylang->model->post->set_language( $en, 'en' );

			$fr = $this->factory->post->create();
			self::$polylang->model->post->set_language( $fr, 'fr' );

			do_action_ref_array( 'pll_init', array( &self::$polylang ) );

			$sm = new WPSEO_Sitemaps_Double();
			$sm->init_sitemaps_providers(); // Since Yoast SEO 5.3.
			set_query_var( 'sitemap', 'post' );
			$this->pll_seo->before_sitemap( $GLOBALS['wp_query'] ); // Need a direct call as we don't fire the 'pre_get_posts' filter

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
			$sm->init_sitemaps_providers(); // Since Yoast SEO 5.3.
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
	}

} // file_exists
