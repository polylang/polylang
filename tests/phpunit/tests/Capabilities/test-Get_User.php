<?php

use WP_Syntex\Polylang\Capabilities\User\NOOP;
use WP_Syntex\Polylang\Capabilities\Capabilities;
use WP_Syntex\Polylang\Capabilities\User\Prototype;
use WP_Syntex\Polylang\Capabilities\User\User_Interface;
use WP_Syntex\Polylang\Capabilities\User\Prototype_Interface;

/**
 * Test the get_user method of the Capabilities class.
 */
class Test_Get_User extends PLL_UnitTestCase {
	public function test_get_user_returns_noop_user_by_default() {
		$user = Capabilities::get_user();

		$this->assertInstanceOf( NOOP::class, $user );
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

		$this->assertSame( $user_1->get_id(), $user_2->get_id() );
		$this->assertSame( $user_id, $user_1->get_id() );
	}

	public function test_get_user_prototype_pattern_with_user_switching() {
		$user_id_1 = self::factory()->user->create();
		$user_id_2 = self::factory()->user->create();

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
		$prototype_stub = $this->createStub( Prototype_Interface::class );
		Capabilities::set_user_prototype( $prototype_stub );

		$user = Capabilities::get_user();

		$this->assertInstanceOf( User_Interface::class, $user );
	}

	public function test_set_user_prototype_persists_across_multiple_calls() {
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );

		// Set a custom prototype.
		$custom_prototype = new Prototype();
		Capabilities::set_user_prototype( $custom_prototype );

		$user_1 = $custom_prototype->get( wp_get_current_user() );
		$user_2 = $custom_prototype->get( wp_get_current_user() );

		$this->assertSame( $user_id, $user_1->get_id() );
		$this->assertSame( $user_id, $user_2->get_id() );
	}
}
