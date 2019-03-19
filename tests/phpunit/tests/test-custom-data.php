<?php

class Custom_Data_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		@mkdir( WP_CONTENT_DIR . '/polylang' );
		copy( dirname( __FILE__ ) . '/../data/fr_FR.png', WP_CONTENT_DIR . '/polylang/fr_FR.png' );
	}

	static function wpTearDownAfterClass() {
		parent::wpTearDownAfterClass();

		unlink( WP_CONTENT_DIR . '/polylang/fr_FR.png' );
		rmdir( WP_CONTENT_DIR . '/polylang' );
	}

	function setUp() {
		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
	}

	function test_custom_flag() {
		$lang = self::$polylang->model->get_language( 'fr' );
		$this->assertEquals( content_url( '/polylang/fr_FR.png' ), $lang->flag_url );
		$this->assertEquals( '<img src="/wp-content/polylang/fr_FR.png" title="Français" alt="Français" />', $lang->flag );
	}

	/*
	 * bug fixed in 1.8
	 */
	function test_ssl_custom_flag() {
		$_SERVER['HTTPS'] = 'on';

		// test ssl also for default flags
		$lang = self::$polylang->model->get_language( 'en' );
		$this->assertContains( 'https', $lang->flag_url );

		// custom flags
		$lang = self::$polylang->model->get_language( 'fr' );
		$this->assertEquals( content_url( '/polylang/fr_FR.png' ), $lang->flag_url );
		$this->assertContains( 'https', $lang->flag_url );
		unset( $_SERVER['HTTPS'] );
	}

}
