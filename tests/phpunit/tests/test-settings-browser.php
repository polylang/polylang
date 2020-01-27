<?php

class Settings_Browser_Test extends PLL_UnitTestCase {

	function test_active_true() {
		self::$polylang->options['browser'] = 1;
		$module = new PLL_Settings_Browser( self::$polylang );
		$this->assertTrue( $module->is_active() );
	}

	function test_active_false() {
		self::$polylang->options['browser'] = 0;
		$module = new PLL_Settings_Browser( self::$polylang );
		$this->assertFalse( $module->is_active() );
	}
}
