<?php

use WP_Syntex\Polylang\Capabilities\User\NOOP_User;

/**
 * Test the NOOP_User class.
 */
class Test_NOOP_User extends PLL_UnitTestCase {
	/**
	 * @var PLL_Language
	 */
	private static $language_fr;

	/**
	 * @var WP_User
	 */
	private static $translator_fr;

	/**
	 * @var WP_User
	 */
	private static $administrator;

	/**
	 * Set up before all tests.
	 *
	 * @param PLL_UnitTest_Factory $factory PLL_UnitTest_Factory object.
	 * @return void
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );

		self::$language_fr = self::$model->get_language( 'fr' );

		// Create a translator user with translate_fr capability.
		$translator_id      = self::factory()->user->create( array( 'role' => 'editor' ) );
		self::$translator_fr = get_user_by( 'ID', $translator_id );
		self::$translator_fr->add_cap( 'translate_fr' );

		self::$administrator = self::factory()->user->create_and_get( array( 'role' => 'administrator' ) );
	}

	/**
	 * @dataProvider user_provider
	 *
	 * @param string $user_type User type to test.
	 */
	public function test_get_id_returns_user_id( $user_type ) {
		$user    = $this->get_user( $user_type );
		$wp_user = 'administrator' === $user_type ? self::$administrator : self::$translator_fr;

		$this->assertSame( $wp_user->ID, $user->get_id() );
	}

	/**
	 * @dataProvider user_provider
	 *
	 * @param string $user_type User type to test.
	 */
	public function test_is_translator_always_returns_false( $user_type ) {
		$user = $this->get_user( $user_type );

		$this->assertFalse( $user->is_translator() );
	}

	/**
	 * @dataProvider user_provider
	 *
	 * @param string $user_type User type to test.
	 */
	public function test_can_translate_always_returns_true( $user_type ) {
		$user = $this->get_user( $user_type );

		$language = $this->createMock( PLL_Language::class );

		$this->assertTrue( $user->can_translate( $language ) );
	}

	/**
	 * @dataProvider user_provider
	 *
	 * @param string $user_type User type to test.
	 */
	public function test_can_translate_with_real_language( $user_type ) {
		$user = $this->get_user( $user_type );

		$this->assertTrue( $user->can_translate( self::$language_fr ) );
	}

	/**
	 * @dataProvider user_provider
	 *
	 * @param string $user_type User type to test.
	 */
	public function test_can_translate_all_with_empty_array( $user_type ) {
		$user = $this->get_user( $user_type );

		$this->assertTrue( $user->can_translate_all( array() ) );
	}

	/**
	 * @dataProvider user_provider
	 *
	 * @param string $user_type User type to test.
	 */
	public function test_can_translate_all_with_single_language( $user_type ) {
		$user = $this->get_user( $user_type );

		$this->assertTrue( $user->can_translate_all( array( 'en' ) ) );
	}

	/**
	 * @dataProvider user_provider
	 *
	 * @param string $user_type User type to test.
	 */
	public function test_can_translate_all_with_multiple_languages( $user_type ) {
		$user = $this->get_user( $user_type );

		$this->assertTrue( $user->can_translate_all( array( 'en', 'fr', 'de', 'es' ) ) );
		$this->assertTrue( $user->can_translate_all( array( 'fr' ) ) );
	}

	/**
	 * @dataProvider user_provider
	 *
	 * @param string $user_type User type to test.
	 */
	public function test_can_translate_all_with_real_languages( $user_type ) {
		$user = $this->get_user( $user_type );

		$this->assertTrue( $user->can_translate_all( array( 'en', 'fr' ) ) );
	}

	public function test_has_cap_delegates_to_wp_user() {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		$wp_user = get_user_by( 'ID', $user_id );
		$user    = new NOOP_User( $wp_user );

		$this->assertTrue( $user->has_cap( 'edit_posts' ) );
		$this->assertFalse( $user->has_cap( 'manage_options' ) );
	}

	public function test_has_cap_with_multiple_arguments() {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		$wp_user = get_user_by( 'ID', $user_id );
		$user    = new NOOP_User( $wp_user );
		$post_id = self::factory()->post->create();

		$result = $user->has_cap( 'edit_post', $post_id );

		$this->assertTrue( $result );
	}

