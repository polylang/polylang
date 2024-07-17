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
	 * Constructor: setups properties.
	 *
	 * @since 3.1
	 *
	 * @param object $polylang The Polylang object.
	 */
	public function __construct( &$polylang ) {
		$this->model      = &$polylang->model;
		$this->curlang    = &$polylang->pref_lang;
		$this->taxonomies = $this->model->get_translated_taxonomies();
	}

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
				add_filter( 'manage_' . $taxonomy . '_custom_column', array( $this, 'term_column' ), 10, 3 );
			}
		}
	}

	/**
	 * Identifies the default term in the terms list table to disable the language dropdown in JS.
	 *
	 * @since 3.1
	 *
	 * @param  string $out     The output.
	 * @param  string $column  The custom column's name.
	 * @param  int    $term_id The term id.
	 * @return string          The HTML string.
	 */
	public function term_column( $out, $column, $term_id ) {
		if ( $column === $this->get_first_language_column() && $this->is_default_term( $term_id ) ) {
			$out .= sprintf( '<div class="hidden" id="default_cat_%1$d">%1$d</div>', intval( $term_id ) );
		}

		return $out;
	}

	/**
	 * Returns the first language column in the posts, pages and media library tables.
	 *
	 * @since 0.9
	 *
	 * @return string First language column name.
	 */
	protected function get_first_language_column() {
		$columns = array();

		foreach ( $this->model->get_languages_list() as $language ) {
			$columns[] = 'language_' . $language->slug;
		}

		return empty( $columns ) ? '' : reset( $columns );
	}
}
