<?php

class Strings_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once POLYLANG_DIR . '/include/api.php';
		$GLOBALS['polylang'] = &self::$polylang; // The WPML API uses the global $polylang
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

	function _return_fr_FR() {
		return array( 'fr_FR' );
	}

	function test_base_strings() {
		self::$polylang = new PLL_Admin( self::$polylang->links_model );
		self::$polylang->init();
		$strings = PLL_Admin_Strings::get_strings();
		$names = wp_list_pluck( $strings, 'name' );
		$this->assertCount( 4, array_intersect( array( 'blogname', 'blogdescription', 'date_format', 'time_format' ), $names ) );
	}

	// FIXME: order of nest two tests matters due to static protected strings in PLL_Admin_Strings
	function test_widget_title_filtered_by_language() {
		global $wp_registered_widgets;
		wp_widgets_init();
		$wp_widget_search = $wp_registered_widgets['search-2']['callback'][0];

		self::$polylang->filters = new PLL_Admin_Filters( self::$polylang );

		$_POST = array(
			'widget-id'     => 'search-2',
			'id_base'       => 'search',
			'widget_number' => 2,
			'multi_number'  => '',
		);

		$_POST['widget-search'][2] = array(
			'title' => 'My Title',
		);

		$_POST['search-2_lang_choice'] = 'en';

		$wp_widget_search->update_callback();
		$strings = PLL_Admin_Strings::get_strings();
		$strings = wp_list_pluck( $strings, 'string' );
		$this->assertNotContains( 'My Title', $strings );
	}

	function test_widget_title_in_all_languages() {
		global $wp_registered_widgets;
		wp_widgets_init();
		$wp_widget_search = $wp_registered_widgets['search-2']['callback'][0];

		self::$polylang->filters = new PLL_Admin_Filters( self::$polylang );

		$_POST = array(
			'widget-id'     => 'search-2',
			'id_base'       => 'search',
			'widget_number' => 2,
			'multi_number'  => '',
		);

		$_POST['widget-search'][2] = array(
			'title' => 'My Title',
		);

		$_POST['search-2_lang_choice'] = 0;

		$wp_widget_search->update_callback();
		$strings = PLL_Admin_Strings::get_strings();
		$strings = wp_list_pluck( $strings, 'string' );
		$this->assertContains( 'My Title', $strings );
	}

	// Bug fixed in 2.1
	// Test #63
	function test_html_string() {
		update_option( 'use_balanceTags', 1 ); // To break malformed html in versions < 2.1
		self::$polylang->curlang = $language = self::$polylang->model->get_language( 'fr' );
		$_mo = new PLL_MO();
		$_mo->add_entry( $_mo->make_entry( '<p>test</p>', '<p>test fr</p>' ) );
		$_mo->add_entry( $_mo->make_entry( '<p>malformed<p>', '<p>malformed fr<p>' ) );
		$_mo->export_to_db( $language );

		$mo = new PLL_MO();
		$mo->import_from_db( $language );
		$GLOBALS['l10n']['pll_string'] = &$mo;
		do_action( 'pll_language_defined' );

		$this->assertEquals( '<p>test fr</p>', pll__( '<p>test</p>' ) );
		$this->assertEquals( '<p>malformed fr<p>', pll__( '<p>malformed<p>' ) );
	}

	// Bug introduced in 2.1 and fixed in 2.1.1
	// Test #94
	function test_slashed_string() {
		self::$polylang->curlang = $language = self::$polylang->model->get_language( 'fr' );
		$_mo = new PLL_MO();
		$_mo->add_entry( $_mo->make_entry( '\slashed', '\slashed fr' ) );
		$_mo->add_entry( $_mo->make_entry( '\\slashed', '\\slashed fr' ) );
		$_mo->add_entry( $_mo->make_entry( '\\\slashed', '\\\slashed fr' ) );
		$_mo->export_to_db( $language );

		$mo = new PLL_MO();
		$mo->import_from_db( $language );
		$GLOBALS['l10n']['pll_string'] = &$mo;
		do_action( 'pll_language_defined' );

		$this->assertEquals( '\slashed fr', pll__( '\slashed' ) );
		$this->assertEquals( '\\slashed fr', pll__( '\\slashed' ) );
		$this->assertEquals( '\\\slashed fr', pll__( '\\\slashed' ) );
	}

	function test_switch_to_locale() {
		// Strings translations
		$mo = new PLL_MO();
		$mo->add_entry( $mo->make_entry( 'test', 'test en' ) );
		$mo->export_to_db( self::$polylang->model->get_language( 'en' ) );

		$mo = new PLL_MO();
		$mo->add_entry( $mo->make_entry( 'test', 'test fr' ) );
		$mo->export_to_db( self::$polylang->model->get_language( 'fr' ) );

		// Reset $wp_locale_switcher to add fr_FR in the list of available languages
		add_filter( 'get_available_languages', array( $this, '_return_fr_FR' ) );
		$old_locale_switcher = $GLOBALS['wp_locale_switcher'];
		$GLOBALS['wp_locale_switcher'] = new WP_Locale_Switcher();
		$GLOBALS['wp_locale_switcher']->init();

		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );
		do_action( 'pll_language_defined' );

		$this->assertEquals( 'test en', pll__( 'test' ) );

		switch_to_locale( 'fr_FR' );
		$this->assertEquals( 'test fr', pll__( 'test' ) );

		restore_current_locale();
		$this->assertEquals( 'test en', pll__( 'test' ) );

		$GLOBALS['wp_locale_switcher'] = $old_locale_switcher; // Reset the original global var
	}
}
