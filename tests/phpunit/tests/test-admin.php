<?php
require POLYLANG_DIR . '/include/api.php';

class Admin_Test extends PLL_UnitTestCase {
	protected static $editor;
	protected static $stylesheet;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::$editor = $factory->user->create( array( 'role' => 'administrator' ) );

		self::$stylesheet = get_option( 'stylesheet' ); // save default theme
	}

	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$editor ); // Set a user to pass current_user_can tests
	}

	public function tear_down() {
		parent::tear_down();

		remove_action( 'customize_register', array( $this, 'whatever' ) );

		switch_theme( self::$stylesheet );
	}

	public function test_admin_bar_menu() {
		global $wp_admin_bar;
		add_filter( 'show_admin_bar', '__return_true' ); // Make sure to show admin bar

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

	/**
	 * Tests that given scripts or stylesheets are well enqueued.
	 *
	 * @param array $scripts {
	 *      @type string   $key   Whether the assets is enqueued in the header or in the footer. Accepts 'header' or 'footer'.
	 *      @type string[] $value The assets names to test against the given position.
	 * }
	 *
	 * @return void
	 */
	protected function _test_scripts( $scripts ) {
		/**
		 * Array keys contains the scripts and stylesheets enqueued,
		 * Array values tells if the assets should be search with source, false if with script name.
		 */
		$not_included_head_scripts   = array(
			'user' => false,
		);
		$not_included_footer_scripts = array(
			'pll_ajax_backend'   => true,
			'polylang_admin-css' => true,
			'post'               => false,
			'term'               => false,
			'classic-editor'     => false,
			'block-editor'       => false,
		);

		$links_model      = self::$model->get_links_model();
		$pll_admin        = new PLL_Admin( $links_model );
		$pll_admin->links = new PLL_Admin_Links( $pll_admin );
		$GLOBALS['wp_styles'] = new WP_Styles();
		$GLOBALS['wp_scripts'] = new WP_Scripts();
		wp_default_scripts( $GLOBALS['wp_scripts'] );

		do_action( 'admin_enqueue_scripts' );

		ob_start();
		do_action( 'admin_print_scripts' );
		$head = ob_get_clean();

		ob_start();
		do_action( 'admin_print_footer_scripts' );
		$footer = ob_get_clean();

		if ( isset( $scripts['header'] ) ) {
			foreach ( $scripts['header'] as $script ) {
				$is_name = isset( $not_included_head_scripts[ $script ] ) && $not_included_head_scripts[ $script ];
				$this->assert_script_is_enqueued( $script, $head, $is_name, 'header' );
				unset( $not_included_head_scripts[ $script ] );
			}
		}

		foreach ( $not_included_head_scripts as $script => $is_name ) {
			$this->assert_script_is_not_enqueued( $script, $head, $is_name, 'header' );
		}

		if ( isset( $scripts['footer'] ) ) {
			foreach ( $scripts['footer'] as $script ) {
				$is_name = isset( $not_included_footer_scripts[ $script ] ) && $not_included_footer_scripts[ $script ];
				$this->assert_script_is_enqueued( $script, $footer, $is_name, 'footer' );
				unset( $not_included_footer_scripts[ $script ] );
			}
		}

		foreach ( $not_included_footer_scripts as $script => $is_name ) {
			$this->assert_script_is_not_enqueued( $script, $footer, $is_name, 'footer' );
		}
	}

	/**
	 * Asserts a script is not enqueued.
	 *
	 * @param string $script   The script name or source.
	 * @param string $content  The content to look into.
	 * @param bool   $is_name  Whether the script is given with name or source. True for name.
	 * @param string $position The position of the script. Used for more accurate error message.
	 *
	 * @return void
	 */
	protected function assert_script_is_not_enqueued( $script, $content, $is_name, $position ) {
		if ( $is_name ) {
			// The current script is a name.
			$test = strpos( $content, $script );
		} else {
			// The current script is a source.
			$test = strpos( $content, plugins_url( "/js/build/$script.min.js", POLYLANG_FILE ) );
		}
		$this->assertFalse( $test, "$script script is enqueued in the $position but it should not." );
	}

	/**
	 * Asserts a script is enqueued.
	 *
	 * @param string $script   The script name or source.
	 * @param string $content  The content to look into.
	 * @param bool   $is_name  Whether the script is given with name or source. True for name.
	 * @param string $position The position of the script. Used for more accurate error message.
	 *
	 * @return void
	 */
	protected function assert_script_is_enqueued( $script, $content, $is_name, $position ) {
		if ( $is_name ) {
			// The current script is a name.
			$test = strpos( $content, $script );
		} else {
			// The current script is a source.
			$test = strpos( $content, plugins_url( "/js/build/$script.min.js", POLYLANG_FILE ) );
		}
		$this->assertIsInt( $test, "$script script is not enqueued in the $position as it should." );
	}

	public function test_scripts_in_post_list_table() {
		$GLOBALS['hook_suffix'] = 'edit.php';
		set_current_screen();

		$scripts = array(
			'footer' => array(
				'pll_ajax_backend',
				'post',
				'polylang_admin-css',
			),
		);
		$this->_test_scripts( $scripts );
	}

	public function test_scripts_in_untranslated_cpt_list_table() {
		$GLOBALS['hook_suffix'] = 'edit.php';
		$_REQUEST['post_type'] = 'cpt';
		register_post_type( 'cpt' );
		set_current_screen();

		$scripts = array(
			'footer' => array(
				'pll_ajax_backend',
				'polylang_admin-css',
			),
		);
		$this->_test_scripts( $scripts );
	}

	public function test_scripts_in_edit_post_classic_editor() {
		$GLOBALS['hook_suffix'] = 'post.php';
		set_current_screen();

		global $current_screen;
		$current_screen->is_block_editor = false;

		$scripts = array(
			'footer' => array(
				'pll_ajax_backend',
				'classic-editor',
				'polylang_admin-css',
			),
		);
		$this->_test_scripts( $scripts );
	}

	public function test_scripts_in_edit_post_block_editor() {
		$GLOBALS['hook_suffix'] = 'post.php';
		set_current_screen();

		$scripts = array(
			'footer' => array(
				'pll_ajax_backend',
				'block-editor',
				'polylang_admin-css',
			),
		);
		$this->_test_scripts( $scripts );
	}

	public function test_scripts_in_edit_untranslated_cpt() {
		$GLOBALS['hook_suffix'] = 'post.php';
		$_REQUEST['post_type'] = 'cpt';
		register_post_type( 'cpt' );
		set_current_screen();

		$scripts = array(
			'footer' => array(
				'pll_ajax_backend',
				'polylang_admin-css',
			),
		);
		$this->_test_scripts( $scripts );
	}


	public function test_scripts_in_media_list_table() {
		$GLOBALS['hook_suffix'] = 'upload.php';
		set_current_screen();

		$scripts = array(
			'footer' => array(
				'pll_ajax_backend',
				'post',
				'polylang_admin-css',
			),
		);
		$this->_test_scripts( $scripts );
	}

	public function test_scripts_in_terms_list_table() {
		$GLOBALS['hook_suffix'] = 'edit-tags.php';
		set_current_screen();

		$scripts = array(
			'footer' => array(
				'pll_ajax_backend',
				'term',
				'polylang_admin-css',
			),
		);
		$this->_test_scripts( $scripts );
	}

	public function test_scripts_in_untranslated_custom_tax_list_table() {
		$GLOBALS['hook_suffix'] = 'edit-tags.php';
		$_REQUEST['taxonomy'] = 'tax';
		register_taxonomy( 'tax', 'post' );
		set_current_screen();

		$scripts = array(
			'footer' => array(
				'pll_ajax_backend',
				'polylang_admin-css',
			),
		);
		$this->_test_scripts( $scripts );
	}

	public function test_scripts_in_edit_term() {
		$GLOBALS['hook_suffix'] = 'term.php';
		set_current_screen();

		$scripts = array(
			'footer' => array(
				'pll_ajax_backend',
				'term',
				'polylang_admin-css',
			),
		);
		$this->_test_scripts( $scripts );
	}

	public function test_scripts_in_edit_unstranslated_custom_tax() {
		$GLOBALS['hook_suffix'] = 'term.php';
		$_REQUEST['taxonomy'] = 'tax';
		register_taxonomy( 'tax', 'post' );
		set_current_screen();

		$scripts = array(
			'footer' => array(
				'pll_ajax_backend',
				'polylang_admin-css',
			),
		);
		$this->_test_scripts( $scripts );
	}


	public function test_scripts_in_user_profile() {
		$GLOBALS['hook_suffix'] = 'profile.php';
		set_current_screen();

		$scripts = array(
			'footer' => array(
				'pll_ajax_backend',
				'polylang_admin-css',
			),
			'header' => array(
				'user',
			),
		);
		$this->_test_scripts( $scripts );
	}

	public function test_scripts_in_edit_widgets() {
		$GLOBALS['hook_suffix'] = 'widgets.php';
		set_current_screen();

		$scripts = array(
			'footer' => array(
				'pll_ajax_backend',
				'polylang_admin-css',
			),
			'header' => array(
				'widgets',
			),
		);
		$this->_test_scripts( $scripts );
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
