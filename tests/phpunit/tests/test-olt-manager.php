<?php

class OLT_Manager_Test extends PLL_UnitTestCase {
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		$factory->language->create_many( 2 );
	}

	public static function wpTearDownAfterClass() {
		parent::wpTearDownAfterClass();

		unlink( WP_LANG_DIR . '/plugins/foo-fr_FR.mo' );
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
		// Clean up!
		unset( $GLOBALS['wp_themes'], $GLOBALS['l10n'], $GLOBALS['l10n_unloaded'] );

		/** @var WP_Textdomain_Registry $wp_textdomain_registry */
		global $wp_textdomain_registry;

		$wp_textdomain_registry = new WP_Textdomain_Registry();

		update_option( 'WPLANG', 'de_DE' );

		// Copy language file.
		@mkdir( DIR_TESTDATA );
		@mkdir( WP_LANG_DIR );
		@mkdir( WP_LANG_DIR . '/plugins' );
		copy( PLL_TEST_DATA_DIR . 'fr_FR.mo', WP_LANG_DIR . '/plugins/foo-fr_FR.mo' );

		new PLL_OLT_Manager();
		$options     = self::create_options(
			array(
				'default_lang' => 'en',
				'force_lang'   => 0,
			)
		);
		$model       = new PLL_Model( $options );
		$links_model = $model->get_links_model();
		$frontend    = new PLL_Frontend( $links_model );

		/*
		 *  Calls `_load_textdomain_just_in_time()` *before* the current language is defined!
		 */
		$this->assertSame( 'Dashboard', __( 'Dashboard', 'foo' ) ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

		$frontend->curlang = $frontend->model->get_language( 'fr' );

		do_action( 'pll_language_defined', 'fr', $frontend->curlang );

		$locale = get_locale();

		$this->assertNotEmpty( $locale );
		$this->assertSame( 'fr_FR', $locale );
		$this->assertSame( 'Tableau de bord', __( 'Dashboard', 'foo' ), 'fr_FR locale for foo domain should be loaded correctly.' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
	}
}
