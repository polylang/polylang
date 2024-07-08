<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Model for taxonomies filtered by Polylang.
 *
 * @since 3.7
 */
class Filtered_Taxonomies {
	/**
	 * Return taxonomies that need to be filtered (post_format like).
	 *
	 * @since 1.7
	 * @since 3.7 Moved from `PLL_Model::get_filtered_taxonomies()` to `WP_Syntex\Polylang\Models\Filtered_Taxonomies::get_filtered_taxonomies()`.
	 *
	 * @param bool $filter True if we should return only valid registered taxonomies.
	 * @return string[] Array of registered taxonomy names.
	 */
	public function get_filtered_taxonomies( bool $filter = true ): array {
		if ( did_action( 'after_setup_theme' ) ) {
			static $taxonomies = null;
		}

		if ( empty( $taxonomies ) ) {
			$taxonomies = array( 'post_format' => 'post_format' );

			/**
			 * Filters the list of taxonomies not translatable but filtered by language.
			 * Includes only the post format by default
			 * The filter must be added soon in the WordPress loading process:
			 * in a function hooked to ‘plugins_loaded’ or directly in functions.php for themes.
			 *
			 * @since 1.7
			 *
			 * @param string[] $taxonomies  List of taxonomy names.
			 * @param bool     $is_settings True when displaying the list of custom taxonomies in Polylang settings.
			 */
			$taxonomies = apply_filters( 'pll_filtered_taxonomies', $taxonomies, false );
		}

		return $filter ? array_intersect( $taxonomies, get_taxonomies() ) : $taxonomies;
	}

	/**
	 * Returns true if Polylang filters this taxonomy per language.
	 *
	 * @since 1.7
	 * @since 3.7 Moved from `PLL_Model::is_filtered_taxonomy()` to `WP_Syntex\Polylang\Models\Filtered_Taxonomies::is_filtered_taxonomy()`.
	 *
	 * @param string|string[] $tax Taxonomy name or array of taxonomy names.
	 * @return bool
	 */
	public function is_filtered_taxonomy( $tax ): bool {
		$taxonomies = $this->get_filtered_taxonomies( false );
		return ( is_array( $tax ) && array_intersect( $tax, $taxonomies ) ) || in_array( $tax, $taxonomies );
	}

	/**
	 * Returns the query vars of all filtered taxonomies.
	 *
	 * @since 1.7
	 * @since 3.7 Moved from `PLL_Model::get_filtered_taxonomies_query_vars()` to `WP_Syntex\Polylang\Models\Filtered_Taxonomies::get_query_vars()`.
	 *
	 * @return string[]
	 */
	public function get_query_vars(): array {
		$query_vars = array();
		foreach ( $this->get_filtered_taxonomies() as $filtered_tax ) {
			$tax = get_taxonomy( $filtered_tax );
			if ( ! empty( $tax ) && is_string( $tax->query_var ) ) {
				$query_vars[] = $tax->query_var;
			}
		}
		return $query_vars;
	}
}
