<?php
/**
 * @package Polylang
 */

/**
 * Class to manage taxonomies list option.
 *
 * @since 3.7
 */
class PLL_Taxonomies_List_Option extends PLL_List_Option {
	/**
	 * Returns non-core, public taxonomies.
	 *
	 * @since 3.7
	 *
	 * @return string[] Object type names list.
	 */
	protected function get_object_types(): array {
		$public_taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ) );

		return array_diff( $public_taxonomies, get_taxonomies( array( '_pll' => true ) ) );
	}
}
