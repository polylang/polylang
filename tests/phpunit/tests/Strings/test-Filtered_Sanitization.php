<?php

namespace WP_Syntex\Polylang\Tests\Strings;

use PLL_UnitTestCase;
use PLL_Admin_Strings;
use PLL_UnitTest_Factory;

/**
 * Test the sanitization of the strings translations with the filter 'pll_sanitize_string_translation'.
 *
 * @group strings
 */
class Test_Filtered_Sanitization extends PLL_UnitTestCase {
	/**
	 * @var WP_User
	 */
	private static $the_boss;

	/**
	 * @var WP_User
	 */
	private static $not_the_boss;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		self::$the_boss     = $factory->user->create_and_get( array( 'role' => 'administrator' ) ); // Has the 'unfiltered_html' capability.
		self::$not_the_boss = $factory->user->create_and_get( array( 'role' => 'author' ) ); // Doesn't have the 'unfiltered_html' capability.

		if ( is_multisite() ) {
			grant_super_admin( self::$the_boss->ID );
		}
	}

	public function set_up() {
		parent::set_up();

		PLL_Admin_Strings::get_strings();
		PLL_Admin_Strings::init();
	}

	public static function wpTearDownAfterClass() {
		wp_delete_user( self::$the_boss->ID );
		wp_delete_user( self::$not_the_boss->ID );

		parent::wpTearDownAfterClass();
	}

	/**
	 * @testWith ["the_boss", "Random string"]
	 *           ["not_the_boss", "Random string"]
	 *
	 * @param string $user   The user to set as current user.
	 * @param string $string The string to sanitize.
	 * @return void
	 */
	public function test_sanitize_safe_string( $user, $string ) {
		wp_set_current_user( self::${$user}->ID );

		$this->assertSame( $string, apply_filters( 'pll_sanitize_string_translation', $string, 'test', 'test', 'test', 'test' ) );
	}

	/**
	 * @testWith ["the_boss", "<script>alert('heck');</script>", "<script>alert('heck');</script>"]
	 *           ["not_the_boss", "<script>alert('heck');</script>", "alert('heck');"]
	 *
	 * @param string $user   The user to set as current user.
	 * @param string $string The string to sanitize.
	 * @param string $expected The expected string.
	 * @return void
	 */
	public function test_sanitize_unsafe_string( $user, $string, $expected ) {
		wp_set_current_user( self::${$user}->ID );

		$this->assertSame( $expected, apply_filters( 'pll_sanitize_string_translation', $string, 'test', 'test', 'test', 'test' ) );
	}
}
