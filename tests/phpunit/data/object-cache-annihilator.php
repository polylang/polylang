<?php
/**
 * Object Cache Annihilator.
 *
 * @package WP_Syntex\Object_Cache_Annihilator
 *
 * A simple file-based object cache implementation for testing purposes.
 * Stores cache data in the filesystem with support for groups and expiration.
 */

namespace WP_Syntex;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if WordPress is already loaded
if ( function_exists( 'wp_cache_add' ) ) {
	return;
}

use WP_Object_Cache;

/**
 * Cache object taking place of `WP_Object_Cache` and allows for easy testing of cache operations.
 *
 * @since 0.1.0
 */
class Object_Cache_Annihilator {
	/**
	 * Directory where cache files are stored.
	 *
	 * @var string
	 */
	private $cache_dir;

	/**
	 * Prefix for cache files.
	 *
	 * @var string
	 */
	private $cache_prefix;

	/**
	 * In-memory cache array.
	 *
	 * @var array
	 */
	private $cache = array();

	/**
	 * Number of successful cache retrievals.
	 *
	 * @var int
	 */
	private $cache_hits = 0;

	/**
	 * Number of failed cache retrievals.
	 *
	 * @var int
	 */
	private $cache_misses = 0;

	/**
	 * List of global cache groups.
	 *
	 * @var array
	 */
	private $global_groups = array();

	/**
	 * List of non-persistent cache groups.
	 *
	 * @var array
	 */
	private $non_persistent_groups = array();

	/**
	 * Whether the site is a multisite installation.
	 *
	 * @var bool
	 */
	private $multisite = false;

	/**
	 * Blog prefix for multisite installations.
	 *
	 * @var string
	 */
	private $blog_prefix = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->cache_dir = WP_CONTENT_DIR . '/object-cache/';
		$this->cache_prefix = 'wp_cache_';
		$this->multisite = is_multisite();
		$this->blog_prefix = $this->multisite ? get_current_blog_id() . ':' : '';

