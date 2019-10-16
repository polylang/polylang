<?php
/**
 * Used to Mock the functions from WordPress 'pluggable' API
 *
 * How it works :
 * - Functions in this file are declared before WordPress is loaded, so they will replace functions with the same name.
 * - Each of these functions redirects towards the Pluggeabble_Functions object, which can be mocked.
 * - By default, this object contains a copy of the pluggeable.php functions, so they will behave like in production if they aren't mocked.
 * - The Pluggable_Functions object is instantiated as a dependency of PLL_UnitTestCase, that's where the mock can be placed.
 *
 * For example, {@see PLL_Bulk_Translate_Test::test_handle_bulk_action_with_varying_parameters()}
 */

/**
 * Overrides the pluggable WP function wp_get_current_user()
 */
function wp_get_current_user() {
	return Pluggable_Functions_Container::get_instance()->get_current_user();
}

/**
 * Class Pluggable_Functions_Container
 *
 * Hold a reference to the instance of our Pluggable_Functions, and makes it accessible in a static context.
 */
class Pluggable_Functions_Container {
	/**
	 * A reference to the current instance.
	 *
	 * @var Pluggable_Functions
	 */
	private static $instance = null;

	/**
	 * Retrieves the current instance of Pluggable_Functions
	 *
	 * @return Pluggable_Functions
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			return new Pluggable_Functions();
		} else {
			return self::$instance;
		}
	}

	/**
	 * Replace the instance of Pluggable_Functions currently in use.
	 *
	 * @param Pluggable_Functions $instance
	 */
	public static function set_instance( $instance ) {
		self::$instance = $instance;
	}
}

/**
 * Class Pluggable_Functions
 *
 * Basically a copy of functions from WordPress' wp-includes/pluggable.php
 */
class Pluggable_Functions {
	/**
	 * The constructor has to be triggered in order to automatically replace the instance in the container
	 */
	public function __construct() {
		Pluggable_Functions_Container::set_instance( $this );
	}

	/**
	 * Returns the current user, using the WP function by default bebavior
	 *
	 * @return WP_User
	 */
	public function get_current_user() {
		return _wp_get_current_user();
	}
}
