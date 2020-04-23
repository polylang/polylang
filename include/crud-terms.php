<?php
/**
 * @package Polylang
 */

/**
 * Adds actions and filters related to languages when creating, reading, updating or deleting posts
 * Acts both on frontend and backend
 *
 * @since 2.4
 */
class PLL_CRUD_Terms {
	public $model, $curlang, $filter_lang, $pref_lang;
	private $tax_query_lang;

	/**
	 * Constructor
	 *
	 * @since 2.4
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->model = &$polylang->model;
		$this->curlang = &$polylang->curlang;
		$this->filter_lang = &$polylang->filter_lang;
		$this->pref_lang = &$polylang->pref_lang;

		// Saving terms
		add_action( 'create_term', array( $this, 'save_term' ), 999, 3 );
		add_action( 'edit_term', array( $this, 'save_term' ), 999, 3 ); // After PLL_Admin_Filters_Term

		// Adds cache domain when querying terms
		add_filter( 'get_terms_args', array( $this, 'get_terms_args' ), 10, 2 );

		// Filters terms by language
		add_filter( 'terms_clauses', array( $this, 'terms_clauses' ), 10, 3 );
		add_action( 'pre_get_posts', array( $this, 'set_tax_query_lang' ), 999 );
		add_action( 'posts_selection', array( $this, 'unset_tax_query_lang' ), 0 );

		// Deleting terms
		add_action( 'pre_delete_term', array( $this, 'delete_term' ) );
	}

	/**
	 * Allows to set a language by default for terms if it has no language yet
	 *
	 * @since 1.5.4
	 *
	 * @param int    $term_id
	 * @param string $taxonomy
	 */
	protected function set_default_language( $term_id, $taxonomy ) {
		if ( ! $this->model->term->get_language( $term_id ) ) {
			if ( ! isset( $this->pref_lang ) && ! empty( $_REQUEST['lang'] ) && $lang = $this->model->get_language( sanitize_key( $_REQUEST['lang'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				// Testing $this->pref_lang makes this test pass only on frontend.
				$this->model->term->set_language( $term_id, $lang );
			} elseif ( ( $term = get_term( $term_id, $taxonomy ) ) && ! empty( $term->parent ) && $parent_lang = $this->model->term->get_language( $term->parent ) ) {
				// Sets language from term parent if exists thanks to Scott Kingsley Clark
				$this->model->term->set_language( $term_id, $parent_lang );
			} elseif ( isset( $this->pref_lang ) ) {
				// Always defined on admin, never defined on frontend
				$this->model->term->set_language( $term_id, $this->pref_lang );
			} else {
				// Only on frontend due to the previous test always true on admin
				$this->model->term->set_language( $term_id, $this->curlang );
			}
		}
	}

	/**
	 * Called when a category or post tag is created or edited
	 * Does nothing except on taxonomies which are filterable
	 *
	 * @since 0.1
	 *
	 * @param int    $term_id
	 * @param int    $tt_id    Term taxonomy id
	 * @param string $taxonomy
	 */
	public function save_term( $term_id, $tt_id, $taxonomy ) {
		if ( $this->model->is_translated_taxonomy( $taxonomy ) ) {

			$lang = $this->model->term->get_language( $term_id );

			if ( empty( $lang ) ) {
				$this->set_default_language( $term_id, $taxonomy );
			}

			/**
			 * Fires after the term language and translations are saved
			 *
			 * @since 1.2
			 *
			 * @param int    $term_id      term id
			 * @param string $taxonomy     taxonomy name
			 * @param array  $translations the list of translations term ids
			 */
			do_action( 'pll_save_term', $term_id, $taxonomy, $this->model->term->get_translations( $term_id ) );
		}
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
			$args['lang'] = empty( $this->tax_query_lang ) && ! empty( $this->curlang ) && ! empty( $args['slug'] ) ? $this->curlang->slug : $this->tax_query_lang;
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
		return $this->model->terms_clauses( $clauses, $lang );
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

	/**
	 * Called when a category or post tag is deleted
	 * Deletes language and translations
	 *
	 * @since 0.1
	 *
	 * @param int $term_id
	 */
	public function delete_term( $term_id ) {
		$this->model->term->delete_translation( $term_id );
		$this->model->term->delete_language( $term_id );
	}
}
