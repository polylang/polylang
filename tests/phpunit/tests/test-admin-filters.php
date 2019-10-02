<?php

class Admin_Filters_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'de_DE_formal' );
		self::create_language( 'ar' );
	}

	function setUp() {
		parent::setUp();

		self::$polylang->filters = new PLL_Admin_Filters( self::$polylang );
	}

	function test_sanitize_title() {
		self::$polylang->filters->curlang = self::$polylang->model->get_language( 'en' );
		$this->assertEquals( 'fullmenge', sanitize_title( 'Füllmenge' ) );

		self::$polylang->filters->curlang = self::$polylang->model->get_language( 'de' );
		$this->assertEquals( 'fuellmenge', sanitize_title( 'Füllmenge' ) );

		unset( self::$polylang->filters->curlang );

		// Bug fixed in 2.4.1
		$_POST['post_lang_choice'] = 'en';
		$this->assertEquals( 'fullmenge', sanitize_title( 'Füllmenge' ) );

		$_POST['post_lang_choice'] = 'de';
		$this->assertEquals( 'fuellmenge', sanitize_title( 'Füllmenge' ) );

		unset( $_POST );
	}

	function test_sanitize_user() {
		self::$polylang->filters->curlang = self::$polylang->model->get_language( 'en' );
		$this->assertEquals( 'angstrom', sanitize_user( 'ångström' ) );

		self::$polylang->filters->curlang = self::$polylang->model->get_language( 'de' );
		$this->assertEquals( 'angstroem', sanitize_user( 'ångström' ) );
	}

	function test_personal_options_update() {
		$_POST['user_lang'] = 'en_US'; // Backward compatibility with WP < 4.7
		$_POST['description_de'] = 'Biography in German';
		remove_action( 'personal_options_update', 'send_confirmation_on_profile_email' );
		do_action( 'personal_options_update', 1 );
		$this->assertEquals( $_POST['description_de'], get_user_meta( 1, 'description_de', true ) );
		unset( $_POST );
	}

	function test_admin_body_class() {
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );
		$this->assertEquals( ' pll-dir-ltr', apply_filters( 'admin_body_class', '' ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'ar' );
		$this->assertEquals( ' pll-dir-rtl', apply_filters( 'admin_body_class', '' ) );

		unset( self::$polylang->curlang );
	}

	function test_privacy_page_post_states() {
		$en = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		update_option( 'wp_page_for_privacy_policy', $en );

		$de = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $de, 'de' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'de' ) );

		ob_start();
		_post_states( get_post( $en ) );
		$this->assertNotFalse( strpos( ob_get_clean(), "<span class='post-state'>Privacy Policy Page</span>" ) );

		ob_start();
		_post_states( get_post( $de ) );
		$this->assertNotFalse( strpos( ob_get_clean(), "<span class='post-state'>Privacy Policy Page</span>" ) );
	}
}
