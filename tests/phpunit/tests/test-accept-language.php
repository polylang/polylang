<?php

/**
 * Test a single {@see PLL_Aceept_Language} instance.
 */
class Accept_Language_Test extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider default_values_provider
	 * @param mixed $arg Class constructor argument.
	 */
	public function test_it_should_have_1_dot_0_as_default_quality_value( $arg ) {
		$accept_language = new PLL_Accept_Language( array( 'language' => 'en' ), $arg );

		$this->assertSame( 1.0, $accept_language->get_quality() );
	}

	public function default_values_provider() {
		return array(
			array( 'something else' ),
			array( '' ),
			array( null ),
		);
	}

	/**
	 * @dataProvider numeric_values_provider
	 * @param $arg
	 * @param $expected
	 */
	public function test_it_should_always_use_a_float_as_quality_value( $arg, $expected ) {
		$accept_language = new PLL_Accept_Language( array( 'language' => 'en' ), $arg );

		$this->assertSame( $expected, $accept_language->get_quality() );
	}

	public function numeric_values_provider() {
		return array(
			array( '0.8', 0.8 ),
			array( 1, 1.0 ),
			array( 0.5, 0.5 ),
		);
	}
}
