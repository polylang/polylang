<?php
require POLYLANG_DIR . '/include/api.php';

class PLL_Assets_UnitTestCase extends PLL_UnitTestCase {
	/**
	 * The Polylang assets identifiers (those rendered by WordPress in HTML tags).
	 *
	 * @var array<string, array<int, string>>
	 */
	private $polylang_assets = array(
		'header' => array(
			'pll_user-js',
			'polylang_admin-css',
		),
		'footer' => array(
			'pll_ajax_backend',
			'pll_post-js',
			'pll_term-js',
			'pll_classic-editor-js',
			'pll_block-editor-js',
		),
	);

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
	protected function _test_scripts( $scripts ) {
		$links_model      = self::$model->get_links_model();
		$pll_admin        = new PLL_Admin( $links_model );
		$pll_admin->links = new PLL_Admin_Links( $pll_admin );
		$pll_admin->init();

		$GLOBALS['wp_styles']  = new WP_Styles();
		$GLOBALS['wp_scripts'] = new WP_Scripts();
		wp_default_scripts( $GLOBALS['wp_scripts'] );

		ob_start();
		// Based on what's done in wp-admin/admin-header.php
		do_action( 'admin_enqueue_scripts' );
		do_action( 'admin_print_styles' );
		do_action( 'admin_print_scripts' );
		$this->assert_scripts_are_enqueued_correctly( $scripts, ob_get_clean(), 'header' );

		ob_start();
		// Based on what's done in wp-admin/admin-footer.php
		do_action( 'admin_print_footer_scripts' );
		$this->assert_scripts_are_enqueued_correctly( $scripts, ob_get_clean(), 'footer' );
	}

	/**
	 * Asserts scripts are enqueued or not.
	 *
	 * @param string[] $scripts  The script names.
	 * @param string   $content  The content to look into.
	 * @param string   $position The position of the script. Used for more accurate error message.
	 * @return void
	 */
	protected function assert_scripts_are_enqueued_correctly( $scripts, $content, $position ) {
		$polylang_assets = $this->get_polylang_assets();

		if ( isset( $scripts[ $position ] ) ) {
			foreach ( $scripts[ $position ] as $script ) {
				$this->assertStringContainsString( $script, $content, "$script script is not enqueued in the $position as it should." );
				$polylang_assets[ $position ] = array_diff( $polylang_assets[ $position ], array( $script ) );
			}
		}

		foreach ( $polylang_assets[ $position ] as $script ) {
			$this->assertStringNotContainsString( $script, $content, "$script script is enqueued in the $position but it should not." );
		}
	}

	/**
	 * Getter for the Polylang scripts and stylesheets.
	 * Mainly here to be overloaded with other assets if needed.
	 *
	 * @return array
	 */
	protected function get_polylang_assets() {
		return $this->polylang_assets;
	}
}
