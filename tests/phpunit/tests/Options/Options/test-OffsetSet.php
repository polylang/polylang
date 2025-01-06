<?php

namespace WP_Syntex\Polylang\Tests\Integration\Options\Options;

use PLL_UnitTestCase;

/**
 * Tests for `Options\Options->offsetGet()`.
 *
 * @group options
 */
class OffsetSet_Test extends PLL_UnitTestCase {
	/**
	 * Makes sure that `offsetSet()` doesn't modify the option's value (because `offsetGet()` doesn't return a reference).
	 *
	 * @return void
	 */
	public function test_arrayaccess_should_not_set_sub_value() {
		$options = self::create_options();

		$this->assertSame( array(), $options->get( 'nav_menus' ), 'The initial value should be an empty array.' );

		/*
		 * This will break with PHPUnit 10.
		 * @see https://github.com/sebastianbergmann/phpunit/issues/5062
		 */
		$this->expectException( '\PHPUnit\Framework\Error\Notice' );
		$this->expectExceptionMessage( 'Indirect modification of overloaded element of WP_Syntex\Polylang\Options\Options has no effect' );

		$options['nav_menus']['twentytwentyone'] = array( 'primary' => array( 'fr' => 4 ) );

		$this->assertSame( array(), $options->get( 'nav_menus' ), 'The option should not have been modified.' );
	}
}
