<?php

namespace WP_Syntex\Polylang\Tests\Integration\Options\Options;

use PLL_UnitTestCase;
use WP_Error;

/**
 * Tests for `Options\Options->unset_sub_value()`.
 *
 * @group options
 */
class UnsetSubValue_Test extends PLL_UnitTestCase {
	public function test_should_unset_sub_value() {
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

		// Make sure the initial value is not what we will expect later.
		$this->assertSame( $nav_menus, $options->get( 'nav_menus' ), 'The initial value should have the value we want to unset.' );

		$new_value = $options->unset_sub_value( 'nav_menus', array( 'twentytwentyone', 'primary', 'fr' ) );

		// Expect no errors.
		$this->assertIsArray( $new_value, 'Unsetting the value should return the modified value.' );

		// Expect the value to be modified.
		$expected = array(
			'twentytwentyone' => array(
				'primary' => array(
					'en' => 7,
				),
				'footer'  => array(),
			),
		);
		$this->assertSame( $expected, $options->get( 'nav_menus' ), 'The option should have been modified.' );

		$options->save_all();

		// Expect the value in the DB to be modified (this proves that `Options::$modified` has been modified).
		$options = get_option( 'polylang', array() );

		$this->assertIsArray( $options );
		$this->assertSameSetsWithIndex(
			array( 'nav_menus' => $expected ),
			array_intersect_key( $options, array( 'nav_menus' => array() ) ),
			'The option should have been modified in the database.'
		);
	}

	public function test_arrayaccess_should_not_set_sub_value() {
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
		$this->expectException( '\PHPUnit\Framework\Error\Notice' ); // @noRector
		$this->expectExceptionMessage( 'Indirect modification of overloaded element of WP_Syntex\Polylang\Options\Options has no effect' );

		unset( $options['nav_menus']['twentytwentyone']['primary']['fr'] );

		$this->assertSame( $nav_menus, $options->get( 'nav_menus' ), 'The option should not have been modified.' );
	}
}
