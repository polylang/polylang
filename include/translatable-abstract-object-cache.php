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
	protected $cache_type;
	protected $cache_object;

	public function __construct( $cache_object = null ) {
		$this->cache_object = $cache_object;
	}

	public function register_type( $cache_type ) {
		$this->cache_type = $cache_type;

		return $this;
	}

	public function add( $args ) {}

	public function set( $args ) {}

	public function get( $args ) {}

	public function set_last_changed() {}

	public function get_last_changed() {}

	public function filter_add_args( $args, $object_ids, $type, $tax_language, $tax_to_cache ) {
		// TODO: implement here.
	}

	public function filter_set_args( $args, $object_ids, $type, $tax_language, $tax_to_cache ) {
		// TODO: implement here.
	}

	public function filter_get_args( $args, $object_ids, $type, $tax_language, $tax_to_cache ) {
		// TODO: implement here.
	}
}
