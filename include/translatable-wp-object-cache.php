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
	 * @param array $args {
	 *   Array of arguments to add data to the cache.
	 *   @type int|string $key   The cache key to use for retrieval later.
	 *   @type mixed      $data  The data to add to the cache.
	 *   @type string     $group Optional. The group to add the cache to. Default empty.
	 * }
	 * @return bool Whether or not data has been added.
	 *
	 * @phpstan-param array{key: int|string, data: mixed, group?: non-empty-string} $args
	 */
	public function add( $args ) {
		return wp_cache_add( $args['key'], $args['data'], isset( $args['group'] ) ? $args['group'] : '' );
	}

	/**
	 * Saves data to the WordPress cache.
	 *
	 * @since 3.4
	 *
	 * @param array $args {
	 *   Array of arguments to save data to the cache.
	 *   @type int|string $key   The cache key to use for retrieval later.
	 *   @type mixed      $data  The data to store in the cache.
	 *   @type string     $group Optional. The group to add the cache to. Default empty.
	 * }
	 * @return bool Whether or not data has been saved.
	 *
	 * @phpstan-param array{key: int|string, data: mixed, group?: non-empty-string} $args
	 */
	public function set( $args ) {
		return wp_cache_set( $args['key'], $args['data'], isset( $args['group'] ) ? $args['group'] : '' );
	}

	/**
	 * Retrieves the cache contents from the WordPress cache.
	 *
	 * @since 3.4
	 *
	 * @param array $args {
	 *    Array of arguments to get data from the cache.
	 *   @type int|string $key   The key under which the cache contents are stored.
	 *   @type string     $group Optional. Where the cache contents are grouped. Default empty.
	 * }
	 * @return mixed Array of object IDs (could be anything, like post or term for instance) if data has been cached.
	 *
	 * @phpstan-param array{key: int|string, group?: string} $args
	 */
	public function get( $args ) {
		return wp_cache_get( $args['key'], isset( $args['group'] ) ? $args['group'] : '' );
	}

	/**
	 * Sets the last changed time for the current cache type group.
	 *
	 * @since 3.4
	 *
	 * @return bool Whether or not last change has been set.
	 */
	public function set_last_changed() {
		return wp_cache_set( 'last_changed', microtime(), $this->cache_type );
	}

	/**
	 * Gets last changed date for the current cache type group.
	 *
	 * @since 3.4
	 *
	 * @return string UNIX timestamp indicating the last change.
	 *
	 * @phpstan-return non-empty-string
	 */
	public function get_last_changed() {
		/**
		 * @phpstan-var non-empty-string
		 */
		return wp_cache_get_last_changed( $this->cache_type );
	}
}
