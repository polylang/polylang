<?php
/**
 * @package Polylang
 */

/**
 * Class defining post types list option.
 *
 * @since 3.7
 */
class PLL_Post_Types_List_Option extends PLL_Abstract_Object_Types_List_Option {
	/**
	 * Returns non-core, public post types.
	 *
	 * @since 3.7
	 *
	 * @return string[] Object type names list.
	 */
	protected function get_object_types(): array {
		return get_post_types( array( 'public' => true, '_builtin' => false ) );
	}
}
