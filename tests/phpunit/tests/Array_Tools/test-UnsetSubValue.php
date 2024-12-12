<?php

namespace WP_Syntex\Polylang\Tests\Unit\Array_Tools;

use PHPUnit_Adapter_TestCase;
use WP_Syntex\Polylang\Array_Tools;

/**
 * Test the method {@see WP_Syntex\Polylang\Array_Tools::set_sub_value}.
 */
class UnsetSubValue_Test extends PHPUnit_Adapter_TestCase {
	/**
	 * @dataProvider expected_and_original_values_provider
	 *
	 * @param array      $orig_value The original value.
	 * @param array|null $expected   The expected value. If `null`, must be identical to `$orig_value`.
	 */
	public function test_should_unset_value( array $orig_value, ?array $expected ) {
		if ( null === $expected ) {
			$expected = $orig_value;
		}
		$this->assertSame( $expected, Array_Tools::unset_sub_value( $orig_value, array( 'foo', 'bar', 'baz', 'test' ) ) );
	}

	public function expected_and_original_values_provider() {
		return array(
			'empty'      => array(
				'orig_value' => array(
					'before' => 'abc',
				),
				'expected'   => null,
			),
			'wrong type' => array(
				'orig_value' => array(
					'before' => 'abc',
					'foo'    => array(
						'bar' => 'bleh', // Not an array.
					),
				),
				'expected'   => null,
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
				'expected'   => array(
					'before' => 'abc',
					'foo'    => array(
						'bar' => array(
							'baz' => array(),
						),
					),
				),
			),
		);
	}
}
