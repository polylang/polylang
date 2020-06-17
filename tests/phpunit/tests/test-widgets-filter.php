<?php

class Widgets_Filter_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	function setUp() {
		parent::setUp();

		require_once POLYLANG_DIR . '/include/api.php'; // Usually loaded only if an instance of Polylang exists
		$GLOBALS['polylang'] = self::$polylang; // We use PLL()
	}

	/**
	 * Copied from WP widgets tests
	 */
	function clean_up_global_scope() {
		global $wp_widget_factory, $wp_registered_sidebars, $wp_registered_widgets, $wp_registered_widget_controls, $wp_registered_widget_updates;

		$wp_registered_sidebars = array();
		$wp_registered_widgets = array();
		$wp_registered_widget_controls = array();
		$wp_registered_widget_updates = array();
		$wp_widget_factory->widgets = array();

		parent::clean_up_global_scope();
	}

	function test_form() {
		global $wp_registered_widgets;

		set_current_screen( 'widgets' );
		wp_widgets_init();
		$wp_widget_search = $wp_registered_widgets['search-2']['callback'][0];
		self::$polylang->filters = new PLL_Admin_Filters( self::$polylang );
		ob_start();
		$wp_widget_search->form_callback( 2 );
		$this->assertNotFalse( strpos( ob_get_clean(), 'search-2_lang_choice' ) );
	}

	function update_lang_choice( $widget, $lang ) {
		self::$polylang->filters = new PLL_Admin_Filters( self::$polylang );

		$_POST = array(
			'widget-id'     => $widget->id,
			'id_base'       => $widget->id_base,
			'widget_number' => $widget->number,
			'multi_number'  => '',
		);

		$_POST[ $widget->id . '_lang_choice' ] = $lang;
		$widget->update_callback();
	}

	function test_display_with_filter() {
		global $wp_registered_widgets;

		wp_widgets_init();
		$wp_widget_search = $wp_registered_widgets['search-2']['callback'][0];
		$this->update_lang_choice( $wp_widget_search, 'en' );

		self::$polylang->filters = new PLL_Frontend_Filters( self::$polylang );
		$args = array( 'before_title' => '', 'after_title' => '', 'before_widget' => '', 'after_widget' => '' );
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );
		ob_start();
		$wp_widget_search->display_callback( $args, 2 );
		$this->assertNotEmpty( ob_get_clean() );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		ob_start();
		$wp_widget_search->display_callback( $args, 2 );
		$this->assertEmpty( ob_get_clean() );
	}

	function test_display_with_no_filter() {
		global $wp_registered_widgets;

		wp_widgets_init();
		$wp_widget_search = $wp_registered_widgets['search-2']['callback'][0];
		$this->update_lang_choice( $wp_widget_search, 0 );

		self::$polylang->filters = new PLL_Frontend_Filters( self::$polylang );
		$args = array( 'before_title' => '', 'after_title' => '', 'before_widget' => '', 'after_widget' => '' );
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );
		ob_start();
		$wp_widget_search->display_callback( $args, 2 );
		$this->assertNotEmpty( ob_get_clean() );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		ob_start();
		$wp_widget_search->display_callback( $args, 2 );
		$this->assertNotEmpty( ob_get_clean() );
	}


	function test_widget_media_image() {
		self::$polylang->options['media_support'] = 1;
		self::$polylang->filters_media = new PLL_Admin_Filters_Media( self::$polylang );
		self::$polylang->posts = new PLL_CRUD_Posts( self::$polylang );

		self::$polylang->pref_lang = self::$polylang->model->get_language( 'en' );
		$filename = dirname( __FILE__ ) . '/../data/image.jpg';

		$en = $this->factory->attachment->create_upload_object( $filename );
		wp_update_post(
			array(
				'ID'           => $en,
				'post_title'   => 'Test image EN',
				'post_excerpt' => 'Caption EN',
			)
		);
		update_post_meta( $en, '_wp_attachment_image_alt', 'Alt text EN' );

		$fr = self::$polylang->posts->create_media_translation( $en, 'fr' );
		wp_update_post(
			array(
				'ID'           => $fr,
				'post_title'   => 'Test image FR',
				'post_excerpt' => 'Caption FR',
			)
		);
		update_post_meta( $fr, '_wp_attachment_image_alt', 'Alt text FR' );

		// Switch to frontend
		self::$polylang->filters = new PLL_Frontend_Filters( self::$polylang );
		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );

		$widget = new WP_Widget_Media_Image();
		$args = array( 'before_title' => '', 'after_title' => '', 'before_widget' => '', 'after_widget' => '' );

		// Empty fields in Edit Image
		$instance = array( 'attachment_id' => $en, 'caption' => null ); // Need to explicitely set 'caption' to null since WP 4.9. See #42350
		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertContains( "wp-image-{$fr}", $output ); // CSS class
		$this->assertContains( 'Alt text FR', $output );
		$this->assertContains( 'Caption FR', $output );
		$this->assertNotContains( 'Test image FR', $output );

		// Edit Image fields are filled
		$instance = array( 'attachment_id' => $en, 'alt' => 'Custom alt', 'caption' => 'Custom caption', 'image_title' => 'Custom title' );
		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertContains( 'Alt text FR', $output );
		$this->assertContains( 'Caption FR', $output );
		$this->assertContains( 'Test image FR', $output );
	}

	function test_wp_get_sidebars_widgets() {
		global $wp_registered_widgets;

		wp_widgets_init();
		$wp_widget_search = $wp_registered_widgets['search-2']['callback'][0];
		$this->update_lang_choice( $wp_widget_search, 'en' );

		self::$polylang->filters = new PLL_Frontend_Filters( self::$polylang );
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );

		self::$polylang->filters->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		self::$polylang->filters->cache->method( 'get' )->willReturn( false );

		$sidebars = wp_get_sidebars_widgets();
		$this->assertTrue( in_array( 'search-2', $sidebars['sidebar-1'] ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		$sidebars = wp_get_sidebars_widgets();
		$this->assertFalse( in_array( 'search-2', $sidebars['sidebar-1'] ) );
	}
}
