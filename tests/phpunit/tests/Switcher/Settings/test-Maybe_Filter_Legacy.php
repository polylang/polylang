<?php

namespace WP_Syntex\Polylang\Tests\Switcher\Settings;

use Mockery;
use PLL_UnitTestCase;
use WP_Syntex\Polylang\Switcher\Settings\Menu as Menu_Settings;

class Maybe_Filter_Legacy_Test extends PLL_UnitTestCase {
	public function tear_down() {
		Mockery::close();
		parent::tear_down();
	}

	/**
	 * Tests that the filter `pll_the_languages_args` receives the right legacy values, converted from non-legacy values (see `Switcher\Settings\Menu::convert_to_legacy()`).
	 * Tests that the final values are converted back to their original values (see `Switcher\Settings\Menu::convert_from_legacy()`).
	 */
	public function test_filtered_menu_new_args(): void {
		$this->setExpectedDeprecated( 'pll_the_languages_args' );
		$fired = false;
		$args  = array(
			'layout'       => 'dropdown',
			'hide_current' => true,
		);
		add_filter(
			'pll_the_languages_args',
			function ( $args ) use ( &$fired ) {
				$this->assertIsArray( $args );
				$this->assertArrayHasKey( 'dropdown', $args, 'The `dropdown` key should be present.' );
				$this->assertSame( 1, $args['dropdown'], 'The layout `dropdown` should be converted into `dropdown = 1`.' );
				$this->assertArrayHasKey( 'layout', $args, 'The `layout` key should be present.' );
				$this->assertSame( 'dropdown', $args['layout'], 'The layout should be `dropdown`.' );
				$this->assertArrayHasKey( 'hide_current', $args, 'The `hide_current` key should be present.' );
				$this->assertSame( 1, $args['hide_current'], 'The value of the `hide_current` key should be converted into an integer.' );
				$fired = true;
				return $args;
			}
		);
		$settings = new Menu_Settings( $args );
		$this->assertTrue( $fired, 'The filter `pll_the_languages_args` should have been fired.' );
		$this->assertSame( 'dropdown', $settings->layout, 'The layout should be back to `dropdown`.' );
		$this->assertTrue( $settings->hide_current, 'The value of the `hide_current` key should be back to a boolean.' );
	}

	/**
	 * Tests that the filter `pll_the_languages_args` receives the right legacy values, converted from non-legacy values.
	 * Tests that the final values are converted into the right values after being changed in the filter.
	 */
	public function test_changed_menu_new_args(): void {
		$this->setExpectedDeprecated( 'pll_the_languages_args' );
		$fired = false;
		$args  = array(
			'layout' => 'dropdown',
		);
		add_filter(
			'pll_the_languages_args',
			function ( $args ) use ( &$fired ) {
				$this->assertIsArray( $args );
				$this->assertArrayHasKey( 'dropdown', $args, 'The `dropdown` key should be present.' );
				$this->assertSame( 1, $args['dropdown'], 'The layout `dropdown` should be converted into `dropdown = 1`.' );
				$args['dropdown'] = 0;
				$fired = true;
				return $args;
			}
		);
		$settings = new Menu_Settings( $args );
		$this->assertTrue( $fired, 'The filter `pll_the_languages_args` should have been fired.' );
		$this->assertSame( 'horizontal', $settings->layout, 'The layout should have been changed to `horizontal`.' );
	}

	/**
	 * Tests that the filter `pll_the_languages_args` receives the right legacy values.
	 * Tests that the final values are converted to the right non-legacy values.
	 */
	public function test_filtered_menu_legacy_args(): void {
		$this->setExpectedDeprecated( 'pll_the_languages_args' );
		$fired = false;
		$args  = array(
			'dropdown'     => 1,
			'hide_current' => 1,
		);
		add_filter(
			'pll_the_languages_args',
			function ( $args ) use ( &$fired ) {
				$this->assertIsArray( $args );
				$this->assertArrayHasKey( 'dropdown', $args, 'The `dropdown` key should be present.' );
				$this->assertSame( 1, $args['dropdown'], 'The layout `dropdown` should be converted into `dropdown = 1`.' );
				$this->assertArrayNotHasKey( 'layout', $args, 'The `layout` key should not be present.' );
				$this->assertArrayHasKey( 'hide_current', $args, 'The `hide_current` key should be present.' );
				$this->assertSame( 1, $args['hide_current'], 'The value of the `hide_current` key should be converted into an integer.' );
				$fired = true;
				return $args;
			}
		);
		$settings = new Menu_Settings( $args );
		$this->assertTrue( $fired, 'The filter `pll_the_languages_args` should have been fired.' );
		$this->assertSame( 'dropdown', $settings->layout, 'The layout should be converted into `dropdown`.' );
		$this->assertTrue( $settings->hide_current, 'The value of the `hide_current` key should be converted into a boolean.' );
	}

	/**
	 * Tests that the filter `pll_the_languages_args` is not fired.
	 * Tests that the method `convert_to_legacy()` is not called.
	 */
	public function test_conversion_to_legacy_does_not_run(): void {
		$args = array(
			'dropdown' => 1,
		);
		$mock = Mockery::mock( Menu_Settings::class, array( $args ) )->makePartial();
		$mock->shouldAllowMockingProtectedMethods();
		$mock->shouldNotReceive( 'convert_to_legacy' );

		$settings = new $mock( $args );
		$this->assertFalse( (bool) did_filter( 'pll_the_languages_args' ), 'The filter `pll_the_languages_args` should not have been fired.' );
		$this->assertSame( 'dropdown', $settings->layout, 'The layout should be converted into `dropdown`.' );
	}

	/**
	 * Tests that unsupported layouts are converted to supported ones (see `Switcher\Settings\Menu::validate()` ).
	 *
	 * @dataProvider menu_unsupported_layout_provider
	 *
	 * @param string $layout   Layout to use.
	 * @param string $expected Expected layout.
	 * @return void
	 */
	public function test_menu_unsupported_layout( string $layout, string $expected ): void {
		$args = array(
			'layout' => $layout,
		);
		$settings = new Menu_Settings( $args );
		$this->assertSame( $expected, $settings->layout, "The layout should be converted into `$expected`." );
	}

	public function menu_unsupported_layout_provider(): array {
		return array(
			'select layout'   => array(
				'layout'   => 'select',
				'expected' => 'dropdown',
			),
			'vertical layout' => array(
				'layout'   => 'vertical',
				'expected' => 'horizontal',
			),
			'unknown layout'  => array(
				'layout'   => 'trucmuche',
				'expected' => 'horizontal',
			),
		);
	}
}
