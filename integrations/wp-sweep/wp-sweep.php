<?php
/**
 * @package Polylang
 */

/**
 * Manages the compatibility with WP Sweep.
 *
 * @since 2.8
 */
class PLL_WP_Sweep {
	/**
	 * Setups actions.
	 *
	 * @since 2.8
	 */
	public function init() {
		add_filter( 'wp_sweep_excluded_taxonomies', array( $this, 'wp_sweep_excluded_taxonomies' ) );
	}

	/**
	 * Add 'term_language' and 'term_translations' to excluded taxonomies otherwise terms loose their language and translation group.
	 *
	 * @since 2.0
	 *
	 * @param array $excluded_taxonomies List of taxonomies excluded from sweeping.
	 * @return array
	 */
	public function wp_sweep_excluded_taxonomies( $excluded_taxonomies ) {
		return array_merge( $excluded_taxonomies, array( 'term_language', 'term_translations' ) );
	}
}
