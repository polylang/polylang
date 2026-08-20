<?php

namespace WP_Syntex\Polylang\Tests\Assets;

use PLL_Admin;
use PLL_Admin_Links;
use PLL_Admin_Filters;
use PLL_UnitTest_Factory;

class Admin_Test extends TestCase {

	/**
	 * @param PLL_UnitTest_Factory $factory
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create( array( 'locale' => 'en_US' ) ); // We need at least one language to "activate" Polylang on all screens.
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
		$this->assert_admin_assets( $scripts );
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
		$this->assert_admin_assets( $scripts );
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
		$this->assert_admin_assets( $scripts );
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
		$this->assert_admin_assets( $scripts );
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
		$this->assert_admin_assets( $scripts );
	}


	public function test_scripts_in_media_list_table() {
		$GLOBALS['hook_suffix'] = 'upload.php';
		set_current_screen();

		$scripts = array(
			'footer' => array(
				'pll_ajax_backend',
				'pll_media-js',
			),
			'header' => array(
				'polylang_admin-css',
			),
		);
		$this->assert_admin_assets( $scripts );
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
		$this->assert_admin_assets( $scripts );
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
		$this->assert_admin_assets( $scripts );
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
		$this->assert_admin_assets( $scripts );
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
		$this->assert_admin_assets( $scripts );
	}


	public function test_scripts_in_user_profile() {
		$GLOBALS['hook_suffix'] = 'profile.php';
		set_current_screen();

		$scripts = array(
			'footer' => array(
				'pll_ajax_backend',
				'pll_user-js',
			),
			'header' => array(
				'polylang_admin-css',
			),
		);
		$this->assert_admin_assets( $scripts );
	}

	public function test_scripts_in_edit_widgets() {
		$GLOBALS['hook_suffix'] = 'widgets.php';
		set_current_screen();

		$scripts = array(
			'footer' => array(
				'pll_ajax_backend',
				'pll_widgets-js',
			),
			'header' => array(
				'polylang_admin-css',
			),
		);
		$this->assert_admin_assets( $scripts );
	}

	/**
	 * @return array
	 */
	protected function get_polylang_assets() {
		return array(
			'header' => array(
				'polylang_admin-css',
			),
			'footer' => array(
				'pll_ajax_backend',
				'pll_post-js',
				'pll_term-js',
				'pll_classic-editor-js',
				'pll_block-editor-js',
				'pll_user-js',
			),
		);
	}

	/**
	 * Tests that given scripts or stylesheets are well enqueued.
	 * And tests that remaining Polylang files are not enqueued.
	 *
	 * @param array $scripts {
	 *   @type string   $key   Whether the assets is enqueued in the header or in the footer. Accepts 'header' or 'footer'.
	 *   @type string[] $value The assets names to test against the given position.
	 * }
	 * @return void
	 */
	protected function assert_admin_assets( $scripts ) {
		$links_model        = self::$model->get_links_model();
		$pll_admin          = new PLL_Admin( $links_model );
		$pll_admin->links   = new PLL_Admin_Links( $pll_admin );
		$pll_admin->filters = new PLL_Admin_Filters( $pll_admin ); // Instance created on `wp_loaded`: see `PLL_Admin::init()`.

		$this->reset_asset_globals();

		ob_start();
		// Based on what's done in wp-admin/admin-header.php
		do_action( 'admin_enqueue_scripts' );
		do_action( 'admin_print_styles' );
		do_action( 'admin_print_scripts' );
		$this->assert_enqueued_assets( $scripts, ob_get_clean(), 'header' );

		if ( 'profile.php' === $GLOBALS['hook_suffix'] ) {
			do_action( 'personal_options', wp_get_current_user() );
		}

		ob_start();
		// Based on what's done in wp-admin/admin-footer.php
		do_action( 'admin_print_footer_scripts' );
		$this->assert_enqueued_assets( $scripts, ob_get_clean(), 'footer' );
	}
}
