<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Option\Business;

use WP_Error;
use WP_Syntex\Polylang\Options\Option\Primitive\List_Type;
use WP_Syntex\Polylang\Options\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining object types list option.
 *
 * @since 3.7
 */
abstract class Abstract_Object_Types extends List_Type {
	/**
	 * List of non-core, public object types.
	 *
	 * @var string[]|null
	 */
	private $object_types;

	/**
	 * Sanitizes option's value.
	 * Can return a `WP_Error` object in case of blocking sanitization error: the value must be rejected then.
	 *
	 * @since 3.7
	 *
	 * @param array   $value   Value to filter.
	 * @param Options $options All options.
	 * @return array|WP_Error
	 */
	protected function sanitize( $value, Options $options ) {
		$value = parent::sanitize( $value, $options );

		if ( is_wp_error( $value ) ) {
			// Blocking sanitization error.
			return $value;
		}

		if ( null === $this->object_types ) {
			$this->object_types = $this->get_object_types();
		}

		/** @var array $value */
		return array_intersect( $value, $this->object_types );
	}

	/**
	 * Returns non-core, public object types.
	 *
	 * @since 3.7
	 *
	 * @return string[] Object type names list.
	 */
	abstract protected function get_object_types(): array;
}
