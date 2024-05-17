<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining post types list option.
 *
 * @since 3.7
 */
class Post_Types extends Abstract_Object_Types {
	/**
	 * Returns non-core, public post types.
	 *
	 * @since 3.7
	 *
	 * @return string[] Object type names list.
	 *
	 * @phpstan-return array<non-falsy-string>
	 */
	protected function get_object_types(): array {
		/** @phpstan-var array<non-falsy-string> */
		return get_post_types( array( '_builtin' => false ) );
	}
}
