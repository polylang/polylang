<?php

class OLT_Manager_Test extends PLL_UnitTestCase {
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		$factory->language->create_many( 2 );
	}

	public function test_polylang_first() {
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

	public function test_load_textdomains() {
		update_option( 'WPLANG', '' );

		new PLL_OLT_Manager();

		load_textdomain( 'foo', PLL_TEST_DATA_DIR . 'fr_FR.mo' );

		$options                 = PLL_Install::get_default_options();
		$options['force_lang']   = 0;
		$options['default_lang'] = 'en';
		$model                   = new PLL_Model( $options );
		$links_model             = $model->get_links_model();
		$frontend                = new PLL_Frontend( $links_model );
		$fr                      = $frontend->model->get_language( 'fr' );
		$frontend->curlang       = $fr;

		do_action( 'pll_language_defined', 'fr', $fr );

		$locale = get_locale();

		$this->assertNotEmpty( $locale );
		$this->assertSame( 'fr_FR', $locale );
		$this->assertSame( 'Tableau de bord', __( 'Dashboard', 'foo' ) );
	}
}
