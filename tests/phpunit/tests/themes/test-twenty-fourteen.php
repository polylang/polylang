<?php

if ( file_exists( DIR_TESTROOT . '/../wordpress/wp-content/themes/twentyfourteen/style.css' ) ) {

	class Twenty_Fourteen_Test extends PLL_UnitTestCase {
		protected static $stylesheet;
		protected static $tag_en;
		protected static $tag_fr;

		/**
		 * @param WP_UnitTest_Factory $factory
		 */
		public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
			parent::wpSetUpBeforeClass( $factory );

			self::create_language( 'en_US' );
			self::create_language( 'fr_FR' );

			require_once POLYLANG_DIR . '/include/api.php';
			$GLOBALS['polylang'] = &self::$polylang;

			self::$stylesheet = get_option( 'stylesheet' ); // save default theme
			switch_theme( 'twentyfourteen' );
		}

		function setUp() {
			parent::setUp();

			require_once get_template_directory() . '/functions.php';
			twentyfourteen_setup();
			Featured_Content::init();

			self::$polylang = new PLL_Frontend( self::$polylang->links_model );
			self::$polylang->init();
			self::$polylang->featured_content = new PLL_Featured_Content();
			self::$polylang->featured_content->init();
		}

		static function wpTearDownAfterClass() {
			parent::wpTearDownAfterClass();

			switch_theme( self::$stylesheet );
		}

		function test_ephemera_widget() {
			$GLOBALS['wp_rewrite']->set_permalink_structure( '' );

			$en = $this->factory->post->create( array( 'post_content' => 'Test' ) );
			set_post_format( $en, 'aside' );
			self::$polylang->model->post->set_language( $en, 'en' );

			$fr = $this->factory->post->create( array( 'post_content' => 'Essai' ) );
			set_post_format( $fr, 'aside' );
			self::$polylang->model->post->set_language( $fr, 'fr' );

			self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );

			require_once get_template_directory() . '/inc/widgets.php';
			$widget = new Twenty_Fourteen_Ephemera_Widget();
			$args = array( 'before_title' => '', 'after_title' => '', 'before_widget' => '', 'after_widget' => '' );
			$instance = array();

			ob_start();
			$widget->widget( $args, $instance );
			$out = ob_get_clean();

			$this->assertFalse( strpos( $out, '<p>Test</p>' ) );
			$this->assertNotFalse( strpos( $out, '<p>Essai</p>' ) );
		}

		function setup_featured_tags() {
			self::$tag_en = $en = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'featured' ) );
			self::$polylang->model->term->set_language( $en, 'en' );

			self::$tag_fr = $fr = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'en avant' ) );
			self::$polylang->model->term->set_language( $fr, 'fr' );
			self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr' ) );

			$options = array(
				'hide-tag' => 1,
				'tag-id'   => $en,
				'tag-name' => 'featured',
			);

			update_option( 'featured-content', $options );
		}

		function test_option_featured_content() {
			$this->setup_featured_tags();

			self::$polylang->curlang = self::$polylang->model->get_language( 'en' );
			$settings = Featured_Content::get_setting();
			$this->assertEquals( self::$tag_en, $settings['tag-id'] );

			self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
			$settings = Featured_Content::get_setting();
			$this->assertEquals( self::$tag_fr, $settings['tag-id'] );
		}

		function test_featured_content_ids() {
			$this->setup_featured_tags();

			$en = $this->factory->post->create( array( 'tags_input' => array( 'featured' ) ) );
			self::$polylang->model->post->set_language( $en, 'en' );

			$fr = $this->factory->post->create( array( 'tags_input' => array( 'en avant' ) ) );
			self::$polylang->model->post->set_language( $fr, 'fr' );

			do_action_ref_array( 'pll_init', array( &self::$polylang ) ); // to pass the test in PLL_Plugins_Compat::twenty_fourteen_featured_content_ids

			self::$polylang->curlang = self::$polylang->model->get_language( 'en' );
			$this->assertEquals( array( get_post( $en ) ), twentyfourteen_get_featured_posts() );

			self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
			$this->assertEquals( array( get_post( $fr ) ), twentyfourteen_get_featured_posts() );
		}
	}

} // file_exists
