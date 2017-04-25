<?php

class OLT_Manager_Test extends PLL_UnitTestCase {

	function test_polylang_first() {
		$plugins = array(
			'jetpack/jetpack.php',
			POLYLANG_BASENAME,
			'woocommerce/woocommerce.php',
		);

		update_option( 'active_plugins', $plugins );
		$active_plugins = get_option( 'active_plugins' );

		update_option( 'active_sitewide_plugins', $plugins );
		$active_sitewide_plugins = get_option( 'active_sitewide_plugins' );

		$this->assertEquals( POLYLANG_BASENAME, reset( $active_plugins ) );
		$this->assertEquals( POLYLANG_BASENAME, reset( $active_sitewide_plugins ) );

		delete_option( 'active_plugins' );
		delete_option( 'active_sitewide_plugins' );
	}
}
