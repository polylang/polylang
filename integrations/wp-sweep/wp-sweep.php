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
		add_filter( 'wp_sweep_excluded_termids', array( $this, 'wp_sweep_excluded_termids' ), 0 );
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

	/**
	 * Add the translation of the default taxonomy terms and our language terms to the excluded terms.
	 *
	 * @since 2.9
	 *
	 * @param array $excluded_term_ids List of term ids excluded from sweeping.
	 * @return array
	 */
	public function wp_sweep_excluded_termids( $excluded_term_ids ) {
		// We got a list of excluded terms (defaults and parents). Let exclude their translations too.
		$_term_ids = array();

		foreach ( $excluded_term_ids as $excluded_term_id ) {
			$_term_ids = array_merge( $_term_ids, array_values( pll_get_term_translations( $excluded_term_id ) ) );
		}

		$excluded_term_ids = array_merge( $excluded_term_ids, $_term_ids );

		// Add the terms of our languages.
		$excluded_term_ids = array_merge(
			$excluded_term_ids,
			pll_languages_list( array( 'fields' => 'term_id' ) ),
			pll_languages_list( array( 'fields' => 'tl_term_id' ) )
		);

		return array_unique( $excluded_term_ids );
	}
}
