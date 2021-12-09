<?php

class License_Test extends PLL_UnitTestCase {

	public function set_up() {
		parent::set_up();

		$this->license = new PLL_License( 'polylang-pro/polylang.php', 'Polylang Pro', POLYLANG_VERSION, 'WP SYNTEX' );

		// Fake the http call to not connect to the Internet for doing tests.
		add_filter(
			'pre_http_request',
			function( $preempt, $parsed_args, $url ) {
				return array(
					'headers' => null,
					'body'    => '{"success": true,"license": "valid","item_id": false,"item_name": "Polylang+Pro","license_limit": 1,"site_count": 1,"expires": "2020-05-28 23: 59: 59","activations_left": 0,"checksum": "11112222333344445555666677778888","payment_id": 9999,"customer_name": "","customer_email": "john@doe.com","price_id": "3"}',
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'cookies' => null,
					'filename' => null,
				);
			},
			10,
			3
		);
	}

	public function test_valid() {
		$this->license->license_data = (object) array(
			'success' => 1,
			'expires' => gmdate( 'Y-m-d H:i:s', strtotime( '+60 days' ) ),
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'Your license key expires on' ) );
	}

	public function test_expires_soon() {
		$this->license->license_data = (object) array(
			'success' => 1,
			'expires' => gmdate( 'Y-m-d H:i:s', strtotime( '+14 days' ) ),
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'Your license key will expire soon' ) );
	}

	public function test_lifetime() {
		$this->license->license_data = (object) array(
			'success' => 1,
			'expires' => 'lifetime',
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'The license key never expires' ) );
	}

	public function test_expired_after_last_check() {
		$this->license->license_data = (object) array(
			'success' => 1,
			'expires' => gmdate( 'Y-m-d H:i:s', strtotime( '-1 days' ) ),
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'Your license key expired on' ) );
	}

	public function test_expired() {
		$this->license->license_data = (object) array(
			'success' => false,
			'error' => 'expired',
			'expires' => gmdate( 'Y-m-d H:i:s', strtotime( '-1 days' ) ),
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'Your license key expired on' ) );
	}

	public function test_invalid_license() {
		$this->license->license_data = (object) array(
			'success' => false,
			'error' => 'missing',
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'Invalid license' ) );
	}

	public function test_disabled_license() {
		$this->license->license_data = (object) array(
			'success' => false,
			'error' => 'disabled',
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'Your license key has been disabled' ) );
	}


	public function test_license_exists_but_wrong_product() {
		$this->license->license_data = (object) array(
			'success' => false,
			'error' => 'item_name_mismatch',
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'This is not a Polylang Pro license key' ) );
	}

	public function test_limit_reached() {
		$this->license->license_data = (object) array(
			'success' => false,
			'error' => 'no_activations_left',
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'Your license key has reached its activation limit' ) );
	}

	public function test_activate_license() {
		update_option( 'polylang_licenses', '' ); // Put wrong option for testing strenghness.

		$this->license->activate_license( '00001111222233334444555566667777' );

		$licenses = get_option( 'polylang_licenses' );

		$this->assertArrayHasKey( 'polylang-pro', $licenses );
		$this->assertArrayHasKey( 'key', $licenses['polylang-pro'] );
		$this->assertEquals( $this->license->license_key, $licenses['polylang-pro']['key'] );
		$this->assertArrayHasKey( 'data', $licenses['polylang-pro'] );

	}
}
