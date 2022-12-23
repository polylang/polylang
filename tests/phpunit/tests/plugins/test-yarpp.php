<?php

class YARPP_Test extends PLL_UnitTestCase {

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
	}

	/**
	 * Bug introduced in 1.8 and fixed in 1.8.2.
	 */
	public function test_yarpp_support() {
		define( 'YARPP_VERSION', '1.0' ); // Fake.
		do_action( 'plugins_loaded' );
		do_action( 'init' );
		$this->assertEquals( 1, $GLOBALS['wp_taxonomies']['language']->yarpp_support );
	}
}
