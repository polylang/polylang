<?php

namespace WP_Syntex\Polylang\Tests\Integration\Options\Options;

use PLL_UnitTestCase;
use WP_Error;

/**
 * Tests for `Options\Options->set_sub_value()`.
 *
 * @group options
 */
class SetSubValue_Test extends PLL_UnitTestCase {
	public function test_should_set_sub_value() {
		$options = self::create_options();

		// Make sure the initial value is not what we will expect later.
		$this->assertSame( array(), $options->get( 'nav_menus' ), 'The initial value should be an empty array.' );

		$errors = $options->set_sub_value( 'nav_menus', array( 'twentytwentyone', 'primary', 'fr' ), 4 );

		// Expect no errors.
		$this->assertInstanceOf( WP_Error::class, $errors );
		$this->assertEmpty( $errors->get_error_codes(), 'Assignation should not trigger an error.' );

		// Expect the value to be modified.
		$expected = array(
			'twentytwentyone' => array(
				'primary' => array(
					'fr' => 4,
				),
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
