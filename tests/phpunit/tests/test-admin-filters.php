<?php

class Admin_Filters_Test extends PLL_UnitTestCase {

	/**
	 * @var PLL_Language[]
	 */
	protected static $languages;

	/**
	 * @param PLL_UnitTest_Factory $factory
	 * @return void
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		self::$languages = array(
			'en' => $factory->language->create_and_get( array( 'locale' => 'en_US' ) ),
			'de' => $factory->language->create_and_get( array( 'locale' => 'de_DE_formal' ) ),
			'ar' => $factory->language->create_and_get( array( 'locale' => 'ar' ) ),
		);
	}

	public function test_sanitize_title_for_current_language_without_character_conversion() {
		$this->add_filter_pll_admin_current_language( 'en' );

		new PLL_Context_Admin();
		$this->assertEquals( 'fullmenge', sanitize_title( 'Füllmenge' ) );
	}

	public function test_sanitize_title_for_language_from_form_without_character_conversion() {
		// Bug fixed in 2.4.1
		$_POST['post_lang_choice'] = 'en';
		new PLL_Context_Admin();
		$this->assertEquals( 'fullmenge', sanitize_title( 'Füllmenge' ) );
	}

	public function test_sanitize_title_for_current_language_with_character_conversion() {
		$this->add_filter_pll_admin_current_language( 'de' );

		new PLL_Context_Admin();
		$this->assertEquals( 'fuellmenge', sanitize_title( 'Füllmenge' ) );
	}

	public function test_sanitize_title_for_language_from_form_with_character_conversion() {
		// Bug fixed in 2.4.1
		$_POST['post_lang_choice'] = 'de';
		new PLL_Context_Admin();
		$this->assertEquals( 'fuellmenge', sanitize_title( 'Füllmenge' ) );
	}

	public function test_sanitize_user_without_character_conversion() {
		$this->add_filter_pll_admin_current_language( 'en' );

		new PLL_Context_Admin();
		$this->assertEquals( 'angstrom', sanitize_user( 'ångström' ) );
	}

	public function test_sanitize_user_with_character_conversion() {
		$this->add_filter_pll_admin_current_language( 'de' );

		new PLL_Context_Admin();
		$this->assertEquals( 'angstroem', sanitize_user( 'ångström' ) );
	}

	public function test_personal_options_update() {
		new PLL_Context_Admin();
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

		$this->add_filter_pll_admin_current_language( 'en' );

		new PLL_Context_Admin();
		$this->assertEquals( ' pll-dir-ltr pll-lang-en', apply_filters( 'admin_body_class', '' ) );
	}

	public function test_admin_body_class_rtl() {
		// Since WP 5.4, remove this filter which requires a WP_Screen that we don't provide and is not relevant for our test.
		if ( class_exists( 'WP_Site_Health' ) ) {
			remove_filter( 'admin_body_class', array( WP_Site_Health::get_instance(), 'admin_body_class' ) );
		}

		$this->add_filter_pll_admin_current_language( 'ar' );

		new PLL_Context_Admin();
		$this->assertEquals( ' pll-dir-rtl pll-lang-ar', apply_filters( 'admin_body_class', '' ) );
	}

	public function test_privacy_page_post_states() {
		new PLL_Context_Admin();

		list( $en, $de ) = array_values(
			self::factory()->post->create_translated(
				array(
					'post_type' => 'page',
					'lang'      => 'en',
				),
				array(
					'post_type' => 'page',
					'lang'      => 'de',
				)
			)
		);

		update_option( 'wp_page_for_privacy_policy', $en );

		ob_start();
		_post_states( get_post( $en ) );
		$this->assertNotFalse( strpos( ob_get_clean(), "<span class='post-state'>Privacy Policy Page</span>" ) );

		ob_start();
		_post_states( get_post( $de ) );
		$this->assertNotFalse( strpos( ob_get_clean(), "<span class='post-state'>Privacy Policy Page</span>" ) );
	}

	protected function add_filter_pll_admin_current_language( $slug ) {
		add_filter(
			'pll_admin_current_language',
			function () use ( $slug ) {
				return self::$languages[ $slug ];
			}
		);
	}
}