	/**
	 * @dataProvider user_provider
	 *
	 * @param string $user_type User type to test.
	 */
	public function test_has_cap_with_user_capabilities( $user_type ) {
		$user = $this->get_user( $user_type );

		// Should delegate to WP_User and return true for edit_posts capability.
		$this->assertTrue( $user->has_cap( 'edit_posts' ) );

		// Should delegate to WP_User and return appropriate value for manage_options.
		if ( 'administrator' === $user_type ) {
			$this->assertTrue( $user->has_cap( 'manage_options' ) );
		} else {
			$this->assertFalse( $user->has_cap( 'manage_options' ) );
		}

		// Should delegate to WP_User and check translate_fr capability.
		if ( 'translator_fr' === $user_type ) {
			$this->assertTrue( $user->has_cap( 'translate_fr' ) );
		} else {
			$this->assertFalse( $user->has_cap( 'translate_fr' ) );
		}
	}

	/**
	 * @dataProvider user_provider
	 *
	 * @param string $user_type User type to test.
	 */
	public function test_get_preferred_language_slug_returns_empty_string( $user_type ) {
		$user = $this->get_user( $user_type );

		$this->assertSame( '', $user->get_preferred_language_slug() );
	}

	/**
	 * @dataProvider user_provider
	 *
	 * @param string $user_type User type to test.
	 */
	public function test_can_translate_or_die_does_not_die( $user_type ) {
		$user = $this->get_user( $user_type );

		$language = $this->createMock( PLL_Language::class );

		// This should not throw any exception or call wp_die()
		$user->can_translate_or_die( $language );

		// If we reach this point, the test passes
		$this->assertTrue( true );
	}

	/**
	 * @dataProvider user_provider
	 *
	 * @param string $user_type User type to test.
	 */
	public function test_can_translate_or_die_with_real_language( $user_type ) {
		$user = $this->get_user( $user_type );

		// This should not throw any exception or call wp_die()
		$user->can_translate_or_die( self::$language_fr );

		// If we reach this point, the test passes
		$this->assertTrue( true );
	}

	public function test_clone_returns_self_for_same_user() {
		$wp_user = self::factory()->user->create_and_get();
		$user    = new NOOP_User( $wp_user );

		$cloned_user = $user->get( $wp_user );

		$this->assertSame( $user, $cloned_user );
	}

	public function test_clone_returns_new_instance_for_different_user() {
		$wp_user_1 = self::factory()->user->create_and_get();
		$wp_user_2 = self::factory()->user->create_and_get();

		$user_1 = new NOOP_User( $wp_user_1 );
		$user_2 = $user_1->get( $wp_user_2 );

		$this->assertNotSame( $user_1->get_id(), $user_2->get_id() );
		$this->assertInstanceOf( NOOP_User::class, $user_2 );
		$this->assertSame( $wp_user_2->ID, $user_2->get_id() );
	}

	public function test_clone_preserves_noop_user_class() {
		$wp_user_1 = self::factory()->user->create_and_get();
		$wp_user_2 = self::factory()->user->create_and_get();

		$user_1 = new NOOP_User( $wp_user_1 );
		$user_2 = $user_1->get( $wp_user_2 );

		// Both should be NOOP_User instances
		$this->assertInstanceOf( NOOP_User::class, $user_1 );
		$this->assertInstanceOf( NOOP_User::class, $user_2 );
	}

	/**
	 * Data provider for users.
	 *
	 * @return array
	 */
	public function user_provider() {
		return array(
			'administrator'  => array( 'administrator' ),
			'translator_fr' => array( 'translator_fr' ),
		);
	}

	/**
	 * Get a NOOP_User instance based on user type.
	 *
	 * @param string $user_type User type ('administrator' or 'translator_fr').
	 * @return NOOP_User
	 */
	private function get_user( string $user_type ): NOOP_User {
		$wp_user = 'administrator' === $user_type ? self::$administrator : self::$translator_fr;

		return new NOOP_User( $wp_user );
	}
}
