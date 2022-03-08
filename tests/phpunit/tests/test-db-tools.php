<?php

/**
 * Tests for methods of the class `PLL_Db_Tool`.
 */
class PLL_Db_Tools_Test extends PLL_UnitTestCase {

	/**
	 * @see PLL_Db_Tools::prepare_values_list()
	 *
	 * @return void
	 */
	public function test_prepare_values_list_should_escape_and_join_values() {
		$values   = array( '', "l'omelette au \"fromage\"", 123, '456' );
		$expected = "'','l\'omelette au \\\"fromage\\\"',123,456";

		$result = PLL_Db_Tools::prepare_values_list( $values );

		$this->assertSame( $expected, $result, 'PLL_Db_Tools::prepare_values_list() should escape and join the given value' );
	}

	/**
	 * @dataProvider dataProviderQuote
	 * @see PLL_Db_Tools::prepare_value()
	 *
	 * @param  string $value    The value to test.
	 * @param  string $expected The expected value.
	 * @return void
	 */
	public function test_prepare_value_should_quote_non_numerical_values( $value, $expected ) {
		$result = PLL_Db_Tools::prepare_value( $value );
		$this->assertSame( $expected, $result, "'PLL_Db_Tools::prepare_value() should add quotes to '$value'" );
	}

	/**
	 * @dataProvider dataProviderNotQuote
	 * @see PLL_Db_Tools::prepare_value()
	 *
	 * @param  string $value    The value to test.
	 * @param  string $expected The expected value.
	 * @return void
	 */
	public function test_prepare_value_should_not_quote_numerical_values( $value, $expected ) {
		$result       = PLL_Db_Tools::prepare_value( $value );
		$value_string = is_string( $value ) ? "'$value'" : $value;
		$this->assertSame( $expected, $result, "PLL_Db_Tools::prepare_value() should not add quotes to {$value_string}" );
	}

	public function dataProviderQuote() {
		return array(
			'empty string'              => array(
				'value'    => '',
				'expected' => "''",
			),
			'string with simple quotes' => array(
				'value'    => "l'omelette",
				'expected' => "'l\'omelette'",
			),
			'string with double quotes' => array(
				'value'    => 'au "fromage"',
				'expected' => "'au \\\"fromage\\\"'",
			),
		);
	}

	public function dataProviderNotQuote() {
		return array(
			'integer'        => array(
				'value'    => 123,
				'expected' => 123,
			),
			'numeric string' => array(
				'value'    => '456',
				'expected' => 456,
			),
		);
	}
}
