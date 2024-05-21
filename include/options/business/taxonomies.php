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
	 * Returns non-core, public taxonomies.
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
}
