<?php

class No_Languages_Test extends PLL_UnitTestCase {

	// bug fixed in 1.8.2
	function test_api_on_admin() {
		require_once PLL_INC . '/api.php'; // usually loaded only if an instance of Polylang exists
		$GLOBALS['polylang'] = self::$polylang;

		// FIXME can't test pll_the_languages due to the constant PLL_ADMIN
		$this->assertFalse( pll_current_language() );
		$this->assertFalse( pll_default_language() ); // the bug
		$this->assertEquals( home_url( '/' ), pll_home_url() );
		unset( $GLOBALS['polylang'] );
	}
}
