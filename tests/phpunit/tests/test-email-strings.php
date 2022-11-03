<?php

class Email_Strings_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		// Let's create languages available in DIR_TESTDATA . '/languages/' so locale switches is done correctly in tests.
		self::create_language( 'en_US' );
		self::create_language( 'es_ES' );

		require_once POLYLANG_DIR . '/include/api.php';
	}

	public function set_up() {
		parent::set_up();

		$this->links_model = self::$model->get_links_model();
	}


	public function test_retrieve_password_default_language() {
		global $wp_version;
		if ( version_compare( $wp_version, '5.7', '<' ) ) {
			$this->markTestSkipped( 'This test requires WordPress version 5.7 or higher.' );
		}

		if ( is_multisite() ) {
			$this->markTestSkipped( 'The blog name string is not translatable in retrieve password email for multisite.' );
		}

		// Create string translations.
		$_mo = new PLL_MO();
		$_mo->add_entry( $_mo->make_entry( get_bloginfo( 'name' ), 'My Site' ) );
		$_mo->export_to_db( self::$model->get_language( 'en' ) );

		reset_phpmailer_instance();
		self::factory()->user->create(
			array(
				'user_login' => 'janeDoe',
				'user_email' => 'jane.doe@example.com',
				'locale'     => 'en_US',
			)
		);

		// Set PLL environment.
		$admin = new PLL_Admin( $this->links_model );
		$admin->init();

		// Let's send the mail.
		$mailer = tests_retrieve_phpmailer_instance();
		$result = retrieve_password( 'janeDoe' );
		$this->assertTrue( $result, 'No mail has been sent to retrieve password.' );
		$this->assertNotFalse( strpos( $mailer->get_sent()->subject, 'My Site' ), 'Blogname string has not been translated in mail subject.' );
		$this->assertNotFalse( strpos( $mailer->get_sent()->body, 'My Site' ), 'Blogname string has not been translated in mail body.' );
		reset_phpmailer_instance();
	}

	public function test_retrieve_password_secondary_language() {
		global $wp_version;
		if ( version_compare( $wp_version, '5.7', '<' ) ) {
			$this->markTestSkipped( 'This test requires WordPress version 5.7 or higher.' );
		}

		if ( is_multisite() ) {
			$this->markTestSkipped( 'The blog name string is not translatable in retrieve password email for multisite.' );
		}

		// Create string translations.
		$_mo = new PLL_MO();
		$_mo->add_entry( $_mo->make_entry( get_bloginfo( 'name' ), 'Mi Sitio' ) );
		$_mo->export_to_db( self::$model->get_language( 'es' ) );

		reset_phpmailer_instance();
		self::factory()->user->create(
			array(
				'user_login' => 'Picasso',
				'user_email' => 'picasso@example.com',
				'locale'     => 'es_ES',
			)
		);

		// Set PLL environment.
		$admin = new PLL_Admin( $this->links_model );
		$admin->init();

		// Let's send the mail.
		$mailer = tests_retrieve_phpmailer_instance();
		$result = retrieve_password( 'Picasso' );
		$this->assertTrue( $result, 'No mail has been sent to retrieve password.' );
		$this->assertNotFalse( strpos( $mailer->get_sent()->subject, 'Mi Sitio' ), 'Blogname string has not been translated in mail subject.' );
		$this->assertNotFalse( strpos( $mailer->get_sent()->body, 'Mi Sitio' ), 'Blogname string has not been translated in mail body.' );
		reset_phpmailer_instance();
	}

	public function test_site_title_in_password_change_email() {
		// Important to use a language available in DIR_TESTDATA . '/languages/', otherwise switch_to_locale() doesn't switch.
		$language = self::$model->get_language( 'es' );
		$_mo = new PLL_MO();
		$_mo->add_entry( $_mo->make_entry( get_bloginfo( 'name' ), 'Mi sitio' ) );
		$_mo->export_to_db( $language );

		reset_phpmailer_instance();
		$user_id = self::factory()->user->create();
		update_user_meta( $user_id, 'locale', 'es_ES' );

		// Set PLL environment.
		$admin = new PLL_Admin( $this->links_model );
		$admin->init();
		$frontend_filters = new PLL_Admin_Filters( $admin );

		$userdata = array(
			'ID'        => $user_id,
			'user_pass' => 'new password',
		);
		wp_update_user( $userdata );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertNotFalse( strpos( $mailer->get_sent()->subject, 'Mi sitio' ) );
		$this->assertNotFalse( strpos( $mailer->get_sent()->body, 'Mi sitio' ) );
		reset_phpmailer_instance();
	}

	public function test_site_title_in_email_change_confirmation_email() {
		$language = self::$model->get_language( 'es' );
		$_mo = new PLL_MO();
		$_mo->add_entry( $_mo->make_entry( get_bloginfo( 'name' ), 'Mi sitio' ) );
		$_mo->export_to_db( $language );

		reset_phpmailer_instance();
		$user_id = self::factory()->user->create();
		update_user_meta( $user_id, 'locale', 'es_ES' );
		wp_set_current_user( $user_id );
		set_current_screen( 'profile.php' );

		// Set PLL environment.
		$admin = new PLL_Admin( $this->links_model );
		$admin->init();

		$_POST = array(
			'user_id' => $user_id,
			'email'   => 'my_new_email@example.org',
		);
		do_action( 'personal_options_update', $user_id );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertNotFalse( strpos( $mailer->get_sent()->subject, 'Mi sitio' ) );
		$this->assertNotFalse( strpos( $mailer->get_sent()->body, 'Mi sitio' ) );
		reset_phpmailer_instance();
	}
}
