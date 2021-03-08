<?php

/**
 * Test a single {@see PLL_Aceept_Language} instance.
 */
class Accept_Language_Test extends PHPUnit_Framework_TestCase {

	public function test_it_should_have_1_dot_0_as_default_quality_value() {
		$accept_language = new PLL_Accept_Language( array( 'language' => 'en' ), '' );

		$this->assertSame( 1.0, $accept_language->get_quality() );
	}
}
