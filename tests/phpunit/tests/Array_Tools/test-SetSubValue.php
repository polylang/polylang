<?php

namespace WP_Syntex\Polylang\Tests\Unit\Array_Tools;

use PHPUnit_Adapter_TestCase;
use WP_Syntex\Polylang\Array_Tools;

/**
 * Test the method {@see WP_Syntex\Polylang\Array_Tools::set_sub_value}.
 */
class SetSubValue_Test extends PHPUnit_Adapter_TestCase {
	/**
	 * @dataProvider original_value_provider
	 *
	 * @param array $orig_value The original value.
	 */
	public function test_should_set_value( array $orig_value ) {
		$expected = array(
			'before' => 'abc',
			'foo'    => array(
				'bar' => array(
					'baz' => array(
						'test' => 4,
					),
				),
			),
		);
		$this->assertSame( $expected, Array_Tools::set_sub_value( $orig_value, array( 'foo', 'bar', 'baz', 'test' ), 4 ) );
	}

	public function original_value_provider() {
		return array(
			'empty'      => array(
				'orig_value' => array(
					'before' => 'abc',
				),
			),
			'wrong type' => array(
				'orig_value' => array(
					'before' => 'abc',
					'foo'    => array(
						'bar' => 'bleh', // Not an array.
					),
				),
			),
			'full path'  => array(
				'orig_value' => array(
					'before' => 'abc',
					'foo'    => array(
						'bar' => array(
							'baz' => array(
								'test' => 6,
							),
						),
					),
				),
			),
		);
	}
}
