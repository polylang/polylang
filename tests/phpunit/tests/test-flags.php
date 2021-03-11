<?php

class Flags_Test extends PLL_UnitTestCase {

	/**
	 * Instance of the Polylang context for these tests.
	 *
	 * @var PLL_Frontend
	 */
	private $pll_env;

	/**
	 * Language properties from {@see PLL_Settings::get_predefined_languages()} to be added as a new language.
	 *
	 * @var array
	 */
	private static $new_language;

	/**
	 * Path to a custom flag image.
	 *
	 * @var string
	 */
	private static $flag_de_ch_informal = WP_CONTENT_DIR . '/polylang/de_CH_informal.png';

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wp_filter;

		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		$wp_filter['pll_languages_list']->remove_all_filters();
		$wp_filter['pll_after_languages_cache']->remove_all_filters();
	}

	function setUp() {
		parent::setUp();

		$options       = array_merge( PLL_Install::get_default_options(), array( 'default_lang' => 'en_US' ) );
		$model         = new PLL_Model( $options );
		$links_model   = new PLL_Links_Default( $model ); // Registers the 'pll_languages_list' and 'pll_after_languages_cache' filters.
	}

	public function tearDown() {
		$flags = array(
			WP_CONTENT_DIR . '/polylang/fr_FR.png',
			WP_CONTENT_DIR . '/polylang/de_CH_informal.png',
		);
		foreach ( $flags as $flag ) {
			if ( file_exists( $flag ) ) {
				unlink( $flag );
			}
		}
		$flag_dir = WP_CONTENT_DIR . '/polylang';
		if ( is_dir( $flag_dir ) ) {
			rmdir( $flag_dir );
		}

		if ( isset( $_SERVER['HTTPS'] ) ) {
			unset( $_SERVER['HTTPS'] );
		}
		parent::tearDown();
	}

	function test_default_flag() {
		$lang = self::$model->get_language( 'fr' );
		$this->assertEquals( plugins_url( '/flags/fr.png', POLYLANG_FILE ), $lang->get_display_flag_url() ); // Bug fixed in 2.8.1.
		$this->assertEquals( 1, preg_match( '#<img src="data:image\/png;base64,(.+)" alt="Français" width="16" height="11" style="(.+)" \/>#', $lang->get_display_flag() ) );
	}

	function test_custom_flag() {
		@mkdir( WP_CONTENT_DIR . '/polylang' );
		copy( dirname( __FILE__ ) . '/../data/fr_FR.png', WP_CONTENT_DIR . '/polylang/fr_FR.png' );

		$lang = self::$model->get_language( 'fr' );
		$this->assertEquals( content_url( '/polylang/fr_FR.png' ), $lang->get_display_flag_url() );
		$this->assertEquals( '<img src="/wp-content/polylang/fr_FR.png" alt="Français" />', $lang->get_display_flag() );
	}

	/*
	 * bug fixed in 1.8
	 */
	function test_default_flag_ssl() {
		$_SERVER['HTTPS'] = 'on';

		$lang = self::$model->get_language( 'fr' );
		$this->assertContains( 'https', $lang->get_display_flag_url() );
	}

	function test_custom_flag_ssl() {
		$_SERVER['HTTPS'] = 'on';
		@mkdir( WP_CONTENT_DIR . '/polylang' );
		copy( dirname( __FILE__ ) . '/../data/fr_FR.png', WP_CONTENT_DIR . '/polylang/fr_FR.png' );

		$lang = self::$model->get_language( 'fr' );
		$this->assertEquals( content_url( '/polylang/fr_FR.png' ), $lang->get_display_flag_url() );
		$this->assertContains( 'https', $lang->get_display_flag_url() );
	}

	function test_remove_flag_inline_style_in_saved_language() {
		@mkdir( WP_CONTENT_DIR . '/polylang' );
		copy( dirname( __FILE__ ) . '/../data/de_CH.png', self::$flag_de_ch_informal );
		self::create_language( 'de_CH_informal' );
		$language = self::$model->get_language( 'de_CH_informal' );

		$this->assertNotContains( 'style', $language->get_display_flag() );
		$this->assertNotContains( 'width', $language->get_display_flag() );
		$this->assertNotContains( 'height', $language->get_display_flag() );
	}

	function test_remove_flag_inline_style_in_new_language() {
		$language = PLL_Language::create( self::$new_language );

		$this->assertNotContains( 'style', $language->get_display_flag() );
		$this->assertNotContains( 'width', $language->get_display_flag() );
		$this->assertNotContains( 'height', $language->get_display_flag() );
	}
}