		if ( ! file_exists( $this->cache_dir ) ) {
			wp_mkdir_p( $this->cache_dir );
		}
	}

	/**
	 * Adds data to the cache if it doesn't already exist.
	 *
	 * @param string $key    The cache key.
	 * @param mixed  $data   The data to store.
	 * @param string $group  Optional. The cache group. Default 'default'.
	 * @param int    $expire Optional. When to expire the cache contents. Default 0 (no expiration).
	 * @return bool True if the data was added, false if it already exists.
	 */
	public function add( $key, $data, $group = 'default', $expire = 0 ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		if ( $this->get( $key, $group ) !== false ) {
			return false;
		}

		return $this->set( $key, $data, $group, $expire );
	}

	/**
	 * Sets data in the cache.
	 *
	 * @param string $key    The cache key.
	 * @param mixed  $data   The data to store.
	 * @param string $group  Optional. The cache group. Default 'default'.
	 * @param int    $expire Optional. When to expire the cache contents. Default 0 (no expiration).
	 * @return bool True on success, false on failure.
	 */
	public function set( $key, $data, $group = 'default', $expire = 0 ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		$cache_file = $this->get_cache_file_path( $key, $group );
		$cache_data = array(
			'value' => $data,
			'expires' => $expire ? time() + $expire : 0,
		);

		$this->cache[ $group ][ $key ] = $data;

		// Ensure directory exists
		$dir = dirname( $cache_file );
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		// Serialize with validation
		$serialized = serialize( $cache_data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		if ( false === $serialized ) {
			return false;
		}

		return file_put_contents( $cache_file, $serialized );
	}

	/**
	 * Retrieves data from the cache.
	 *
	 * @param string $key    The cache key.
	 * @param string $group  Optional. The cache group. Default 'default'.
	 * @param bool   $force  Optional. Whether to force an update of the local cache. Default false.
	 * @param bool   $found  Optional. Whether the key was found in the cache. Passed by reference.
	 * @return mixed|false The cache contents on success, false on failure.
	 */
	public function get( $key, $group = 'default', $force = false, &$found = null ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		// Check runtime cache first
		if ( ! $force && isset( $this->cache[ $group ][ $key ] ) ) {
			++$this->cache_hits;
			$found = true;
			return $this->cache[ $group ][ $key ];
		}

		$cache_file = $this->get_cache_file_path( $key, $group );

		if ( ! file_exists( $cache_file ) ) {
			++$this->cache_misses;
			$found = false;
			return false;
		}

		$file_contents = file_get_contents( $cache_file );
		if ( false === $file_contents ) {
			++$this->cache_misses;
			$found = false;
			return false;
		}

		$data = unserialize( $file_contents ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize

		// Validate unserialized data structure
		if ( ! is_array( $data ) || ! isset( $data['value'] ) ) {
			if ( file_exists( $cache_file ) ) {
				unlink( $cache_file );
			}
			++$this->cache_misses;
			$found = false;
			return false;
		}

		if ( $data['expires'] && time() > $data['expires'] ) {
			unlink( $cache_file );
			++$this->cache_misses;
			$found = false;
			return false;
		}

		$this->cache[ $group ][ $key ] = $data['value'];
		++$this->cache_hits;
		$found = true;
		return $data['value'];
	}

	/**
	 * Removes data from the cache.
	 *
	 * @param string $key   The cache key.
	 * @param string $group Optional. The cache group. Default 'default'.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $key, $group = 'default' ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		$cache_file = $this->get_cache_file_path( $key, $group );

		if ( isset( $this->cache[ $group ][ $key ] ) ) {
			unset( $this->cache[ $group ][ $key ] );
		}

		if ( file_exists( $cache_file ) ) {
			return unlink( $cache_file );
		}

		return false;
	}

	/**
	 * Replaces data in the cache if it already exists.
	 *
	 * @param string $key    The cache key.
	 * @param mixed  $data   The data to store.
	 * @param string $group  Optional. The cache group. Default 'default'.
	 * @param int    $expire Optional. When to expire the cache contents. Default 0 (no expiration).
	 * @return bool True if the data was replaced, false if it doesn't exist.
	 */
	public function replace( $key, $data, $group = 'default', $expire = 0 ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		if ( false === $this->get( $key, $group ) ) {
			return false;
		}

		return $this->set( $key, $data, $group, $expire );
	}

	/**
	 * Clears all cached data.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function flush() {
		$this->cache = array();
		$this->cache_hits = 0;
		$this->cache_misses = 0;

		$this->cleanup_directory( $this->cache_dir );

		return empty( glob( $this->cache_dir . '*' ) );
	}

	/**
	 * Kills the object cache, disables external object cache, and replaces it with WordPress one.
	 *
	 * @global WP_Object_Cache $wp_object_cache
	 */
	public function die() {
		global $wp_object_cache;

		$this->flush();
		$wp_object_cache = new WP_Object_Cache();
		wp_using_ext_object_cache( false );
	}

	/**
	 * Resurrects the object cache from the ashes and re-enables external object cache.
	 *
	 * @global WP_Object_Cache $wp_object_cache
	 */
	public function resurrect() {
		global $wp_object_cache;

		$wp_object_cache = $this;
		wp_using_ext_object_cache( true );
	}

	/**
	 * Recursively cleanup a directory by removing all its contents.
	 *
	 * @param string $dir Directory path to clean.
	 */
	private function cleanup_directory( $dir ) {
		$files = glob( $dir . '*' );
		if ( $files ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					unlink( $file );
				} elseif ( is_dir( $file ) ) {
					$this->cleanup_directory( $file . '/' );
					rmdir( $file );
				}
			}
		}
	}

	/**
	 * Adds groups to the list of global groups.
	 *
	 * @param string|array $groups A group or an array of groups to add.
	 */
	public function add_global_groups( $groups ) {
		$groups = (array) $groups;
		$this->global_groups = array_merge( $this->global_groups, $groups );
		$this->global_groups = array_unique( $this->global_groups );
	}

	/**
	 * Adds groups to the list of non-persistent groups.
	 *
	 * @param string|array $groups A group or an array of groups to add.
	 */
	public function add_non_persistent_groups( $groups ) {
		$groups = (array) $groups;
		$this->non_persistent_groups = array_merge( $this->non_persistent_groups, $groups );
		$this->non_persistent_groups = array_unique( $this->non_persistent_groups );
	}

	/**
	 * Increments numeric cache item's value.
	 *
	 * @param string $key    The cache key.
	 * @param int    $offset Optional. The amount by which to increment the item's value. Default 1.
	 * @param string $group  Optional. The cache group. Default 'default'.
	 * @return int|false The item's new value on success, false on failure.
	 */
	public function incr( $key, $offset = 1, $group = 'default' ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		$value = $this->get( $key, $group );
		if ( false === $value ) {
			return false;
		}

		if ( ! is_numeric( $value ) ) {
			$value = 0;
		}

		$value += $offset;
		if ( $value < 0 ) {
			$value = 0;
		}

		$this->set( $key, $value, $group );
		return $value;
	}

	/**
	 * Decrements numeric cache item's value.
	 *
	 * @param string $key    The cache key.
	 * @param int    $offset Optional. The amount by which to decrement the item's value. Default 1.
	 * @param string $group  Optional. The cache group. Default 'default'.
	 * @return int|false The item's new value on success, false on failure.
	 */
	public function decr( $key, $offset = 1, $group = 'default' ) {
		return $this->incr( $key, -$offset, $group );
	}

	/**
	 * Switches the internal blog ID.
	 *
	 * @param int $blog_id Blog ID.
	 */
	public function switch_to_blog( $blog_id ) {
		$this->blog_prefix = $this->multisite ? $blog_id . ':' : '';
	}

	/**
	 * Retrieves multiple values from the cache in one call.
	 *
	 * @param array  $keys  Array of keys to retrieve.
	 * @param string $group Optional. The cache group. Default 'default'.
	 * @param bool   $force Optional. Whether to force an update of the local cache. Default false.
	 * @return array Array of values.
	 */
	public function get_multiple( $keys, $group = 'default', $force = false ) {
		$values = array();
		foreach ( $keys as $key ) {
			$values[ $key ] = $this->get( $key, $group, $force );
		}
		return $values;
	}

	/**
	 * Sets multiple values to the cache in one call.
	 *
	 * @param array  $items  Array of key => value pairs to store.
	 * @param string $group  Optional. The cache group. Default 'default'.
	 * @param int    $expire Optional. When to expire the cache contents. Default 0 (no expiration).
	 * @return bool True on success, false on failure.
	 */
	public function set_multiple( $items, $group = 'default', $expire = 0 ) {
		$success = true;
		foreach ( $items as $key => $value ) {
			if ( ! $this->set( $key, $value, $group, $expire ) ) {
				$success = false;
			}
		}
		return $success;
	}

	/**
	 * Deletes multiple values from the cache in one call.
	 *
	 * @param array  $keys  Array of keys to delete.
	 * @param string $group Optional. The cache group. Default 'default'.
	 * @return bool True on success, false on failure.
	 */
	public function delete_multiple( $keys, $group = 'default' ) {
		$success = true;
		foreach ( $keys as $key ) {
			if ( ! $this->delete( $key, $group ) ) {
				$success = false;
			}
		}
		return $success;
	}

	/**
	 * Adds multiple values to the cache in one call.
	 *
	 * @param array  $items  Array of key => value pairs to store.
	 * @param string $group  Optional. The cache group. Default 'default'.
	 * @param int    $expire Optional. When to expire the cache contents. Default 0 (no expiration).
	 * @return bool True on success, false on failure.
	 */
	public function add_multiple( $items, $group = 'default', $expire = 0 ) {
		$success = true;
		foreach ( $items as $key => $value ) {
			if ( ! $this->add( $key, $value, $group, $expire ) ) {
				$success = false;
			}
		}
		return $success;
	}

	/**
	 * Clears the in-memory runtime cache.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function flush_runtime() {
		$this->cache = array();
		$this->cache_hits = 0;
		$this->cache_misses = 0;
		return true;
	}

	/**
	 * Clears the cache for a specific group.
	 *
	 * @param string $group Optional. The cache group. Default 'default'.
	 * @return bool True on success, false on failure.
	 */
	public function flush_group( $group ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		$group_dir = $this->cache_dir . $group . '/';
		if ( ! file_exists( $group_dir ) ) {
			return true;
		}

		$files = glob( $group_dir . $this->cache_prefix . '*' );
		if ( $files ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					unlink( $file );
				}
			}
		}

		if ( isset( $this->cache[ $group ] ) ) {
			unset( $this->cache[ $group ] );
		}

		return true;
	}

	/**
	 * Gets the file path for a cache key.
	 *
	 * @param string $key   The cache key.
	 * @param string $group The cache group.
	 * @return string The cache file path.
	 */
	private function get_cache_file_path( $key, $group ) {
		$group_dir = $this->cache_dir . $group . '/';
		if ( ! file_exists( $group_dir ) ) {
			wp_mkdir_p( $group_dir );
		}
		// Sanitize key more strictly
		$safe_key = preg_replace( '/[^a-zA-Z0-9_-]/', '_', $key );
		return $group_dir . $this->cache_prefix . $safe_key . '.cache';
	}
}

