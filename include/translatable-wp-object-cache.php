<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class to handle WordPress cache mechanism into `PLL_Translatable_Object`.
 *
 * @since 3.4
 */
class PLL_Translatable_WP_Object_Cache extends PLL_Translatable_Abstract_Object_Cache {
	/**
	 * Adds data to the WordPress cache without overriding.
	 *
	 * @since 3.4
	 *
	 * @param array $args Array of arguments to add data to the cache.
	 * @return bool Whether or not data has been added.
	 */
	public function add( $args ) {
		// TODO: implement here.
	}

	/**
	 * Saves data to the WordPress cache.
	 *
	 * @since 3.4
	 *
	 * @param array $args Array of arguments to save data to the cache.
	 * @return bool Whether or not data has been saved.
	 */
	public function set( $args ) {
		// TODO: implement here.
	}

	/**
	 * Retrieves the cache contents from the WordPress cache.
	 *
	 * @since 3.4
	 *
	 * @param array $args Array of arguments to get data from the cache.
	 * @return int[] Array of object IDs (could be anything, like post or term for instance).
	 */
	public function get( $args ) {
		// TODO: implement here.
	}

	/**
	 * Sets the last changed time for the current cache type group.
	 *
	 * @since 3.4
	 *
	 * @return bool Whether or not last change has been set.
	 */
	public function set_last_changed() {
		// TODO: implement here.
	}

	/**
	 * Gets last changed date for the current cache type group.
	 *
	 * @since 3.4
	 *
	 * @return mixed Anything indicating the last change (e.g UNIX timestamp, DateTime...).
	 */
	public function get_last_changed() {
		// TODO: implement here.
	}
}
