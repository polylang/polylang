<?php

class Strings_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once POLYLANG_DIR . '/include/api.php';
	}

	public function set_up() {
		parent::set_up();

		$this->links_model = self::$model->get_links_model();
	}

	/**
	 * Copied from WP widgets tests.
	 */
	public function clean_up_global_scope() {
		global $_wp_sidebars_widgets, $wp_widget_factory, $wp_registered_sidebars, $wp_registered_widgets, $wp_registered_widget_controls, $wp_registered_widget_updates;

		$_wp_sidebars_widgets = array();
		$wp_registered_sidebars = array();
		$wp_registered_widgets = array();
		$wp_registered_widget_controls = array();
		$wp_registered_widget_updates = array();
		$wp_widget_factory->widgets = array();

		parent::clean_up_global_scope();
	}

	protected function add_widget_search() {
		update_option(
			'widget_search',
			array(
				2              => array( 'title' => '' ),
				'_multiwidget' => 1,
			)
		);

		update_option(
			'sidebars_widgets',
			array(
				'wp_inactive_widgets' => array(),
				'sidebar-1'           => array( 'search-2' ),
			)
		);
	}

	public function _return_fr_FR() {
		return array( 'fr_FR' );
	}

	public function test_base_strings() {
		$pll_admin = new PLL_Admin( $this->links_model );
		$pll_admin->init();
		$strings = PLL_Admin_Strings::get_strings();
		$names = wp_list_pluck( $strings, 'name' );
		$this->assertCount( 4, array_intersect( array( 'blogname', 'blogdescription', 'date_format', 'time_format' ), $names ) );
	}

	/**
	 * /!\ The order of nest two tests matters due to static protected strings in PLL_Admin_Strings.
	 */
	public function test_widget_title_filtered_by_language() {
		global $wp_registered_widgets;

		$this->add_widget_search();

		wp_widgets_init();
		$wp_widget_search = $wp_registered_widgets['search-2']['callback'][0];

		$pll_admin = new PLL_Admin( $this->links_model );
		$pll_admin->init();
		new PLL_Admin_Filters_Widgets_Options( $pll_admin );

		$_POST = array(
			'widget-id'     => 'search-2',
			'id_base'       => 'search',
			'widget_number' => 2,
			'multi_number'  => '',
		);

		$_POST['widget-search'][2] = array(
			'title'    => 'My Title',
			'pll_lang' => 'en',
		);

		$wp_widget_search->update_callback();
		$strings = PLL_Admin_Strings::get_strings();
		$strings = wp_list_pluck( $strings, 'string' );
		$this->assertNotContains( 'My Title', $strings );
	}

	public function test_widget_title_in_all_languages() {
		global $wp_registered_widgets;

		$this->add_widget_search();

		wp_widgets_init();
		$wp_widget_search = $wp_registered_widgets['search-2']['callback'][0];

		$pll_admin = new PLL_Admin( $this->links_model );
		$pll_admin->init();
		new PLL_Admin_Filters( $pll_admin );

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

	/**
	 * Bug fixed in 2.1.
	 * Issue #63.
	 */
	public function test_html_string() {
		update_option( 'use_balanceTags', 1 ); // To break malformed html in versions < 2.1
		$language = self::$model->get_language( 'fr' );

		$_mo = new PLL_MO();
		$_mo->add_entry( $_mo->make_entry( '<p>test</p>', '<p>test fr</p>' ) );
		$_mo->add_entry( $_mo->make_entry( '<p>malformed<p>', '<p>malformed fr<p>' ) );
		$_mo->export_to_db( $language );

		$mo = new PLL_MO();
		$mo->import_from_db( $language );

		$frontend = new PLL_Frontend( $this->links_model );
		$frontend->curlang = $language;
		do_action( 'pll_language_defined' );

		$this->assertEquals( '<p>test fr</p>', pll__( '<p>test</p>' ) );
		$this->assertEquals( '<p>malformed fr<p>', pll__( '<p>malformed<p>' ) );
	}

	/**
	 * Bug introduced in 2.1 and fixed in 2.1.1.
	 * Issue #94.
	 */
	public function test_slashed_string() {
		$language = self::$model->get_language( 'fr' );

		$_mo = new PLL_MO();
		$_mo->add_entry( $_mo->make_entry( '\slashed', '\slashed fr' ) );
		$_mo->add_entry( $_mo->make_entry( '\\slashed', '\\slashed fr' ) );
		$_mo->add_entry( $_mo->make_entry( '\\\slashed', '\\\slashed fr' ) );
		$_mo->export_to_db( $language );

		$mo = new PLL_MO();
		$mo->import_from_db( $language );

		$frontend = new PLL_Frontend( $this->links_model );
		$frontend->curlang = $language;
		do_action( 'pll_language_defined' );

		$this->assertEquals( '\slashed fr', pll__( '\slashed' ) );
		$this->assertEquals( '\\slashed fr', pll__( '\\slashed' ) );
		$this->assertEquals( '\\\slashed fr', pll__( '\\\slashed' ) );
	}

	/**
	 * Tests workaround of https://core.trac.wordpress.org/ticket/55941
	 */
	public function test_empty_string() {
		$language = self::$model->get_language( 'fr' );

		$_mo = new PLL_MO();
		$_mo->add_entry( $_mo->make_entry( '0', '0' ) );
		$_mo->export_to_db( $language );

		$mo = new PLL_MO();
		$mo->import_from_db( $language );

		$frontend = new PLL_Frontend( $this->links_model );
		$frontend->curlang = $language;
		do_action( 'pll_language_defined' );

		$this->assertEquals( '0', pll__( '0' ) );
		$this->assertEquals( '', pll__( '' ) );
	}

	public function test_switch_to_locale() {
		// Strings translations
		$mo = new PLL_MO();
		$mo->add_entry( $mo->make_entry( 'test', 'test en' ) );
		$mo->export_to_db( self::$model->get_language( 'en' ) );

		$mo = new PLL_MO();
		$mo->add_entry( $mo->make_entry( 'test', 'test fr' ) );
		$mo->export_to_db( self::$model->get_language( 'fr' ) );

		// Reset $wp_locale_switcher to add fr_FR in the list of available languages
		add_filter( 'get_available_languages', array( $this, '_return_fr_FR' ) );
		$old_locale_switcher = $GLOBALS['wp_locale_switcher'];
		$GLOBALS['wp_locale_switcher'] = new WP_Locale_Switcher();
		$GLOBALS['wp_locale_switcher']->init();

		$frontend = new PLL_Frontend( $this->links_model );
		$frontend->curlang = self::$model->get_language( 'en' );
		do_action( 'pll_language_defined' );

		$this->assertEquals( 'test en', pll__( 'test' ) );

		switch_to_locale( 'fr_FR' );
		$this->assertEquals( 'test fr', pll__( 'test' ) );

		restore_current_locale();
		$this->assertEquals( 'test en', pll__( 'test' ) );

		$GLOBALS['wp_locale_switcher'] = $old_locale_switcher; // Reset the original global var
	}
}
