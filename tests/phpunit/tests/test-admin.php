<?php
class Admin_Test extends PLL_UnitTestCase {
	/**
	 * @param PLL_UnitTest_Factory $factory
	 * @return void
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	public function set_up() {
		parent::set_up();

		wp_set_current_user( 1 ); // Set a user to pass current_user_can tests
	}

	public function tear_down() {
		parent::tear_down();

		remove_action( 'customize_register', array( $this, 'whatever' ) );

		switch_theme( 'default' ); // Restore the default theme.
	}

	public function test_admin_bar_menu_should_show() {
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

	public function test_admin_bar_menu_with_filtered_language() {
		global $wp_admin_bar;
		add_filter( 'show_admin_bar', '__return_true' ); // Make sure to show admin bar.

		$this->go_to( home_url( '/wp-admin/edit.php' ) );
		$links_model = self::$model->get_links_model();
		$pll_admin   = new PLL_Admin( $links_model );
		$pll_admin->init();
		$pll_admin->filter_lang = self::$model->get_language( 'fr' );
		$pll_admin->pref_lang   = $pll_admin->filter_lang;

		_wp_admin_bar_init();
		do_action_ref_array( 'admin_bar_menu', array( &$wp_admin_bar ) );

		// 'fr' is selected, so it should not appear in the dropdown.
		$fr = $wp_admin_bar->get_node( 'fr' );
		$this->assertNull( $fr );

		// 'all' and 'en' should appear as dropdown items.
		$all = $wp_admin_bar->get_node( 'all' );
		$this->assertSame( 'languages', $all->parent );
		$this->assertSame( '/wp-admin/edit.php?lang=all', $all->href );

		$en = $wp_admin_bar->get_node( 'en' );
		$this->assertSame( 'languages', $en->parent );
		$this->assertSame( '/wp-admin/edit.php?lang=en', $en->href );
	}

	public function test_admin_bar_with_filtered_category() {
		global $wp_admin_bar;

		$cats = self::factory()->category->create_translated(
			array( 'name' => 'My cat', 'lang' => 'en' ),
			array( 'name' => 'Mon chat', 'lang' => 'fr' )
		);

		$posts = self::factory()->post->create_translated(
			array( 'post_category' => array( $cats['en'] ), 'lang' => 'en' ),
			array( 'post_category' => array( $cats['fr'] ), 'lang' => 'fr' )
		);


		add_filter( 'show_admin_bar', '__return_true' ); // Make sure to show admin bar.

		$this->go_to( home_url( "/wp-admin/edit.php?s&post_status=all&post_type=post&action=-1&m=0&cat={$cats['fr']}&filter_action=Filter&paged=1" ) );
		$links_model = self::$model->get_links_model();
		$pll_admin   = new PLL_Admin( $links_model );
		$pll_admin->init();
		$pll_admin->filter_lang = self::$model->get_language( 'fr' );
		$pll_admin->pref_lang   = $pll_admin->filter_lang;

		$GLOBALS['pagenow'] = 'edit.php';

		_wp_admin_bar_init();
		do_action_ref_array( 'admin_bar_menu', array( &$wp_admin_bar ) );

		// 'fr' is selected, so it should not appear in the dropdown.
		$this->assertSame(
			esc_url( '/wp-admin/edit.php?s&post_status=all&post_type=post&action=-1&m=0&filter_action=Filter&lang=en&category_name=my-cat' ),
			$wp_admin_bar->get_node( 'en' )->href
		);
	}

	public function test_admin_bar_with_selected_category() {
		global $wp_admin_bar;

		$cats = self::factory()->category->create_translated(
			array( 'name' => 'My cat', 'lang' => 'en' ),
			array( 'name' => 'Mon chat', 'lang' => 'fr' )
		);

		$posts = self::factory()->post->create_translated(
			array( 'post_category' => array( $cats['en'] ), 'lang' => 'en' ),
			array( 'post_category' => array( $cats['fr'] ), 'lang' => 'fr' )
		);

		add_filter( 'show_admin_bar', '__return_true' ); // Make sure to show admin bar.

		$this->go_to( home_url( '/wp-admin/edit.php?category_name=mon-chat' ) );
		$links_model = self::$model->get_links_model();
		$pll_admin   = new PLL_Admin( $links_model );
		$pll_admin->init();
		$pll_admin->filter_lang = self::$model->get_language( 'fr' );
		$pll_admin->pref_lang   = $pll_admin->filter_lang;

		$GLOBALS['pagenow'] = 'edit.php';

		_wp_admin_bar_init();
		do_action_ref_array( 'admin_bar_menu', array( &$wp_admin_bar ) );

		// 'fr' is selected, so it should not appear in the dropdown.
		$fr = $wp_admin_bar->get_node( 'fr' );
		$this->assertNull( $fr );

		$en = $wp_admin_bar->get_node( 'en' );
		$this->assertSame( esc_url( '/wp-admin/edit.php?lang=en&category_name=my-cat' ), $en->href );
	}

	/**
	 * @testWith [ "post-new.php", "post-new.php?post_type=page" ]
	 *           [ "post.php", "post.php" ]
	 *           [ "site-editor.php", "site-editor.php" ]
	 *           [ "term.php", "term.php" ]
	 *
	 * @param string $pagenow The page now.
	 * @param string $url     The URL of the page.
	 * @return void
	 */
	public function test_admin_bar_menu_should_hide( $pagenow, $url ) {
		global $wp_admin_bar;
		add_filter( 'show_admin_bar', '__return_true' ); // Make sure to show admin bar.

		$this->go_to( admin_url( $url ) );
		$GLOBALS['pagenow'] = $pagenow;

		$links_model = self::$model->get_links_model();
		$pll_admin = new PLL_Admin( $links_model );
		$pll_admin->init();

		_wp_admin_bar_init();
		do_action_ref_array( 'admin_bar_menu', array( &$wp_admin_bar ) );

		$languages = $wp_admin_bar->get_node( 'languages' );
		$this->assertEmpty( $languages, "Languages admin bar menu should be hidden on $pagenow pages" );
	}

