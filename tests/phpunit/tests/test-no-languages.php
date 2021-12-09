<?php

class No_Languages_Test extends PLL_UnitTestCase {

	// bug fixed in 1.8.2
	public function test_api_on_admin() {
		require_once POLYLANG_DIR . '/include/api.php'; // usually loaded only if an instance of Polylang exists

		$links_model = self::$model->get_links_model();
		$GLOBALS['polylang'] = new PLL_Admin( $links_model );

		// FIXME can't test pll_the_languages due to the constant PLL_ADMIN
		$this->assertFalse( pll_current_language() );
		$this->assertFalse( pll_default_language() ); // the bug
		$this->assertEquals( home_url( '/' ), pll_home_url() );
	}
}
