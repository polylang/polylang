<?php

namespace WP_Syntex\Polylang\Tests\Strings;

use PLL_Model;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
use WP_Syntex\Polylang\Strings\Database_Repository;

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

		$this->pll_model = new PLL_Model(
			self::create_options(
				array(
					'default_lang' => 'en',
				)
			)
		);
	}

	public function tear_down() {
		global $_wp_sidebars_widgets, $wp_registered_sidebars, $wp_registered_widgets;

		$_wp_sidebars_widgets   = array();
		$wp_registered_sidebars = array();
		$wp_registered_widgets  = array();

		Database_Repository::reset();

		parent::tear_down();
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
		Database_Repository::register( 'test', $string, 'test' );
		( new Database_Repository( $this->pll_model->languages ) )->find_all(); // Load the strings into the repository and add sanitization hooks.

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
		Database_Repository::register( 'test', $string, 'test' );
		( new Database_Repository( $this->pll_model->languages ) )->find_all(); // Load the strings into the repository and add sanitization hooks.

		wp_set_current_user( self::${$user}->ID );

		$this->assertSame( $expected, apply_filters( 'pll_sanitize_string_translation', $string, 'test', 'test', 'test', 'test' ) );
	}

	/**
	 * @testWith ["the_boss", "<script>alert('heck');</script>"]
	 *           ["not_the_boss", "<script>alert('heck');</script>"]
	 *
	 * @param string $user   The user to set as current user.
	 * @param string $string The string to sanitize.
	 * @return void
	 */
	public function test_sanitize_widget_title( $user, $string ) {
		global $wp_registered_widgets;

		update_option(
			'widget_search',
			array(
				2              => array( 'title' => 'My Title' ),
				'_multiwidget' => 1,
			)
		);

		update_option(
			'sidebars_widgets',
			array(
				'wp_inactive_widgets' => array(),
				'sidebar-1'           => array( 'search-2' ),
			)
		);

		wp_widgets_init();
		$wp_registered_widgets['search-2']['callback'][0] = new \WP_Widget_Search();
		$wp_registered_widgets['search-2']['params'][0]   = array( 'number' => 2 );

		( new Database_Repository( $this->pll_model->languages ) )->find_all(); // Register the widget title string and add sanitization hooks.

		$this->assertSame( '', apply_filters( 'pll_sanitize_string_translation', $string, 'Widget title', 'Widget', 'My Title', '' ) );
	}

	/**
	 * @testWith ["the_boss"]
	 *           ["not_the_boss"]
	 *
	 * @param string $user The user to set as current user.
	 * @return void
	 */
	public function test_sanitize_already_existing_unsafe_translation( $user ) {
		( new Database_Repository( $this->pll_model->languages ) )->find_all(); // Register the widget title string.

		wp_set_current_user( self::${$user}->ID );

		$this->assertSame( '<script>alert(\'heck\');</script>', apply_filters( 'pll_sanitize_string_translation', '<script>alert(\'heck\');</script>', 'widget_title', 'test', 'test', '<script>alert(\'heck\');</script>' ) );
	}
}
