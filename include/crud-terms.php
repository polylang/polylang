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
	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * Current language (used to filter the content).
	 *
	 * @var PLL_Language|null
	 */
	public $curlang;

	/**
	 * Language selected in the admin language filter.
	 *
	 * @var PLL_Language|null
	 */
	public $filter_lang;

	/**
	 * Preferred language to assign to new contents.
	 *
	 * @var PLL_Language|null
	 */
	public $pref_lang;

	/**
	 * Stores the 'lang' query var from WP_Query.
	 *
	 * @var string|null
	 */
	private $tax_query_lang;

	/**
	 * Stores the term name before creating a slug if needed.
	 *
	 * @var string
	 */
	private $pre_term_name = '';

	/**
	 * Reference to the Polylang options array.
	 *
	 * @var \WP_Syntex\Polylang\Options\Options
	 */
	protected $options;

	/**
	 * Constructor
	 *
	 * @since 2.4
	 *
	 * @param PLL_Base $polylang The Polylang object.
	 */
	public function __construct( PLL_Base &$polylang ) {
		$this->options     = $polylang->options;
		$this->model       = &$polylang->model;
		$this->curlang     = &$polylang->curlang;
		$this->filter_lang = &$polylang->filter_lang;
		$this->pref_lang   = &$polylang->pref_lang;

		// Saving terms.
		add_action( 'create_term', array( $this, 'save_term' ), 999, 3 );
		add_action( 'edit_term', array( $this, 'save_term' ), 999, 3 ); // After PLL_Admin_Filters_Term
		add_filter( 'pre_term_name', array( $this, 'set_pre_term_name' ) );
		add_filter( 'pre_term_slug', array( $this, 'set_pre_term_slug' ), 10, 2 );

		// Filters terms query by language.
		add_filter( 'get_terms_args', array( $this, 'adjust_query_lang' ) );
		add_filter( 'terms_clauses', array( $this, 'terms_clauses' ), 10, 3 );
		add_action( 'pre_get_posts', array( $this, 'set_tax_query_lang' ), 999 );
		add_action( 'posts_selection', array( $this, 'unset_tax_query_lang' ), 0 );

		// Deleting terms.
		add_action( 'pre_delete_term', array( $this, 'delete_term' ), 10, 2 );
	}

	/**
	 * Allows to set a language by default for terms if it has no language yet.
	 *
	 * @since 1.5.4
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy name.
	 * @return void
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
			} elseif ( ! empty( $this->curlang ) ) {
				// Only on frontend due to the previous test always true on admin
				$this->model->term->set_language( $term_id, $this->curlang );
			} else {
				// In all other cases set to default language.
				$this->model->term->set_language( $term_id, $this->options['default_lang'] );
			}
		}
	}

	/**
	 * Called when a category or post tag is created or edited.
	 * Does nothing except on taxonomies which are filterable.
	 *
	 * @since 0.1
	 *
	 * @param int    $term_id  Term id of the term being saved.
	 * @param int    $tt_id    Term taxonomy id.
	 * @param string $taxonomy Taxonomy name.
	 * @return void
	 */
	public function save_term( $term_id, $tt_id, $taxonomy ) {
		if ( $this->model->is_translated_taxonomy( $taxonomy ) ) {

			$lang = $this->model->term->get_language( $term_id );

			if ( empty( $lang ) ) {
				$this->set_default_language( $term_id, $taxonomy );
			}

			/**
			 * Fires after the term language and translations are saved.
			 *
			 * @since 1.2
			 *
			 * @param int    $term_id      Term id.
			 * @param string $taxonomy     Taxonomy name.
			 * @param int[]  $translations The list of translations term ids.
			 */
			do_action( 'pll_save_term', $term_id, $taxonomy, $this->model->term->get_translations( $term_id ) );
		}
	}

	/**
	 * Returns the languages to filter `WP_Term_Query`.
	 *
	 * @since 3.8
	 *
	 * @param string[] $taxonomies Queried taxonomies.
	 * @param array    $args       WP_Term_Query arguments.
	 * @return PLL_Language[] The languages to filter `WP_Term_Query`.
	 */
	protected function get_queried_languages( $taxonomies, $args ): array {
		global $pagenow;

		/*
		 * Do nothing except on taxonomies which are filterable.
		 * Since WP 4.7, make sure not to filter wp_get_object_terms().
		 */
		if ( ! $this->model->is_translated_taxonomy( $taxonomies ) || ! empty( $args['object_ids'] ) ) {
			return array();
		}

		// If get_terms() is queried with a 'lang' parameter.
		if ( isset( $args['lang'] ) ) {
			$languages = is_string( $args['lang'] ) ? explode( ',', $args['lang'] ) : $args['lang'];
			return array_filter( array_map( array( $this->model, 'get_language' ), (array) $languages ) );
		}

		// On the tags page, everything should be filtered according to the admin language filter except the parent dropdown.
		if ( 'edit-tags.php' === $pagenow && empty( $args['class'] ) ) {
			return ! empty( $this->filter_lang ) ? array( $this->filter_lang ) : array();
		}

		return ! empty( $this->curlang ) ? array( $this->curlang ) : array();
	}

	/**
	 * Adjusts query language for `_get_term_hierarchy()` and `WP_Query`.
	 *
	 * @since 1.3
	 * @since 3.8 Renamed from `get_terms_args()`.
	 *
	 * @param array $args WP_Term_Query arguments.
	 * @return array Modified arguments.
	 */
	public function adjust_query_lang( $args ) {
		// Don't break _get_term_hierarchy().
		if ( 'all' === $args['get'] && 'id' === $args['orderby'] && 'id=>parent' === $args['fields'] ) {
			$args['lang'] = '';
		}

		if ( isset( $this->tax_query_lang ) ) {
			$args['lang'] = empty( $this->tax_query_lang ) && ! empty( $this->curlang ) && ! empty( $args['slug'] ) ? $this->curlang->slug : $this->tax_query_lang;
		}

		return $args;
	}

	/**
	 * Filters categories and post tags by language(s) when needed on admin side
	 *
	 * @since 0.2
	 *
	 * @param string[] $clauses    List of sql clauses.
	 * @param string[] $taxonomies List of taxonomies.
	 * @param array    $args       WP_Term_Query arguments.
	 * @return string[] Modified sql clauses.
	 */
	public function terms_clauses( $clauses, $taxonomies, $args ) {
		$languages = $this->get_queried_languages( $taxonomies, $args );
		return $this->model->terms_clauses( $clauses, $languages );
	}

	/**
	 * Sets the WP_Term_Query language when doing a WP_Query.
	 * Needed since WP 4.9.
	 *
	 * @since 2.3.2
	 *
	 * @param WP_Query $query WP_Query object.
	 * @return void
	 */
	public function set_tax_query_lang( $query ) {
		$this->tax_query_lang = $query->query_vars['lang'] ?? '';
	}

	/**
	 * Removes the WP_Term_Query language filter for WP_Query.
	 * Needed since WP 4.9.
	 *
	 * @since 2.3.2
	 *
	 * @return void
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
	 * @param int    $term_id  Id of the term to delete.
	 * @param string $taxonomy Name of the taxonomy.
	 * @return void
	 */
	public function delete_term( $term_id, $taxonomy ) {
		if ( ! $this->model->is_translated_taxonomy( $taxonomy ) ) {
			return;
		}

		// Delete translation and relationships only if the term is translatable.
		$this->model->term->delete_translation( $term_id );
		$this->model->term->delete_language( $term_id );
	}

	/**
	 * Stores the term name for use in pre_term_slug
	 *
	 * @since 0.9.5
	 *
	 * @param string $name term name
	 * @return string unmodified term name
	 */
	public function set_pre_term_name( $name ) {
		$this->pre_term_name = is_string( $name ) ? $name : '';

		return $name;
	}

	/**
	 * Appends language slug to the term slug if needed.
	 *
	 * @since 3.3
	 *
	 * @param string $slug     Term slug.
	 * @param string $taxonomy Term taxonomy.
	 * @return string Slug with a language suffix if found.
	 */
	public function set_pre_term_slug( $slug, $taxonomy ) {
		if ( ! $this->model->is_translated_taxonomy( $taxonomy ) || ! is_string( $slug ) ) {
			return $slug;
		}

		$term_slug = new PLL_Term_Slug( $this->model, $slug, $taxonomy, $this->pre_term_name );

		return $term_slug->get_suffixed_slug( '-' );
	}
}
