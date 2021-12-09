<?php
require POLYLANG_DIR . '/include/api.php';

class Admin_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
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

	protected function _test_scripts( $scripts ) {
		$links_model = self::$model->get_links_model();
		$pll_admin = new PLL_Admin( $links_model );
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

		$test = strpos( $footer, 'pll_ajax_backend' );
		in_array( 'pll_ajax_backend', $scripts ) ? $this->assertNotFalse( $test ) : $this->assertFalse( $test );

		foreach ( array( 'post', 'term' ) as $key ) {
			$test = strpos( $footer, plugins_url( "/js/build/$key.min.js", POLYLANG_FILE ) );
			in_array( $key, $scripts ) ? $this->assertNotFalse( $test ) : $this->assertFalse( $test );
		}

		$test = strpos( $head, plugins_url( '/js/build/user.min.js', POLYLANG_FILE ) );
		in_array( 'user', $scripts ) ? $this->assertNotFalse( $test ) : $this->assertFalse( $test );

		$test = strpos( $footer, 'polylang_admin-css' );
		in_array( 'css', $scripts ) ? $this->assertNotFalse( $test ) : $this->assertFalse( $test );
	}

	public function test_scripts_in_post_list_table() {
		$GLOBALS['hook_suffix'] = 'edit.php';
		set_current_screen();

		$scripts = array( 'pll_ajax_backend', 'post', 'css' );
		$this->_test_scripts( $scripts );
	}

	public function test_scripts_in_untranslated_cpt_list_table() {
		$GLOBALS['hook_suffix'] = 'edit.php';
		$_REQUEST['post_type'] = 'cpt';
		register_post_type( 'cpt' );
		set_current_screen();

		$scripts = array( 'pll_ajax_backend', 'css' );
		$this->_test_scripts( $scripts );
	}

	public function test_scripts_in_edit_post() {
		$GLOBALS['hook_suffix'] = 'post.php';
		set_current_screen();

		$scripts = array( 'pll_ajax_backend', 'classic-editor', 'metabox', 'css' );
		$this->_test_scripts( $scripts );
	}

	public function test_scripts_in_edit_untranslated_cpt() {
		$GLOBALS['hook_suffix'] = 'post.php';
		$_REQUEST['post_type'] = 'cpt';
		register_post_type( 'cpt' );
		set_current_screen();

		$scripts = array( 'pll_ajax_backend', 'css' );
		$this->_test_scripts( $scripts );
	}


	public function test_scripts_in_media_list_table() {
		$GLOBALS['hook_suffix'] = 'upload.php';
		set_current_screen();

		$scripts = array( 'pll_ajax_backend', 'post', 'css' );
		$this->_test_scripts( $scripts );
	}

	public function test_scripts_in_terms_list_table() {
		$GLOBALS['hook_suffix'] = 'edit-tags.php';
		set_current_screen();

		$scripts = array( 'pll_ajax_backend', 'term', 'css' );
		$this->_test_scripts( $scripts );
	}

	public function test_scripts_in_untranslated_custom_tax_list_table() {
		$GLOBALS['hook_suffix'] = 'edit-tags.php';
		$_REQUEST['taxonomy'] = 'tax';
		register_taxonomy( 'tax', 'post' );
		set_current_screen();

		$scripts = array( 'pll_ajax_backend', 'css' );
		$this->_test_scripts( $scripts );
	}

	public function test_scripts_in_edit_term() {
		$GLOBALS['hook_suffix'] = 'term.php';
		set_current_screen();

		$scripts = array( 'pll_ajax_backend', 'term', 'css' );
		$this->_test_scripts( $scripts );
	}

	public function test_scripts_in_edit_unstranslated_custom_tax() {
		$GLOBALS['hook_suffix'] = 'term.php';
		$_REQUEST['taxonomy'] = 'tax';
		register_taxonomy( 'tax', 'post' );
		set_current_screen();

		$scripts = array( 'pll_ajax_backend', 'css' );
		$this->_test_scripts( $scripts );
	}


	public function test_scripts_in_user_profile() {
		$GLOBALS['hook_suffix'] = 'profile.php';
		set_current_screen();

		$scripts = array( 'pll_ajax_backend', 'user', 'css' );
		$this->_test_scripts( $scripts );
	}
}
