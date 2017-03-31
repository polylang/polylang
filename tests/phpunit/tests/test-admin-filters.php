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
		$this->assertEquals( 'Angstrom', sanitize_user( 'Ångström' ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'de' );
		$this->assertEquals( 'Angstroem', sanitize_user( 'Ångström' ) );
	}
}
