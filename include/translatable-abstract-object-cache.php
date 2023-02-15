<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class to handle cache mechanism into `PLL_Translatable_Object`.
 *
 * @since 3.4
 */
abstract class PLL_Translatable_Abstract_Object_Cache {
	/**
	 * Cache type of the current object.
	 *
	 * @var string|null
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $cache_type;

	/**
	 * Object to handle cache if needed.
	 *
	 * @var object|null
	 */
	protected $cache_object;

	/**
	 * Constructor.
	 *
	 * @since 3.4
	 *
	 * @param object|null $cache_object Optional. Cache object to use inside `PLL_Translatable_Abstract_Object_Cache`. Default to `null`.
	 */
	public function __construct( $cache_object = null ) {
		$this->cache_object = $cache_object;
	}

	/**
	 * Registers the type of cache the current object will handle.
	 * If an other type has already been registered, returns a new object with the new type registered.
	 *
	 * @since 3.4
	 *
	 * @param string $cache_type Cache type to use.
	 * @return self
	 *
	 * @phpstan-param non-empty-string $cache_type
	 */
	public function register_type( $cache_type ) {
		if ( ! empty( $this->cache_type ) && $cache_type !== $this->cache_type ) {
			return ( new $this( $this->cache_object ) )->register_type( $cache_type );
		}

		$this->cache_type = $cache_type;

		return $this;
	}

	/**
	 * Adds data to the cache without overriding.
	 *
	 * @since 3.4
	 *
	 * @param array $args Array of arguments to add data to the cache.
	 * @return bool Whether or not data has been added.
	 */
	abstract public function add( $args );

	/**
	 * Saves data to the cache.
	 *
	 * @since 3.4
	 *
	 * @param array $args Array of arguments to save data to the cache.
	 * @return bool Whether or not data has been saved.
	 */
	abstract public function set( $args );

	/**
	 * Retrieves the cache contents from the cache.
	 *
	 * @since 3.4
	 *
	 * @param array $args Array of arguments to get data from the cache.
	 * @return mixed Array of object IDs (could be anything, like post or term for instance) if data has been cached.
	 */
	abstract public function get( $args );

	/**
	 * Sets the last changed time for the current cache type group.
	 *
	 * @since 3.4
	 *
	 * @return bool Whether or not last change has been set.
	 */
	abstract public function set_last_changed();

	/**
	 * Gets last changed date for the current cache type group.
	 *
	 * @since 3.4
	 *
	 * @return string UNIX timestamp indicating the last change.
	 */
	abstract public function get_last_changed();

	/**
	 * Filters arguments to use in `self::add()`.
	 *
	 * @since 3.4
	 *
	 * @param array    $args         Array of arguments to add data to the cache.
	 * @param int[]    $object_ids   List of object IDs.
	 * @param string   $type         Identifier that must be unique for each type of content.
	 * @param string   $tax_language Taxonomy name for the languages.
	 * @param string[] $tax_to_cache List of taxonomies to cache.
	 * @return array Filtered array of arguments to add data to the cache.
	 *
	 * @phpstan-param non-empty-string $type
	 * @phpstan-param non-empty-string $tax_language
	 * @phpstan-param list<non-empty-string> $tax_to_cache
	 */
	public function filter_add_args( $args, $object_ids, $type, $tax_language, $tax_to_cache ) {
		/**
		 * Filters the arguments passed to `PLL_Translatable_Abstract_Object_Cache::add()`.
		 *
		 * @since 3.4
		 *
		 * @param array    $args         Array of arguments to add data to the cache.
		 * @param int[]    $object_ids   List of object IDs.
		 * @param string   $type         Identifier that must be unique for each type of content.
		 * @param string   $tax_language Taxonomy name for the languages.
		 * @param string[] $tax_to_cache List of taxonomies to cache.
		 */
		return apply_filters( 'pll_pre_add_to_object_cache', $args, $object_ids, $type, $tax_language, $tax_to_cache );
	}

	/**
	 * Filters arguments to use in `self::set()`.
	 *
	 * @since 3.4
	 *
	 * @param array    $args         Array of arguments to save data to the cache.
	 * @param int[]    $object_ids   List of object IDs.
	 * @param string   $type         Identifier that must be unique for each type of content.
	 * @param string   $tax_language Taxonomy name for the languages.
	 * @param string[] $tax_to_cache List of taxonomies to cache.
	 * @return array Filtered array of arguments to save data to the cache.
	 *
	 * @phpstan-param non-empty-string $type
	 * @phpstan-param non-empty-string $tax_language
	 * @phpstan-param list<non-empty-string> $tax_to_cache
	 */
	public function filter_set_args( $args, $object_ids, $type, $tax_language, $tax_to_cache ) {
		/**
		 * Filters the arguments passed to `PLL_Translatable_Abstract_Object_Cache::set()`.
		 *
		 * @since 3.4
		 *
		 * @param array    $args         Array of arguments to save data to the cache.
		 * @param int[]    $object_ids   List of object IDs.
		 * @param string   $type         Identifier that must be unique for each type of content.
		 * @param string   $tax_language Taxonomy name for the languages.
		 * @param string[] $tax_to_cache List of taxonomies to cache.
		 */
		return apply_filters( 'pll_pre_set_to_object_cache', $args, $object_ids, $type, $tax_language, $tax_to_cache );
	}

	/**
	 * Filters arguments to use in `self::get()`.
	 *
	 * @since 3.4
	 *
	 * @param array    $args         Array of arguments to get data from the cache.
	 * @param int[]    $object_ids   List of object IDs.
	 * @param string   $type         Identifier that must be unique for each type of content.
	 * @param string   $tax_language Taxonomy name for the languages.
	 * @param string[] $tax_to_cache List of taxonomies to cache.
	 * @return array Filtered array of arguments to get data from the cache.
	 *
	 * @phpstan-param non-empty-string $type
	 * @phpstan-param non-empty-string $tax_language
	 * @phpstan-param list<non-empty-string> $tax_to_cache
	 */
	public function filter_get_args( $args, $object_ids, $type, $tax_language, $tax_to_cache ) {
		/**
		 * Filters the arguments passed to `PLL_Translatable_Abstract_Object_Cache::get()`.
		 *
		 * @since 3.4
		 *
		 * @param array    $args         Array of arguments to get data from the cache.
		 * @param int[]    $object_ids   List of object IDs.
		 * @param string   $type         Identifier that must be unique for each type of content.
		 * @param string   $tax_language Taxonomy name for the languages.
		 * @param string[] $tax_to_cache List of taxonomies to cache.
		 */
		return apply_filters( 'pll_pre_set_to_object_cache', $args, $object_ids, $type, $tax_language, $tax_to_cache );
	}
}
