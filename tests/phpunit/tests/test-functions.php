<?php

/**
 * Test class for Polylang functions.
 */
class Functions_Test extends PHPUnit_Framework_TestCase {

	public function test_sanitize_id() {
		$this->assertSame( 0, pll_sanitize_id( null ) );
		$this->assertSame( 0, pll_sanitize_id( array() ) );
		$this->assertSame( 0, pll_sanitize_id( array( 100 ) ) );
		$this->assertSame( 0, pll_sanitize_id( (object) array() ) );
		$this->assertSame( 0, pll_sanitize_id( (object) array( 100 ) ) );
		$this->assertSame( 100, pll_sanitize_id( 100 ) );
		$this->assertSame( 0, pll_sanitize_id( -100 ) );
		$this->assertSame( 100, pll_sanitize_id( 100.0 ) );
		$this->assertSame( 100, pll_sanitize_id( 100.1 ) );
		$this->assertSame( 100, pll_sanitize_id( '100' ) );
		$this->assertSame( 0, pll_sanitize_id( '-100' ) );
		$this->assertSame( 100, pll_sanitize_id( '100.0' ) );
		$this->assertSame( 100, pll_sanitize_id( '100.1' ) );
		$this->assertSame( 0, pll_sanitize_id( 'true' ) );
		$this->assertSame( 0, pll_sanitize_id( 'false' ) );
		$this->assertSame( 0, pll_sanitize_id( true ) );
		$this->assertSame( 0, pll_sanitize_id( false ) );
	}

	public function test_sanitize_ids() {
		$inputs = array(
			null,
			array(),
			array( 101 ),
			(object) array(),
			(object) array( 102 ),
			111,
			-112,
			113.0,
			114.1,
			'121',
			'-122',
			'123.0',
			'124.1',
			'true',
			'false',
			true,
			false,
		);

		$expected = array(
			111,
			113,
			114,
			121,
			123,
			124,
		);

		$this->assertSame( $expected, array_values( pll_sanitize_ids( $inputs ) ) ); // Use `array_values()` to re-index.
	}
}