	public function test_remove_customize_submenu_with_block_base_theme() {
		global $submenu, $_wp_theme_features, $pagenow;

		$pagenow = 'index.php'; // Set $pagenow so `user_can_access_admin_page()` doesn't throw PHP deprecation notice with PHP 8.5.

		unset( $_wp_theme_features['widgets'] );

		switch_theme( 'block-theme' );

		$links_model         = self::$model->get_links_model();
		$pll_admin           = new PLL_Admin( $links_model );
		$pll_admin->nav_menu = new PLL_Nav_Menu( $pll_admin ); // For auto added pages to menu.

		self::require_wp_menus();

		$this->assertNotContains( 'customize', array_merge( ...array_values( $submenu['themes.php'] ) ) );
	}

	public function test_remove_customize_submenu_with_non_block_base_theme() {
		global $submenu, $_wp_theme_features;
		unset( $_wp_theme_features['widgets'] );

		$links_model         = self::$model->get_links_model();
		$pll_admin           = new PLL_Admin( $links_model );
		$pll_admin->nav_menu = new PLL_Nav_Menu( $pll_admin ); // For auto added pages to menu.

		self::require_wp_menus();

		$this->assertContains( 'customize', array_merge( ...array_values( $submenu['themes.php'] ) ) );
	}

	public function test_do_not_remove_customize_submenu_with_block_base_theme_if_a_plugin_use_it() {
		global $submenu, $_wp_theme_features;
		unset( $_wp_theme_features['widgets'] );

		switch_theme( 'block-theme' );

		$links_model         = self::$model->get_links_model();
		$pll_admin           = new PLL_Admin( $links_model );
		$pll_admin->nav_menu = new PLL_Nav_Menu( $pll_admin ); // For auto added pages to menu.

		add_action( 'customize_register', array( $this, 'whatever' ) );

		self::require_wp_menus();

		$this->assertContains( 'customize', array_merge( ...array_values( $submenu['themes.php'] ) ) );
	}
}
