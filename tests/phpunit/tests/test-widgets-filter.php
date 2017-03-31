<?php

class Widgets_Filter_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	function setUp() {
		parent::setUp();

		require_once PLL_INC . '/api.php'; // usually loaded only if an instance of Polylang exists
		$GLOBALS['polylang'] = self::$polylang; // We use PLL()
	}

	function tearDown() {
		parent::tearDown();

		unset( $GLOBALS['polylang'] );
	}

	// copied from WP widgets tests
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
			'widget-id'     => 'search-2',
			'id_base'       => 'search',
			'widget_number' => 2,
			'multi_number'  => '',
		);

		$_POST['search-2_lang_choice'] = $lang;
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
}
