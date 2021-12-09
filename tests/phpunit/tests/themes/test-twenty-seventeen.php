<?php

if ( file_exists( DIR_TESTROOT . '/../wordpress/wp-content/themes/twentyseventeen/style.css' ) ) {

	class Twenty_Seventeen_Test extends PLL_UnitTestCase {
		protected static $stylesheet;

		/**
		 * @param WP_UnitTest_Factory $factory
		 */
		public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
			parent::wpSetUpBeforeClass( $factory );

			self::create_language( 'en_US' );
			self::create_language( 'fr_FR' );

			require_once POLYLANG_DIR . '/include/api.php';

			self::$stylesheet = get_option( 'stylesheet' ); // save default theme
			switch_theme( 'twentyseventeen' );
		}

		public function set_up() {
			parent::set_up();

			require_once get_template_directory() . '/functions.php';
		}

		public static function wpTearDownAfterClass() {
			parent::wpTearDownAfterClass();

			switch_theme( self::$stylesheet );
		}

		public function get_template_part( $slug, $name ) {
			include get_template_directory() . "/{$slug}-{$name}.php";
		}

		public function test_front_page_panels() {
			// Allow to locate the template part of Twenty Seventeen as the original mechanism uses constants
			add_action( 'get_template_part_template-parts/page/content', array( $this, 'get_template_part' ), 10, 2 );

			$en = self::factory()->post->create( array( 'post_title' => 'section 1 EN' ) );
			self::$model->post->set_language( $en, 'en' );

			$fr = self::factory()->post->create( array( 'post_title' => 'section 1 FR' ) );
			self::$model->post->set_language( $fr, 'fr' );

			self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

			set_theme_mod( 'panel_1', $en );

			$links_model = self::$model->get_links_model();
			$frontend = new PLL_Frontend( $links_model );
			$GLOBALS['polylang'] = &$frontend;
			$frontend->init();
			do_action( 'pll_init' );
			$twenty_seventeen = new PLL_Twenty_Seventeen();
			$twenty_seventeen->init();

			$frontend->curlang = self::$model->get_language( 'fr' ); // brute force

			ob_start();
			twentyseventeen_front_page_section( null, 1 );
			$this->assertNotFalse( strpos( ob_get_clean(), 'section 1 FR' ) );

			$frontend->curlang = self::$model->get_language( 'en' ); // brute force

			ob_start();
			twentyseventeen_front_page_section( null, 1 );
			$this->assertNotFalse( strpos( ob_get_clean(), 'section 1 EN' ) );

			unset( $GLOBALS['polylang'] );
		}
	}

} // file_exists
