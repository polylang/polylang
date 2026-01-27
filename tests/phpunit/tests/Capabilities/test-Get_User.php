<?php

use WP_Syntex\Polylang\Capabilities\Capabilities;
use WP_Syntex\Polylang\Capabilities\User\NOOP_User;
use WP_Syntex\Polylang\Capabilities\User\User_Interface;

/**
 * Test the get_user method of the Capabilities class.
 */
class Test_Get_User extends PLL_UnitTestCase {
	public function tear_down() {
		parent::tear_down();

		$this->reset_prototype();
	}

	public function test_get_user_returns_noop_user_by_default() {
		$user = Capabilities::get_user();

		$this->assertInstanceOf( NOOP_User::class, $user );
	}

	public function test_get_user_returns_current_user_id() {
		$current_user = wp_get_current_user();
		$user         = Capabilities::get_user();

		$this->assertSame( $current_user->ID, $user->get_id() );
	}

	public function test_get_user_returns_different_instances_for_different_users() {
		$user_id_1 = self::factory()->user->create();
		$user_id_2 = self::factory()->user->create();

		wp_set_current_user( $user_id_1 );
		$user_1 = Capabilities::get_user();

		wp_set_current_user( $user_id_2 );
		$user_2 = Capabilities::get_user();

		$this->assertNotSame( $user_1->get_id(), $user_2->get_id() );
		$this->assertSame( $user_id_1, $user_1->get_id() );
		$this->assertSame( $user_id_2, $user_2->get_id() );
	}

	public function test_get_user_returns_same_instance_for_same_user() {
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );

		$user_1 = Capabilities::get_user();
		$user_2 = Capabilities::get_user();

		$this->assertSame( $user_1, $user_2 );
		$this->assertSame( $user_id, $user_1->get_id() );
	}

	public function test_get_user_prototype_pattern_with_user_switching() {
		$user_id_1 = self::factory()->user->create();
		$user_id_2 = self::factory()->user->create();

		$this->reset_prototype();

		wp_set_current_user( $user_id_1 );
		$user_1a = Capabilities::get_user();

		wp_set_current_user( $user_id_2 );
		$user_2 = Capabilities::get_user();

		wp_set_current_user( $user_id_1 );
		$user_1b = Capabilities::get_user();

		$this->assertSame( $user_1a->get_id(), $user_1b->get_id() );
		$this->assertNotSame( $user_1a->get_id(), $user_2->get_id() );
		$this->assertSame( $user_id_1, $user_1a->get_id() );
		$this->assertSame( $user_id_2, $user_2->get_id() );
	}

	public function test_get_injected_user_returns_the_correct_instance() {
		$user = self::factory()->user->create_and_get();
		$user = Capabilities::get_user( $user );

		$this->assertSame( $user->get_id(), $user->get_id() );
	}

	public function test_set_user_prototype_changes_returned_user_type() {
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );

		// Set a custom prototype.
		$custom_prototype = $this->createMock( User_Interface::class );
		Capabilities::set_user_prototype( $custom_prototype );

		$user = Capabilities::get_user();

		$this->assertInstanceOf( User_Interface::class, $user );
	}

	public function test_set_user_prototype_persists_across_multiple_calls() {
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );

		// Set a custom prototype.
		$custom_prototype = new NOOP_User( wp_get_current_user() );
		Capabilities::set_user_prototype( $custom_prototype );

		$user_1 = Capabilities::get_user();
		$user_2 = Capabilities::get_user();

		$this->assertInstanceOf( NOOP_User::class, $user_1 );
		$this->assertInstanceOf( NOOP_User::class, $user_2 );
		$this->assertSame( $user_id, $user_1->get_id() );
		$this->assertSame( $user_id, $user_2->get_id() );
	}

	/**
	 * Reset the user_prototype static property in the Capabilities class.
	 *
	 * @return void
	 */
	private function reset_prototype() {
		$reflection = new \ReflectionClass( Capabilities::class );
		$property   = $reflection->getProperty( 'user_prototype' );
		version_compare( PHP_VERSION, '8.1', '<' ) && $property->setAccessible( true );
		$property->setValue( null, null );
	}
}
