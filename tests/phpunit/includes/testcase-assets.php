<?php
require POLYLANG_DIR . '/include/api.php';

class PLL_Assets_UnitTestCase extends PLL_UnitTestCase {
	protected static $editor;
	protected static $stylesheet;
	protected $polylang_assets = array(
		'header' => array(
			'user' => 'source',
			'polylang_admin-css' => 'name',
		),
		'footer' => array(
			'pll_ajax_backend'   => 'name',
			'post'               => 'source',
			'term'               => 'source',
			'classic-editor'     => 'source',
			'block-editor'       => 'source',
		),
	);

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

	/**
	 * Tests that given scripts or stylesheets are well enqueued.
	 * And tests that remaining Polylang files are not enqueued.
	 *
	 * @param array $scripts {
	 *      @type string   $key   Whether the assets is enqueued in the header or in the footer. Accepts 'header' or 'footer'.
	 *      @type string[] $value The assets names to test against the given position.
	 * }
	 *
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

		do_action( 'admin_enqueue_scripts' );

		ob_start();
		// Based on what's done in wp-admin/admin-header.php
		do_action( 'admin_print_styles' );
		do_action( 'admin_print_scripts' );
		$head = ob_get_clean();

		ob_start();
		// Based on what's done in wp-admin/admin-footer.php
		do_action( 'admin_print_footer_scripts' );
		$footer = ob_get_clean();

		$polylang_assets = $this->get_polylang_assets();

		if ( isset( $scripts['header'] ) ) {
			foreach ( $scripts['header'] as $script ) {
				$is_name = isset( $polylang_assets['header'][ $script ] ) && 'name' === $polylang_assets['header'][ $script ];
				$this->assert_script_is_enqueued( $script, $head, $is_name, 'header' );
				unset( $polylang_assets['header'][ $script ] );
			}
		}

		foreach ( $polylang_assets['header'] as $script => $type ) {
			$is_name = 'name' === $type;
			$this->assert_script_is_not_enqueued( $script, $head, $is_name, 'header' );
		}

		if ( isset( $scripts['footer'] ) ) {
			foreach ( $scripts['footer'] as $script ) {
				$is_name = isset( $polylang_assets['footer'][ $script ] ) && 'name' === $polylang_assets['footer'][ $script ];
				$this->assert_script_is_enqueued( $script, $footer, $is_name, 'footer' );
				unset( $polylang_assets['footer'][ $script ] );
			}
		}

		foreach ( $polylang_assets['footer'] as $script => $type ) {
			$is_name = 'name' === $type;
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
	private function assert_script_is_not_enqueued( $script, $content, $is_name, $position ) {
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
	private function assert_script_is_enqueued( $script, $content, $is_name, $position ) {
		if ( $is_name ) {
			// The current script is a name.
			$test = strpos( $content, $script );
		} else {
			// The current script is a source.
			$test = strpos( $content, plugins_url( "/js/build/$script.min.js", POLYLANG_FILE ) );
		}
		$this->assertIsInt( $test, "$script script is not enqueued in the $position as it should." );
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
