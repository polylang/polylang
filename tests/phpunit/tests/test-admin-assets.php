<?php
class Admin_Assets_Test extends PLL_Assets_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' ); // We need at least one language to "activate" Polylang on all screens.
	}

	public function test_scripts_in_post_list_table() {
		$GLOBALS['hook_suffix'] = 'edit.php';
		set_current_screen();

		$scripts = array(
			'footer' => array(
				'pll_ajax_backend',
				'pll_post-js',
			),
			'header' => array(
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
			),
			'header' => array(
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
				'pll_classic-editor-js',
			),
			'header' => array(
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
				'pll_block-editor-js',
			),
			'header' => array(
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
			),
			'header' => array(
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
				'pll_post-js',
			),
			'header' => array(
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
				'pll_term-js',
			),
			'header' => array(
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
			),
			'header' => array(
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
				'pll_term-js',
			),
			'header' => array(
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
			),
			'header' => array(
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
			),
			'header' => array(
				'pll_user-js',
				'polylang_admin-css',
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
			),
			'header' => array(
				'pll_widgets-js',
				'polylang_admin-css',
			),
		);
		$this->_test_scripts( $scripts );
	}
}
