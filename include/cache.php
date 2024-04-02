<?php
/**
 * @package Polylang
 */

/**
 * An extremely simple non persistent cache system.
 *
 * @since 1.7
 *
 * @template TCacheData
 */
class PLL_Cache {
	/**
	 * Current site id.
	 *
	 * @var int
	 */
	protected $blog_id;

	/**
	 * The cache container.
	 *
	 * @var array
	 *
	 * @phpstan-var array<int, array<non-empty-string, TCacheData>>
	 */
	protected $cache = array();

	/**
	 * Constructor.
	 *
	 * @since 1.7
	 */
	public function __construct() {
		$this->blog_id = get_current_blog_id();
		add_action( 'switch_blog', array( $this, 'switch_blog' ) );
	}

	/**
	 * Called when switching blog.
	 *
	 * @since 1.7
	 *
	 * @param int $new_blog_id New blog ID.
	 * @return void
	 */
	public function switch_blog( $new_blog_id ) {
		$this->blog_id = $new_blog_id;
	}

	/**
	 * Adds a value in cache.
	 *
	 * @since 1.7
	 * @since 3.6 Returns the cached value.
	 *
	 * @param string $key  Cache key.
	 * @param mixed  $data The value to add to the cache.
	 * @return mixed
	 *
	 * @phpstan-param non-empty-string $key
	 * @phpstan-param TCacheData $data
	 * @phpstan-return TCacheData
	 */
	public function set( $key, $data ) {
		$this->cache[ $this->blog_id ][ $key ] = $data;

		return $data;
	}

	/**
	 * Returns value from cache.
	 *
	 * @since 1.7
	 *
	 * @param string $key Cache key.
	 * @return mixed
	 *
	 * @phpstan-param non-empty-string $key
	 * @phpstan-return TCacheData|false
	 */
	public function get( $key ) {
		return isset( $this->cache[ $this->blog_id ][ $key ] ) ? $this->cache[ $this->blog_id ][ $key ] : false;
	}

	/**
	 * Cleans the cache (for this blog only).
	 *
	 * @since 1.7
	 *
	 * @param string $key Optional. Cache key. An empty string to clean the whole cache for the current blog.
	 *                    Default is an empty string.
	 * @return void
	 */
	public function clean( $key = '' ) {
		if ( '' === $key ) {
			unset( $this->cache[ $this->blog_id ] );
		} else {
			unset( $this->cache[ $this->blog_id ][ $key ] );
		}
	}

	/**
	 * Generates and returns a "unique" cache key, depending on `$data` and prefixed by `$prefix`.
	 *
	 * @since 3.6
	 *
	 * @param string              $prefix String to prefix the cache key.
	 * @param string|array|object $data   Data.
	 * @return string
	 *
	 * @phpstan-param non-empty-string $prefix
	 * @phpstan-return non-empty-string
	 */
	public function get_unique_key( string $prefix, $data ): string {
		/** @var scalar */
		$serialized = maybe_serialize( $data );
		return $prefix . md5( (string) $serialized );
	}
}
