<?php

class Polylang_Textdomain_Load_Test extends PLL_UnitTestCase {
	/**
	 * @var PLL_Language[]
	 */
	protected static $languages;


	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		self::$languages = array(
			'en' => $factory->language->create_and_get( array( 'locale' => 'en_US' ) ),
			'fr' => $factory->language->create_and_get( array( 'locale' => 'fr_FR' ) ),
		);
	}

	public static function wpTearDownAfterClass() {
		parent::wpTearDownAfterClass();

		unlink( WP_LANG_DIR . '/plugins/polylang-fr_FR.mo' );
	}

	public function test_polylang_textdomain_load_after_setup_theme() {
		global $wp_version, $wp_textdomain_registry;

		// To make `polylang` textdomain correctly registered.
		$wp_textdomain_registry = new WP_Textdomain_Registry();

		// Reset the textdomain.
		$GLOBALS['l10n_unloaded']['polylang'] = true;
		unset( $GLOBALS['l10n']['polylang'] );

		// Copy language file.
		@mkdir( DIR_TESTDATA );
		@mkdir( WP_LANG_DIR );
		@mkdir( WP_LANG_DIR . '/plugins' );
		copy( PLL_TEST_DATA_DIR . 'plugins/polylang/polylang-fr_FR.mo', WP_LANG_DIR . '/plugins/polylang-fr_FR.mo' );

		update_user_meta( 1, 'locale', 'fr_FR' );
		wp_set_current_user( 1 );
		set_current_screen( 'index.php' );

		/**
		 * Prevent PLL_OLT_Manager to reset `$GLOBALS['l10n']` when Polylang is initializing during test.
		 * Ensure this test work correctly because `lang_dir_for_domain` filter was introduced since WordPress 6.6.
		 */
		if ( is_admin() && version_compare( $wp_version, '6.6', '>=' ) ) {
			remove_action( 'pll_language_defined', array( PLL_OLT_Manager::instance(), 'load_textdomains' ), 2 );
			remove_action( 'pll_no_language_defined', array( PLL_OLT_Manager::instance(), 'load_textdomains' ) );
		}

		$this->assertSame( 'Languages', __( 'Languages', 'polylang' ) );

		do_action( 'after_setup_theme' ); // Fires `Polylang::enable_textdomain()`.

		$this->assertSame( 'Langues', __( 'Languages', 'polylang' ) );
		$this->assertSame( 'Champs personnalisés', __( 'Custom fields', 'polylang' ) );
		$this->assertSame( 'Sélecteur de langues', __( 'Language switcher', 'polylang' ) );
	}
}
