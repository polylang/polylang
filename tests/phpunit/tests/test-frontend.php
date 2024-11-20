<?php


class Frontend_Test extends PLL_UnitTestCase {

	public function set_up() {
		parent::set_up();

		wp_set_current_user( 1 ); // Set a user to pass current_user_can tests.
	}

	public function tear_down() {
		parent::tear_down();

		switch_theme( 'default' );
	}

	public function test_remove_customize_admin_bar_with_block_base_theme() {
		global $wp_admin_bar;

		switch_theme( 'block-theme' );
		add_filter( 'show_admin_bar', '__return_true' ); // Make sure to show admin bar.

		$links_model = self::$model->get_links_model();
		$frontend = new PLL_Frontend( $links_model );
		$frontend->nav_menu = new PLL_Nav_Menu( $frontend ); // For auto added pages to menu.

		_wp_admin_bar_init();
		do_action_ref_array( 'admin_bar_menu', array( &$wp_admin_bar ) );
		do_action( 'wp_before_admin_bar_render' );

		$this->assertEquals( null, $wp_admin_bar->get_node( 'customize' ) );
	}

	public function test_remove_customize_admin_bar_with_non_block_base_theme() {
		global $wp_admin_bar;
		add_filter( 'show_admin_bar', '__return_true' ); // Make sure to show admin bar.

		$links_model = self::$model->get_links_model();
		$frontend = new PLL_Frontend( $links_model );
		$frontend->nav_menu = new PLL_Nav_Menu( $frontend ); // For auto added pages to menu.

		_wp_admin_bar_init();
		do_action_ref_array( 'admin_bar_menu', array( &$wp_admin_bar ) );

		remove_action( 'wp_before_admin_bar_render', 'wp_customize_support_script' ); // To avoid the script launch in test.

		do_action( 'wp_before_admin_bar_render' );

		$this->assertInstanceOf( stdClass::class, $wp_admin_bar->get_node( 'customize' ) );
	}
}
