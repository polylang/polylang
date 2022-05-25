<?php

if ( version_compare( $GLOBALS['wp_version'], '5.9', '>=' ) && file_exists( DIR_TESTROOT . '/../jetpack/jetpack.php' ) ) {

	require_once DIR_TESTROOT . '/../jetpack/functions.opengraph.php';

	class Jetpack_Test extends PLL_UnitTestCase {
		/**
		 * @param WP_UnitTest_Factory $factory
		 */
		public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
			parent::wpSetUpBeforeClass( $factory );

			self::create_language( 'en_US' );
			self::create_language( 'fr_FR' );
		}

		public function set_up() {
			parent::set_up();

			require_once POLYLANG_DIR . '/include/api.php'; // usually loaded only if an instance of Polylang exists
			$this->load_jetpack();

			$links_model = self::$model->get_links_model();
			$this->frontend = new PLL_Frontend( $links_model );
			$this->frontend->init();
			$GLOBALS['polylang'] = &$this->frontend; // we still use the global $polylang
		}

		protected function load_jetpack() {
			global $wp_actions;

			$wp_actions['wp_default_scripts'] = 0; // Trick Jetpack to avoid a doing_it_wrong notice.
			require_once DIR_TESTROOT . '/../jetpack/jetpack.php';
			$wp_actions['wp_default_scripts'] = 1;
		}

		public function test_opengraph() {
			// create posts to get something  on home page
			$en = $this->factory->post->create();
			self::$model->post->set_language( $en, 'en' );

			$fr = $this->factory->post->create();
			self::$model->post->set_language( $fr, 'fr' );

			$this->go_to( home_url( '/?lang=fr' ) );
			$this->frontend->curlang = self::$model->get_language( 'fr' );

			do_action_ref_array( 'pll_init', array( &$this->frontend ) );

			ob_start();
			jetpack_og_tags();
			$output = ob_get_clean();

			$this->assertNotFalse( strpos( $output, '<meta property="og:locale" content="fr_FR" />' ) );
			$this->assertFalse( strpos( $output, '<meta property="og:locale:alternate" content="fr_FR" />' ) ); // only for alternate languages
			$this->assertNotFalse( strpos( $output, '<meta property="og:locale:alternate" content="en_US" />' ) );
		}
	}

} // file_exists
