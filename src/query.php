<?php
/**
 * @package Polylang
 */

/**
 * A class to manipulate the language query var in WP_Query
 *
 * @since 2.2
 */
class PLL_Query {
	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * @var WP_Query
	 */
	public $query;

	/**
	 * Constructor
	 *
	 * @since 2.2
	 *
	 * @param WP_Query  $query Reference to the WP_Query object.
	 * @param PLL_Model $model Instance of PLL_Model.
	 */
	public function __construct( &$query, &$model ) {
		$this->query = &$query;
		$this->model = &$model;
	}

	/**
	 * Checks if the query already includes a language taxonomy.
	 *
	 * @since 3.0
	 *
	 * @param array $qvars WP_Query query vars.
	 * @return bool
	 */
	protected function is_already_filtered( $qvars ) {
		if ( isset( $qvars['lang'] ) ) {
			return true;
		}

		if ( ! empty( $qvars['tax_query'] ) && is_array( $qvars['tax_query'] ) ) {
			foreach ( $qvars['tax_query'] as $tax_query ) {
				if ( isset( $tax_query['taxonomy'] ) && 'language' === $tax_query['taxonomy'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if translated taxonomy is queried
	 * Compatible with nested queries introduced in WP 4.1
	 *
	 * @see https://wordpress.org/support/topic/tax_query-bug
	 *
	 * @since 1.7
	 *
	 * @param array $tax_queries An array of tax queries.
	 * @return bool
	 */
	protected function have_translated_taxonomy( $tax_queries ) {
		if ( is_array( $tax_queries ) ) {
			foreach ( $tax_queries as $tax_query ) {
				if ( isset( $tax_query['taxonomy'] ) && $this->model->is_translated_taxonomy( $tax_query['taxonomy'] ) && ! ( isset( $tax_query['operator'] ) && 'NOT IN' === $tax_query['operator'] ) ) {
					return true;
				}

				// Nested queries
				elseif ( is_array( $tax_query ) && $this->have_translated_taxonomy( $tax_query ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get queried taxonomies
	 *
	 * @since 2.2
	 *
	 * @return array queried taxonomies
	 */
	public function get_queried_taxonomies() {
		return ! empty( $this->query->tax_query->queried_terms ) ? array_keys( wp_list_filter( $this->query->tax_query->queried_terms, array( 'operator' => 'NOT IN' ), 'NOT' ) ) : array();
	}

	/**
	 * Sets the language in query.
	 * Optimized for (and requires) WP 3.5+.
	 *
	 * @since 2.2
	 * @since 3.3 Accepts now an array of languages.
	 *
	 * @param PLL_Language|PLL_Language[] $languages Language object(s).
	 * @return void
	 */
	public function set_language( $languages ) {
		if ( ! is_array( $languages ) ) {
			$languages = array( $languages );
		}

		$tt_ids = array();
		foreach ( $languages as $language ) {
			$tt_ids[] = $language->get_tax_prop( 'language', 'term_taxonomy_id' );
		}

		// Defining directly the tax_query (rather than setting 'lang' avoids transforming the query by WP).
		$lang_query = array(
			'taxonomy' => 'language',
			'field'    => 'term_taxonomy_id', // Since WP 3.5
			'terms'    => $tt_ids,
			'operator' => 'IN',
		);

		$tax_query = &$this->query->query_vars['tax_query'];

		if ( isset( $tax_query['relation'] ) && 'OR' === $tax_query['relation'] ) {
			$tax_query = array(
				$lang_query,
				$tax_query,
				'relation' => 'AND',
			);
		} elseif ( is_array( $tax_query ) ) {
			// The tax query is expected to be *always* an array, but it seems that 3rd parties fill it with a string
			// Causing a fatal error if we don't check it.
			// See https://wordpress.org/support/topic/fatal-error-2947/
			$tax_query[] = $lang_query;
		} elseif ( empty( $tax_query ) ) {
			// Supposing the tax query has been wrongly filled with an empty string
			$tax_query = array( $lang_query );
		}
	}

	/**
	 * Adds the language in the query after it has checked that it won't conflict with other query vars.
	 *
	 * @since 2.2
	 *
	 * @param PLL_Language|false $lang Language.
	 * @return void
	 */
	public function filter_query( $lang ) {
		$qvars = &$this->query->query_vars;

		if ( ! $this->is_already_filtered( $qvars ) ) {
			$taxonomies = array_intersect( $this->model->get_translated_taxonomies(), get_taxonomies( array( '_builtin' => false ) ) );

			foreach ( $taxonomies as $tax ) {
				$tax_object = get_taxonomy( $tax );
				if ( ! empty( $tax_object ) && ! empty( $qvars[ $tax_object->query_var ] ) ) {
					return;
				}
			}

			if ( ! empty( $qvars['tax_query'] ) && $this->have_translated_taxonomy( $qvars['tax_query'] ) ) {
				return;
			}

			// Filter queries according to the requested language
			if ( ! empty( $lang ) ) {
				$taxonomies = $this->get_queried_taxonomies();

				if ( $taxonomies && ( empty( $qvars['post_type'] ) || 'any' === $qvars['post_type'] ) ) {
					foreach ( $taxonomies as $taxonomy ) {
						$tax_object = get_taxonomy( $taxonomy );
						if ( ! empty( $tax_object ) && $this->model->is_translated_post_type( $tax_object->object_type ) ) {
							$this->set_language( $lang );
							break;
						}
					}
				} elseif ( empty( $qvars['post_type'] ) || $this->model->is_translated_post_type( $qvars['post_type'] ) ) {
					$this->set_language( $lang );
				}
			}
		} else {
			$this->maybe_set_language_for_or_relation();

			// Do not filter untranslatable post types such as nav_menu_item
			if ( isset( $qvars['post_type'] ) && ! $this->model->is_translated_post_type( $qvars['post_type'] ) && ( empty( $qvars['tax_query'] ) || ! $this->have_translated_taxonomy( $qvars['tax_query'] ) ) ) {
				unset( $qvars['lang'] );
			}

			// Unset 'all' query var (mainly for admin language filter).
			if ( isset( $qvars['lang'] ) && 'all' === $qvars['lang'] ) {
				unset( $qvars['lang'] );
			}
		}
	}

	/**
	 * Sets the language correctly if the current query is a 'OR' relation,
	 * since WordPress merges the language with the other query vars when the relation is OR.
	 *
	 * @since 3.3
	 *
	 * @return void
	 */
	protected function maybe_set_language_for_or_relation() {
		if ( ! $this->query->tax_query instanceof WP_Tax_Query ) {
			return;
		}

		if ( 'OR' !== $this->query->tax_query->relation ) {
			return;
		}

		if ( ! isset( $this->query->tax_query->queried_terms['language'] ) ) {
			return;
		}

		$langs = $this->query->tax_query->queried_terms['language']['terms'];
		if ( is_string( $langs ) ) {
			$langs = explode( ',', $langs );
		}
		$langs = array_map( array( $this->model, 'get_language' ), $langs );
		$langs = array_filter( $langs );

		if ( ! empty( $langs ) ) {
			$this->set_language( $langs );
			unset( $this->query->query_vars['lang'] ); // Unset the language query var otherwise WordPress would add the language query by slug in WP_Query::parse_tax_query().
		}
	}
}
