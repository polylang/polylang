<?php

class Flags_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		@mkdir( WP_CONTENT_DIR . '/polylang' );
		copy( dirname( __FILE__ ) . '/../data/fr_FR.png', WP_CONTENT_DIR . '/polylang/fr_FR.png' );
	}

	public static function wpTearDownAfterClass() {
		parent::wpTearDownAfterClass();

		unlink( WP_CONTENT_DIR . '/polylang/fr_FR.png' );
		rmdir( WP_CONTENT_DIR . '/polylang' );
	}

	public function set_up() {
		parent::set_up();

		$options       = array_merge( PLL_Install::get_default_options(), array( 'default_lang' => 'en_US' ) );
		$model         = new PLL_Model( $options );
		$links_model   = new PLL_Links_Default( $model ); // Registers the 'pll_languages_list' and 'pll_after_languages_cache' filters.
	}

	public function tear_down() {
		unset( $_SERVER['HTTPS'] );
		parent::tear_down();
	}

	public function test_default_flag() {
		$lang = self::$model->get_language( 'en' );
		$this->assertEquals( plugins_url( '/flags/us.png', POLYLANG_FILE ), $lang->get_display_flag_url() ); // Bug fixed in 2.8.1.
		$this->assertEquals( 1, preg_match( '#<img src="data:image\/png;base64,(.+)" alt="English" width="16" height="11" style="(.+)" \/>#', $lang->get_display_flag() ) );
	}

	public function test_custom_flag() {
		$lang = self::$model->get_language( 'fr' );
		$this->assertEquals( content_url( '/polylang/fr_FR.png' ), $lang->get_display_flag_url() );
		$this->assertEquals( '<img src="/wp-content/polylang/fr_FR.png" alt="Français" />', $lang->get_display_flag() );
	}

	/**
	 * Bug fixed in 1.8
	 */
	public function test_default_flag_ssl() {
		$_SERVER['HTTPS'] = 'on';

		$lang = self::$model->get_language( 'en' );
		$this->assertStringContainsString( 'https', $lang->get_display_flag_url() );
	}

	public function test_custom_flag_ssl() {
		$_SERVER['HTTPS'] = 'on';

		$lang = self::$model->get_language( 'fr' );
		$this->assertEquals( content_url( '/polylang/fr_FR.png' ), $lang->get_display_flag_url() );
		$this->assertStringContainsString( 'https', $lang->get_display_flag_url() );
	}
}
