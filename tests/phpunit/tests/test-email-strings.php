<?php

class Email_Strings_Test extends PLL_UnitTestCase {

	/**
	 * @var PLL_Links_Model
	 */
	protected $links_model;

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

		$_POST['user_login'] = 'janeDoe'; // Backward compatibility with WordPress < 5.7

		// Set PLL environment.
		$admin = new PLL_Admin( $this->links_model );
		$admin->init();

		// Let's send the mail.
		$mailer = tests_retrieve_phpmailer_instance();
		$result = retrieve_password();
		$this->assertTrue( $result, 'No mail has been sent to retrieve password.' );
		$this->assertNotFalse( strpos( $mailer->get_sent()->subject, 'My Site' ), 'Blogname string has not been translated in mail subject.' );
		$this->assertNotFalse( strpos( $mailer->get_sent()->body, 'My Site' ), 'Blogname string has not been translated in mail body.' );
		reset_phpmailer_instance();
	}

	public function test_retrieve_password_secondary_language() {
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

		$_POST['user_login'] = 'Picasso'; // Backward compatibility with WordPress < 5.7

		// Set PLL environment.
		$admin = new PLL_Admin( $this->links_model );
		$admin->init();

		// Let's send the mail.
		$mailer = tests_retrieve_phpmailer_instance();
		$result = retrieve_password();
		$this->assertTrue( $result, 'No mail has been sent to retrieve password.' );
		$this->assertNotFalse( strpos( $mailer->get_sent()->subject, 'Mi Sitio' ), 'Blogname string has not been translated in mail subject.' );
		$this->assertNotFalse( strpos( $mailer->get_sent()->body, 'Mi Sitio' ), 'Blogname string has not been translated in mail body.' );
		reset_phpmailer_instance();
	}
}
