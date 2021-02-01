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

			self::$stylesheet = get_option( 'stylesheet' ); // save default theme
			switch_theme( 'twentyfourteen' );
		}

		function setUp() {
			parent::setUp();

			require_once get_template_directory() . '/functions.php';
			twentyfourteen_setup();
			Featured_Content::init();

			$links_model = self::$model->get_links_model();
			$this->frontend = new PLL_Frontend( $links_model );
			$GLOBALS['polylang'] = &$this->frontend;
			$this->frontend->featured_content = new PLL_Featured_Content();
			$this->frontend->featured_content->init();
		}

		static function wpTearDownAfterClass() {
			parent::wpTearDownAfterClass();

			switch_theme( self::$stylesheet );
		}

		function tearDown() {
			parent::tearDown();

			unset( $GLOBALS['polylang'] );
		}

		function test_ephemera_widget() {
			global $content_width; // The widget accesses this global, no matter what it contains.
			$GLOBALS['wp_rewrite']->set_permalink_structure( '' );

			$en = $this->factory->post->create( array( 'post_content' => 'Test' ) );
			set_post_format( $en, 'aside' );
			self::$model->post->set_language( $en, 'en' );

			$fr = $this->factory->post->create( array( 'post_content' => 'Essai' ) );
			set_post_format( $fr, 'aside' );
			self::$model->post->set_language( $fr, 'fr' );

			$this->frontend->curlang = self::$model->get_language( 'fr' );

			require_once get_template_directory() . '/inc/widgets.php';
			$widget = new Twenty_Fourteen_Ephemera_Widget();
			$args = array( 'before_title' => '', 'after_title' => '', 'before_widget' => '', 'after_widget' => '' );
			$instance = array();

			ob_start();
			$widget->widget( $args, $instance );
			$out = ob_get_clean();

			$this->assertFalse( strpos( $out, '<p>Test</p>' ) );
			$this->assertNotFalse( strpos( $out, '<p>Essai</p>' ) );

			unset( $content_width );
		}

		function setup_featured_tags() {
			self::$tag_en = $en = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'featured' ) );
			self::$model->term->set_language( $en, 'en' );

			self::$tag_fr = $fr = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'en avant' ) );
			self::$model->term->set_language( $fr, 'fr' );
			self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

			$options = array(
				'hide-tag' => 1,
				'tag-id'   => $en,
				'tag-name' => 'featured',
			);

			update_option( 'featured-content', $options );
		}

		function test_option_featured_content() {
			$this->setup_featured_tags();

			$this->frontend->curlang = self::$model->get_language( 'en' );
			$settings = Featured_Content::get_setting();
			$this->assertEquals( self::$tag_en, $settings['tag-id'] );

			$this->frontend->curlang = self::$model->get_language( 'fr' );
			$settings = Featured_Content::get_setting();
			$this->assertEquals( self::$tag_fr, $settings['tag-id'] );
		}

		function test_featured_content_ids() {
			$this->setup_featured_tags();

			$en = $this->factory->post->create( array( 'tags_input' => array( 'featured' ) ) );
			self::$model->post->set_language( $en, 'en' );

			$fr = $this->factory->post->create( array( 'tags_input' => array( 'en avant' ) ) );
			self::$model->post->set_language( $fr, 'fr' );

			do_action_ref_array( 'pll_init', array( &$this->frontend ) ); // to pass the test in PLL_Plugins_Compat::twenty_fourteen_featured_content_ids

			$this->frontend->curlang = self::$model->get_language( 'en' );
			$this->assertEquals( array( get_post( $en ) ), twentyfourteen_get_featured_posts() );

			$this->frontend->curlang = self::$model->get_language( 'fr' );
			$this->assertEquals( array( get_post( $fr ) ), twentyfourteen_get_featured_posts() );
		}
	}

} // file_exists
