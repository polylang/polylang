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

		unlink( WP_LANG_DIR . '/plugins/polylang-fr_FR.l10n.php' );
	}

	public function test_polylang_textdomain_load_after_setup_theme() {
		// Copy language file.
		@mkdir( DIR_TESTDATA );
		@mkdir( WP_LANG_DIR );
		@mkdir( WP_LANG_DIR . '/plugins' );
		copy( PLL_TEST_DATA_DIR . 'plugins/polylang/polylang-fr_FR.l10n.php', WP_LANG_DIR . '/plugins/polylang-fr_FR.l10n.php' );

		update_user_meta( 1, 'locale', 'fr_FR' );
		wp_set_current_user( 1 );
		set_current_screen( 'index.php' );

		if ( is_admin() ) {
			remove_action( 'pll_language_defined', array( PLL_OLT_Manager::instance(), 'load_textdomains' ), 2 );
			remove_action( 'pll_no_language_defined', array( PLL_OLT_Manager::instance(), 'load_textdomains' ) );
		}

		( new PLL_Context_Admin( array( 'options' => $this->factory()->pll_model->options->get_all() ) ) )->get();

		$this->assertSame( 'Langues', __( 'Languages', 'polylang' ) );
	}
}
