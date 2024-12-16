<?php

namespace WP_Syntex\Polylang\Tests\Integration\Options\Options;

use PLL_UnitTestCase;

/**
 * Tests for `Options\Options->offsetUnset()`.
 *
 * @group options
 */
class OffsetUnset_Test extends PLL_UnitTestCase {
	/**
	 * Makes sure that `offsetUnset()` doesn't modify the option's value (because `offsetGet()` doesn't return a reference).
	 *
	 * @return void
	 */
	public function test_arrayaccess_should_not_unset_sub_value() {
		$nav_menus = array(
			'twentytwentyone' => array(
				'primary' => array(
					'fr' => 4,
					'en' => 7,
				),
				'footer'  => array(),
			),
		);
		$options = self::create_options(
			array(
				'nav_menus' => $nav_menus,
			)
		);

		$this->assertSame( $nav_menus, $options->get( 'nav_menus' ), 'The initial value should have the value we want to unset.' );

		/*
		 * This will break with PHPUnit 10.
		 * @see https://github.com/sebastianbergmann/phpunit/issues/5062
		 */
		$this->expectException( '\PHPUnit\Framework\Error\Notice' );
		$this->expectExceptionMessage( 'Indirect modification of overloaded element of WP_Syntex\Polylang\Options\Options has no effect' );

		unset( $options['nav_menus']['twentytwentyone']['primary']['fr'] );

		$this->assertSame( $nav_menus, $options->get( 'nav_menus' ), 'The option should not have been modified.' );
	}
}
