<?php

class Admin_Filters_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'de_DE_formal' );
	}

	function setUp() {
		parent::setUp();

		self::$polylang->filters = new PLL_Admin_Filters( self::$polylang );
	}

	function test_sanitize_title() {
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );
		$this->assertEquals( 'fullmenge', sanitize_title( 'Füllmenge' ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'de' );
		$this->assertEquals( 'fuellmenge', sanitize_title( 'Füllmenge' ) );
	}

	function test_sanitize_user() {
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );
		$this->assertEquals( 'angstrom', sanitize_user( 'ångström' ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'de' );
		$this->assertEquals( 'angstroem', sanitize_user( 'ångström' ) );
	}

	function test_personal_options_update() {
		$_POST['description_de'] = 'Biography in German';
		remove_action( 'personal_options_update', 'send_confirmation_on_profile_email' );
		do_action( 'personal_options_update', 1 );
		$this->assertEquals( $_POST['description_de'], get_user_meta( 1, 'description_de', true ) );
		unset( $_POST );
	}
}
