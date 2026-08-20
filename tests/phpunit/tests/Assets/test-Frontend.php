<?php

namespace WP_Syntex\Polylang\Tests\Assets;

use PLL_Frontend;
use PLL_Widgets_Trait;
use PLL_UnitTest_Factory;
use WP_Syntex\Polylang\Widgets\Languages;

class Frontend_Test extends TestCase {
	use PLL_Widgets_Trait;

	private const SIDEBAR_ID = 'sidebar-1';
	private const WIDGET_ID  = 'polylang-2';

	/**
	 * @var int
	 */
	private static $widget_index;

	/**
	 * @param PLL_UnitTest_Factory $factory
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );

		// Ensure languages are not hidden by `hide_if_empty`.
		$factory->post->create_translated(
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

	protected function get_polylang_assets(): array {
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

		pll_the_languages(
			array_merge(
				array(
					'echo' => 0,
				),
				$args
			)
		);

		$this->assert_frontend_assets( $assets );
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

		do_action( 'wp_enqueue_scripts' );

		ob_start();
		dynamic_sidebar( self::SIDEBAR_ID );
		ob_end_clean();

		$this->assert_frontend_assets( $assets );
	}

	/**
	 * Resets WordPress style and script globals and asserts frontend switcher assets are not enqueued yet.
	 *
	 * @return void
	 */
	protected function reset_asset_globals() {
		parent::reset_asset_globals();

		$this->assert_frontend_assets( array() );
	}

	/**
	 * Asserts frontend switcher scripts and styles are printed as expected.
	 *
	 * @param array $assets Expected assets per position.
	 * @return void
	 */
	protected function assert_frontend_assets( $assets ) {
		ob_start();
		wp_print_styles();
		wp_print_head_scripts();
		$this->assert_enqueued_assets( $assets, ob_get_clean(), 'header' );

		ob_start();
		wp_print_footer_scripts();
		$this->assert_enqueued_assets( $assets, ob_get_clean(), 'footer' );
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
