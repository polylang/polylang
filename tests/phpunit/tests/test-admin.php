<?php
class Admin_Test extends PLL_UnitTestCase {

	protected $nav_menu;
	protected static $stylesheet;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::$stylesheet = get_option( 'stylesheet' ); // save default theme
	}

	public function set_up() {
		parent::set_up();

		wp_set_current_user( 1 ); // Set a user to pass current_user_can tests
	}

	public function tear_down() {
		parent::tear_down();

		remove_action( 'customize_register', array( $this, 'whatever' ) );

		switch_theme( self::$stylesheet );
	}

	public function test_admin_bar_menu() {
		global $wp_admin_bar;
		add_filter( 'show_admin_bar', '__return_true' ); // Make sure to show admin bar.

		$this->go_to( home_url( '/wp-admin/edit.php' ) );
		$links_model = self::$model->get_links_model();
		$pll_admin = new PLL_Admin( $links_model );
		$pll_admin->init();

		_wp_admin_bar_init();
		do_action_ref_array( 'admin_bar_menu', array( &$wp_admin_bar ) );

		$languages = $wp_admin_bar->get_node( 'languages' );
		$this->assertEmpty( $languages->parent );
		$this->assertEquals( '/wp-admin/edit.php?lang=all', $languages->href );

		$en = $wp_admin_bar->get_node( 'en' );
		$this->assertEquals( 'languages', $en->parent );
		$this->assertEquals( '/wp-admin/edit.php?lang=en', $en->href );

		$fr = $wp_admin_bar->get_node( 'fr' );
		$this->assertEquals( 'languages', $fr->parent );
		$this->assertEquals( '/wp-admin/edit.php?lang=fr', $fr->href );
	}

	public function test_remove_customize_submenu_with_block_base_theme() {
		$block_base_theme = wp_get_theme( 'twentytwentytwo' );
		if ( ! $block_base_theme->exists() ) {
			self::markTestSkipped( 'This test requires twenty twenty two' );
		}

		global $submenu;
		switch_theme( 'twentytwentytwo' );

		global $_wp_theme_features;
		unset( $_wp_theme_features['widgets'] );


		$links_model = self::$model->get_links_model();
		$pll_admin = new PLL_Admin( $links_model );
		$this->nav_menu = new PLL_Nav_Menu( $pll_admin ); // For auto added pages to menu.

		self::require_wp_menus();

		$this->assertNotContains( 'customize', array_merge( ...array_values( $submenu['themes.php'] ) ) );
	}

	public function test_remove_customize_submenu_with_non_block_base_theme() {
		global $submenu;

		global $_wp_theme_features;
		unset( $_wp_theme_features['widgets'] );

		$links_model = self::$model->get_links_model();
		$pll_admin = new PLL_Admin( $links_model );
		$this->nav_menu = new PLL_Nav_Menu( $pll_admin ); // For auto added pages to menu.

		self::require_wp_menus();

		$this->assertContains( 'customize', array_merge( ...array_values( $submenu['themes.php'] ) ) );
	}

	public function test_do_not_remove_customize_submenu_with_block_base_theme_if_a_plugin_use_it() {
		$block_base_theme = wp_get_theme( 'twentytwentytwo' );
		if ( ! $block_base_theme->exists() ) {
			self::markTestSkipped( 'This test requires twenty twenty two' );
		}

		global $submenu;
		switch_theme( 'twentytwentytwo' );

		global $_wp_theme_features;
		unset( $_wp_theme_features['widgets'] );

		$links_model = self::$model->get_links_model();
		$pll_admin = new PLL_Admin( $links_model );
		$this->nav_menu = new PLL_Nav_Menu( $pll_admin ); // For auto added pages to menu.

		add_action( 'customize_register', array( $this, 'whatever' ) );

		self::require_wp_menus();

		$this->assertContains( 'customize', array_merge( ...array_values( $submenu['themes.php'] ) ) );
	}
}
