<?php

/**
 * A class to filter WP_Term_Query
 * Acts both on frontend and backend
 *
 * @since 2.4
 */
class PLL_CRUD_Terms {
	public $model, $curlang, $filter_lang;
	private $tax_query_lang;

	/**
	 * Constructor
	 *
	 * @since 2.4
	 *
	 * @param object $polylang
	 */
	public function __construct( $polylang ) {
		$this->model = &$polylang->model;
		$this->curlang = &$polylang->curlang;
		$this->filter_lang = &$polylang->filter_lang;

		// Adds cache domain when querying terms
		add_filter( 'get_terms_args', array( $this, 'get_terms_args' ), 10, 2 );

		// Filters categories and post tags by language
		add_filter( 'terms_clauses', array( $this, 'terms_clauses' ), 10, 3 );
		add_action( 'pre_get_posts', array( $this, 'set_tax_query_lang' ), 999 );
		add_action( 'posts_selection', array( $this, 'unset_tax_query_lang' ), 0 );
	}

	/**
	 * Get the language(s) to filter get_terms
	 *
	 * @since 1.7.6
	 *
	 * @param array $taxonomies queried taxonomies
	 * @param array $args       get_terms arguments
	 * @return object|string|bool the language(s) to use in the filter, false otherwise
	 */
	protected function get_queried_language( $taxonomies, $args ) {
		// Does nothing except on taxonomies which are filterable
		// Since WP 4.7, make sure not to filter wp_get_object_terms()
		if ( ! $this->model->is_translated_taxonomy( $taxonomies ) || ! empty( $args['object_ids'] ) ) {
			return false;
		}

		// If get_terms is queried with a 'lang' parameter
		if ( isset( $args['lang'] ) ) {
			return $args['lang'];
		}

		// On tags page, everything should be filtered according to the admin language filter except the parent dropdown
		if ( 'edit-tags.php' === $GLOBALS['pagenow'] && empty( $args['class'] ) ) {
			return $this->filter_lang;
		}

		return $this->curlang;
	}

	/**
	 * Adds language dependent cache domain when querying terms
	 * Useful as the 'lang' parameter is not included in cache key by WordPress
	 *
	 * @since 1.3
	 *
	 * @param array $args
	 * @param array $taxonomies
	 * @return array modified arguments
	 */
	public function get_terms_args( $args, $taxonomies ) {
		// Don't break _get_term_hierarchy()
		if ( 'all' === $args['get'] && 'id' === $args['orderby'] && 'id=>parent' === $args['fields'] ) {
			$args['lang'] = '';
		}

		if ( isset( $this->tax_query_lang ) ) {
			$args['lang'] = empty( $this->tax_query_lang ) && ! empty( $args['slug'] ) ? $this->curlang->slug : $this->tax_query_lang;
		}

		if ( $lang = $this->get_queried_language( $taxonomies, $args ) ) {
			$lang = is_string( $lang ) && strpos( $lang, ',' ) ? explode( ',', $lang ) : $lang;
			$key = '_' . ( is_array( $lang ) ? implode( ',', $lang ) : $this->model->get_language( $lang )->slug );
			$args['cache_domain'] = empty( $args['cache_domain'] ) ? 'pll' . $key : $args['cache_domain'] . $key;
		}
		return $args;
	}

	/**
	 * Filters categories and post tags by language(s) when needed on admin side
	 *
	 * @since 0.2
	 *
	 * @param array $clauses    list of sql clauses
	 * @param array $taxonomies list of taxonomies
	 * @param array $args       get_terms arguments
	 * @return array modified sql clauses
	 */
	public function terms_clauses( $clauses, $taxonomies, $args ) {
		$lang = $this->get_queried_language( $taxonomies, $args );

		// Adds our clauses to filter by current language
		if ( ! empty( $lang ) && false === strpos( $clauses['join'], 'pll_tr' ) ) {
			$clauses['join']  .= $this->model->term->join_clause();
			$clauses['where'] .= $this->model->term->where_clause( $lang );
		}

		return $clauses;
	}

	/**
	 * Sets the WP_Term_Query language when doing a WP_Query
	 * Needed since WP 4.9
	 *
	 * @since 2.3.2
	 *
	 * @param object $query WP_Query object
	 */
	public function set_tax_query_lang( $query ) {
		$this->tax_query_lang = isset( $query->query_vars['lang'] ) ? $query->query_vars['lang'] : '';
	}

	/**
	 * Removes the WP_Term_Query language filter for WP_Query
	 * Needed since WP 4.9
	 *
	 * @since 2.3.2
	 */
	public function unset_tax_query_lang() {
		unset( $this->tax_query_lang );
	}
}
