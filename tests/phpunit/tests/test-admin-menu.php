<?php

class Admin_Menu_Test extends PLL_UnitTestCase {
	public function set_up() {
		parent::set_up();

		$options         = self::create_options();
		$model           = new PLL_Model( $options );
		$links_model     = $model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );

		$GLOBALS['pagenow'] = 'admin.php';
		wp_set_current_user( 1 );
		self::require_wp_menus( false );
	}

	public function test_add_menus_sets_admin_page_hooks_and_submenu_pages() {
		global $admin_page_hooks, $submenu;

		$this->pll_admin->add_menus();

		$this->assertArrayHasKey( 'mlang', $admin_page_hooks );
		$this->assertSame( 'languages', $admin_page_hooks['mlang'] );

		$this->assertIsArray( $submenu );
		$this->assertArrayHasKey( 'mlang', $submenu );

		$pll_submenu = $submenu['mlang'];

		$this->assertNotEmpty( $pll_submenu );

		$pages = array_column( $pll_submenu, 2 );

		$this->assertContains( 'mlang', $pages );
		$this->assertContains( 'mlang_settings', $pages );
	}

	public function test_add_menus_adds_correct_admin_body_class_on_first_tab_screen() {
		$this->pll_admin->add_menus();

		set_current_screen( 'toplevel_page_mlang' );

		do_action( 'admin_head-toplevel_page_mlang' );

		$body_classes = apply_filters( 'admin_body_class', 'existing-class' );

		$this->assertStringContainsString( 'existing-class', $body_classes );
		$this->assertStringContainsString( ' languages_page_mlang ', " {$body_classes} " ); // Purposely using a space to avoid false positives.
	}

	public function test_current_screen_callback_sets_id_and_base_to_languages_screen() {
		$this->pll_admin->add_menus();

		$screen = (object) array(
			'id'   => 'toplevel_page_mlang',
			'base' => 'toplevel_page_mlang',
		);

		do_action( 'current_screen', $screen );

		$this->assertSame( 'languages_page_mlang', $screen->id );
		$this->assertSame( 'languages_page_mlang', $screen->base );
	}

	public function test_add_menus_current_screen_callback_returns_early_when_screen_id_is_not_first_tab() {
		$this->pll_admin->add_menus();

		$screen = (object) array(
			'id'   => 'languages_page_mlang_strings',
			'base' => 'languages_page_mlang_strings',
		);

		do_action( 'current_screen', $screen );

		$this->assertSame( 'languages_page_mlang_strings', $screen->id );
		$this->assertSame( 'languages_page_mlang_strings', $screen->base );
	}
}
