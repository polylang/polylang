<?php
/**
 * @package Polylang
 */

/**
 * Manages filters and actions related to default terms.
 *
 * @since 3.1
 * @since 3.7 Extends `PLL_Default_Term`, most of the code is moved to it.
 */
class PLL_Admin_Default_Term extends PLL_Default_Term {

	/**
	 * Setups filters and actions needed.
	 *
	 * @since 3.1
	 *
	 * @return void
	 */
	public function add_hooks() {
		parent::add_hooks();

		foreach ( $this->taxonomies as $taxonomy ) {
			if ( 'category' === $taxonomy ) {
				// Adds the language column in the 'Terms' table.
				add_filter( 'pll_first_language_term_column', array( $this, 'first_language_column' ), 10, 2 );
			}
		}
	}

	/**
	 * Identifies the default term in the terms list table to disable the language dropdown in JS.
	 *
	 * @since 3.7
	 *
	 * @param  string $out     The output.
	 * @param  int    $term_id The term id.
	 * @return string          The HTML string.
	 */
	public function first_language_column( $out, $term_id ) {
		if ( $this->is_default_term( $term_id ) ) {
			$out .= sprintf( '<div class="hidden" id="default_cat_%1$d">%1$d</div>', intval( $term_id ) );
		}

		return $out;
	}
}
