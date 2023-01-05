<?php

class Admin_Filters_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'de_DE_formal' );
		self::create_language( 'ar' );
	}

	public function set_up() {
		parent::set_up();

		$links_model = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );
	}

	public function test_sanitize_title_for_current_language_without_character_conversion() {
		$this->pll_admin->curlang = self::$model->get_language( 'en' );
		$this->pll_admin->add_filters();
		$this->assertEquals( 'fullmenge', sanitize_title( 'Füllmenge' ) );
	}

	public function test_sanitize_title_for_language_from_form_without_character_conversion() {
			// Bug fixed in 2.4.1
		$_POST['post_lang_choice'] = 'en';
		$this->pll_admin->add_filters();
		$this->assertEquals( 'fullmenge', sanitize_title( 'Füllmenge' ) );
	}

	public function test_sanitize_title_for_current_language_with_character_conversion() {
		$this->pll_admin->curlang = self::$model->get_language( 'de' );
		$this->pll_admin->add_filters();
		$this->assertEquals( 'fuellmenge', sanitize_title( 'Füllmenge' ) );
	}

	public function test_sanitize_title_for_language_from_form_with_character_conversion() {
		// Bug fixed in 2.4.1
		$_POST['post_lang_choice'] = 'de';
		$this->pll_admin->add_filters();
		$this->assertEquals( 'fuellmenge', sanitize_title( 'Füllmenge' ) );
	}

	public function test_sanitize_user_without_character_conversion() {
		$this->pll_admin->curlang = self::$model->get_language( 'en' );
		$this->pll_admin->add_filters();
		$this->assertEquals( 'angstrom', sanitize_user( 'ångström' ) );
	}

	public function test_sanitize_user_with_character_conversion() {
		$this->pll_admin->curlang = self::$model->get_language( 'de' );
		$this->pll_admin->add_filters();
		$this->assertEquals( 'angstroem', sanitize_user( 'ångström' ) );
	}

	public function test_personal_options_update() {
		$this->pll_admin->add_filters();
		$_POST['description_de'] = 'Biography in German';
		remove_action( 'personal_options_update', 'send_confirmation_on_profile_email' );
		do_action( 'personal_options_update', 1 );
		$this->assertEquals( $_POST['description_de'], get_user_meta( 1, 'description_de', true ) );
	}

	public function test_admin_body_class_ltr() {
		// Since WP 5.4, remove this filter which requires a WP_Screen that we don't provide and is not relevant for our test.
		if ( class_exists( 'WP_Site_Health' ) ) {
			remove_filter( 'admin_body_class', array( WP_Site_Health::get_instance(), 'admin_body_class' ) );
		}

		$this->pll_admin->curlang = self::$model->get_language( 'en' );
		$this->pll_admin->add_filters();
		$this->assertEquals( ' pll-dir-ltr pll-dir-rtl pll-language-en', apply_filters( 'admin_body_class', '' ) );
	}

	public function test_admin_body_class_rtl() {
		// Since WP 5.4, remove this filter which requires a WP_Screen that we don't provide and is not relevant for our test.
		if ( class_exists( 'WP_Site_Health' ) ) {
			remove_filter( 'admin_body_class', array( WP_Site_Health::get_instance(), 'admin_body_class' ) );
		}

		$this->pll_admin->curlang = self::$model->get_language( 'ar' );
		$this->pll_admin->add_filters();
		$this->assertEquals( ' pll-dir-rtl pll-language-ar', apply_filters( 'admin_body_class', '' ) );
	}


	public function test_privacy_page_post_states() {
		$en = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		update_option( 'wp_page_for_privacy_policy', $en );

		$de = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $de, 'de' );

		self::$model->post->save_translations( $en, compact( 'en', 'de' ) );

		$this->pll_admin->add_filters();

		ob_start();
		_post_states( get_post( $en ) );
		$this->assertNotFalse( strpos( ob_get_clean(), "<span class='post-state'>Privacy Policy Page</span>" ) );

		ob_start();
		_post_states( get_post( $de ) );
		$this->assertNotFalse( strpos( ob_get_clean(), "<span class='post-state'>Privacy Policy Page</span>" ) );
	}
}
