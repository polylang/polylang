<?php

class License_Test extends PLL_UnitTestCase {

	function setUp() {
		parent::setUp();

		$this->license = new PLL_License( 'polylang-pro/polylang.php', 'Polylang Pro', POLYLANG_VERSION, 'WP SYNTEX' );
	}

	function test_valid() {
		$this->license->license_data = (object) array(
			'success' => 1,
			'expires' => gmdate( 'Y-m-d H:i:s', strtotime( 'last day of next month' ) ),
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'Your license key expires on' ) );
	}

	function test_expires_soon() {
		$this->license->license_data = (object) array(
			'success' => 1,
			'expires' => gmdate( 'Y-m-d H:i:s', strtotime( '+14 days' ) ),
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'Your license key will expire soon' ) );
	}

	function test_lifetime() {
		$this->license->license_data = (object) array(
			'success' => 1,
			'expires' => 'lifetime',
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'The license key never expires' ) );
	}

	function test_expired_after_last_check() {
		$this->license->license_data = (object) array(
			'success' => 1,
			'expires' => gmdate( 'Y-m-d H:i:s', strtotime( '-1 days' ) ),
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'Your license key expired on' ) );
	}

	function test_expired() {
		$this->license->license_data = (object) array(
			'success' => false,
			'error' => 'expired',
			'expires' => gmdate( 'Y-m-d H:i:s', strtotime( '-1 days' ) ),
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'Your license key expired on' ) );
	}

	function test_invalid_license() {
		$this->license->license_data = (object) array(
			'success' => false,
			'error' => 'missing',
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'Invalid license' ) );
	}

	function test_disabled_license() {
		$this->license->license_data = (object) array(
			'success' => false,
			'error' => 'disabled',
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'Your license key has been disabled' ) );
	}


	function test_license_exists_but_wrong_product() {
		$this->license->license_data = (object) array(
			'success' => false,
			'error' => 'item_name_mismatch',
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'This is not a Polylang Pro license key' ) );
	}

	function test_limit_reached() {
		$this->license->license_data = (object) array(
			'success' => false,
			'error' => 'no_activations_left',
		);

		$this->assertNotFalse( strpos( $this->license->get_form_field(), 'Your license key has reached its activation limit' ) );
	}
}
