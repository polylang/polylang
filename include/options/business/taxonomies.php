<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining taxonomies list option.
 *
 * @since 3.7
 */
class Taxonomies extends Abstract_Object_Types {
	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Optional. Option value.
	 *
	 * @phpstan-param non-falsy-string $key
	 */
	public function __construct( string $key, $value = null ) {
		parent::__construct( $key, $value, array(), 'string' );
	}

	/**
	 * Returns non-core taxonomies.
	 *
	 * @since 3.7
	 *
	 * @return string[] Object type names list.
	 *
	 * @phpstan-return array<non-falsy-string>
	 */
	protected function get_object_types(): array {
		$public_taxonomies = get_taxonomies( array( '_builtin' => false ) );
		/** @phpstan-var array<non-falsy-string> */
		return array_diff( $public_taxonomies, get_taxonomies( array( '_pll' => true ) ) );
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 * @since 3.7
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( 'List of taxonomies to translate.', 'polylang' );
	}
}
