<?php

use WP_Syntex\Polylang\Switcher\Assets;
use WP_Syntex\Polylang\Widgets\Languages;

class Frontend_Assets_Test extends PLL_Assets_UnitTestCase {
	use PLL_Widgets_Trait;

	private const SIDEBAR_ID = 'sidebar-1';
	private const WIDGET_ID  = 'polylang-2';

	/**
	 * @var int
	 */
	private static $widget_index;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		// Ensure languages are not hidden by `hide_if_empty`.
		self::factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);

		self::$widget_index = (int) preg_replace( '/[^\d]/', '', self::WIDGET_ID );

		self::require_api();
	}

	public function set_up() {
		parent::set_up();

		// The widget must be registered after its settings are stored in the DB.
		unregister_widget( Languages::class );

		$links_model         = self::$model->get_links_model();
		$GLOBALS['polylang'] = new PLL_Frontend( $links_model );
		$GLOBALS['polylang']->init();
	}

	/**
	 * @return array
	 */
	protected function get_polylang_assets() {
		return array(
			'header' => array(
				'pll-language-switcher-css', // `css/build/frontend-switcher.css`.
			),
			'footer' => array(
				'pll-language-switcher-js', // `js/build/frontend-switcher.js`.
			),
		);
	}

	/**
	 * @testWith [{"layout":"select"}]
	 *           [{"layout":"dropdown"}]
	 *
	 * @param array $args Arguments passed to `pll_the_languages()`.
	 * @return void
	 */
	public function test_assets_enqueued_for_select_and_dropdown( $args ) {
		$assets = array(
			'footer' => array(
				'pll-language-switcher-js',
			),
		);
		$this->_test_pll_the_languages_assets( $args, $assets );
	}

	/**
	 * @testWith [{"layout":"horizontal"}]
	 *           [{"layout":"vertical"}]
	 *           [{"dropdown":0,"show_wrapper":false}]
	 *
	 * @param array $args Arguments passed to `pll_the_languages()`.
	 * @return void
	 */
	public function test_assets_not_enqueued_for_nav( $args ) {
		if ( isset( $args['dropdown'] ) ) {
			$this->setExpectedDeprecated( 'pll_the_languages()' );
		}

		$this->_test_pll_the_languages_assets( $args, array() );
	}

	/**
	 * @testWith ["select"]
	 *           ["dropdown"]
	 *
	 * @param string $layout Widget layout.
	 * @return void
	 */
	public function test_widget_assets_enqueued_for_select_and_dropdown( $layout ) {
		$assets = array(
			'header' => array(
				'pll-language-switcher-css',
			),
			'footer' => array(
				'pll-language-switcher-js',
			),
		);
		$this->_test_widget_assets( $layout, $assets );
	}

	/**
	 * @testWith ["horizontal"]
	 *           ["vertical"]
	 *
	 * @param string $layout Widget layout.
	 * @return void
	 */
	public function test_widget_assets_enqueued_for_nav( $layout ) {
		$assets = array(
			'header' => array(
				'pll-language-switcher-css',
			),
		);
		$this->_test_widget_assets( $layout, $assets );
	}

	/**
	 * Tests assets enqueued by `pll_the_languages()`.
	 *
	 * @param array $args   Arguments passed to `pll_the_languages()`.
	 * @param array $assets Expected assets per position.
	 * @return void
	 */
	protected function _test_pll_the_languages_assets( $args, $assets ) {
		$this->reset_asset_globals();
		$this->assert_frontend_assets_are_not_enqueued();

		pll_the_languages(
			array_merge(
				array(
					'echo' => 0,
				),
				$args
			)
		);

		$this->assert_frontend_assets_are_enqueued_correctly( $assets );
	}

	/**
	 * Tests assets enqueued by the language switcher widget.
	 *
	 * @param string $layout Widget layout.
	 * @param array  $assets Expected assets per position.
	 * @return void
	 */
	protected function _test_widget_assets( $layout, $assets ) {
		$this->setup_active_widget( $layout );
		$this->reset_asset_globals();
		$this->assert_frontend_assets_are_not_enqueued();

		do_action( 'wp_enqueue_scripts' );

		ob_start();
		dynamic_sidebar( self::SIDEBAR_ID );
		ob_end_clean();

		$this->assert_frontend_assets_are_enqueued_correctly( $assets );
	}

	/**
	 * Resets WordPress style and script globals.
	 *
	 * @return void
	 */
	protected function reset_asset_globals() {
		$GLOBALS['wp_styles']  = new WP_Styles();
		$GLOBALS['wp_scripts'] = new WP_Scripts();
		wp_default_scripts( $GLOBALS['wp_scripts'] );
	}

	/**
	 * Asserts frontend switcher scripts and styles are printed as expected.
	 *
	 * @param array $assets Expected assets per position.
	 * @return void
	 */
	protected function assert_frontend_assets_are_enqueued_correctly( $assets ) {
		ob_start();
		wp_print_styles();
		wp_print_head_scripts();
		$this->assert_scripts_are_enqueued_correctly( $assets, ob_get_clean(), 'header' );

		ob_start();
		wp_print_footer_scripts();
		$this->assert_scripts_are_enqueued_correctly( $assets, ob_get_clean(), 'footer' );
	}

	/**
	 * Asserts frontend switcher scripts and styles are not enqueued yet.
	 *
	 * @return void
	 */
	protected function assert_frontend_assets_are_not_enqueued() {
		$this->assertFalse(
			wp_style_is( Assets::FRONTEND_ASSET_HANDLE, 'enqueued' ),
			'Frontend switcher CSS should not be enqueued yet.'
		);
		$this->assertFalse(
			wp_script_is( Assets::FRONTEND_ASSET_HANDLE, 'enqueued' ),
			'Frontend switcher JS should not be enqueued yet.'
		);

		$this->assert_frontend_assets_are_enqueued_correctly( array() );
	}

	/**
	 * Stores widget settings, registers a sidebar, activates the widget, and registers it.
	 *
	 * @param string $layout Widget layout.
	 * @return void
	 */
	private function setup_active_widget( $layout ) {
		update_option(
			'widget_polylang',
			array(
				self::$widget_index => array(
					'layout' => $layout,
					'title'  => 'Language switcher',
				),
				'_multiwidget'      => 1,
			)
		);

		register_sidebar(
			array(
				'name' => 'Sidebar',
				'id'   => self::SIDEBAR_ID,
			)
		);
		wp_assign_widget_to_sidebar( self::WIDGET_ID, self::SIDEBAR_ID );

		do_action( 'widgets_init' );
	}
}