/**
 * Initializes the object cache.
 *
 * @global WP_Object_Cache $wp_object_cache WordPress Object Cache
 */
function wp_cache_init() { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
	global $wp_object_cache;

	if ( ! ( $wp_object_cache instanceof Object_Cache_Annihilator ) ) {
		$wp_object_cache = new Object_Cache_Annihilator();
	}
}

/*
 * phpcs:disable Squiz.Commenting.FunctionComment.WrongStyle
 * phpcs:disable Squiz.Commenting.FunctionComment.Missing
 *
 * Core cache functions implementation.
 */

function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->add( $key, $data, $group, $expire );
}

function wp_cache_add_global_groups( $groups ) {
	global $wp_object_cache;
	$wp_object_cache->add_global_groups( $groups );
}

function wp_cache_add_non_persistent_groups( $groups ) {
	global $wp_object_cache;
	$wp_object_cache->add_non_persistent_groups( $groups );
}

function wp_cache_incr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->incr( $key, $offset, $group );
}

function wp_cache_decr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->decr( $key, $offset, $group );
}

function wp_cache_switch_to_blog( $blog_id ) {
	global $wp_object_cache;
	$wp_object_cache->switch_to_blog( $blog_id );
}

function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
	global $wp_object_cache;
	return $wp_object_cache->get( $key, $group, $force, $found );
}

function wp_cache_get_multiple( $keys, $group = '', $force = false ) {
	global $wp_object_cache;
	return $wp_object_cache->get_multiple( $keys, $group, $force );
}

function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->set( $key, $data, $group, $expire );
}

function wp_cache_set_multiple( $items, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->set_multiple( $items, $group, $expire );
}

function wp_cache_delete( $key, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->delete( $key, $group );
}

function wp_cache_delete_multiple( $keys, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->delete_multiple( $keys, $group );
}

function wp_cache_add_multiple( $items, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->add_multiple( $items, $group, $expire );
}

function wp_cache_flush_runtime() {
	global $wp_object_cache;
	return $wp_object_cache->flush_runtime();
}

function wp_cache_flush_group( $group ) {
	global $wp_object_cache;
	return $wp_object_cache->flush_group( $group );
}

function wp_cache_close() {
	return true;
}
