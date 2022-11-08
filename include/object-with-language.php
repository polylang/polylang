<?php
/**
 * @package Polylang
 */

/**
 * Abstract class to use for object types that support at least one language.
 *
 * @since 3.3
 */
abstract class PLL_Object_With_Language implements PLL_Object_With_Language_Interface {

	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * Taxonomy name for the languages.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $tax_language;

	/**
	 * Object type to use when registering the taxonomy.
	 * Left empty for object types that are not a taxonomy.
	 *
	 * @var string|null
	 *
	 * @phpstan-var non-empty-string|null
	 */
	protected $object_type = null;

	/**
	 * Name of the `PLL_Language` property that stores the term_taxonomy ID.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $tax_tt_prop_name;

	/**
	 * List of taxonomies to cache in get_object_term().
	 *
	 * @var string[]
	 */
	protected $tax_to_cache = array();

	/**
	 * Constructor.
	 *
	 * @since 3.3
	 *
	 * @param PLL_Model $model Instance of `PLL_Model`.
	 */
	public function __construct( PLL_Model &$model ) {
		$this->model          = &$model;
		$this->tax_to_cache[] = $this->tax_language;

		/*
		 * Register our taxonomy as soon as possible.
		 * This is early registration, not ready for rewrite rules as $wp_rewrite will be setup later.
		 */
		register_taxonomy(
			$this->tax_language,
			(array) $this->object_type,
			array(
				'label'     => false,
				'public'    => false,
				'query_var' => false,
				'rewrite'   => false,
				'_pll'      => true,
			)
		);
	}

	/**
	 * Returns the language taxonomy name.
	 *
	 * @since 3.3
	 *
	 * @return string
	 *
	 * @phpstan-return non-empty-string
	 */
	public function get_tax_language() {
		return $this->tax_language;
	}

	/**
	 * Returns the language of an object.
	 *
	 * @since 0.1
	 * @since 3.3 Renamed the parameter $post_id into $id.
	 *
	 * @param int $id Object ID.
	 * @return PLL_Language|false A `PLL_Language` object, `false` if no language is associated to that object.
	 */
	public function get_language( $id ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return false;
		}

		$lang = $this->get_object_term( $id, $this->tax_language );

		if ( empty( $lang ) ) {
			return false;
		}

		return $this->model->get_language( $lang );
	}

	/**
	 * Assigns a new language to an object.
	 *
	 * @since 3.1
	 *
	 * @param int          $id   Object ID.
	 * @param PLL_Language $lang New language to assign to the object.
	 * @return bool True on success (or if the given language is already assigned to the object). False otherwise.
	 */
	public function update_language( $id, PLL_Language $lang ) {
		return $this->set_language( $id, $lang );
	}

	/**
	 * Wraps `wp_get_object_terms()` to cache it and return only one object.
	 * Inspired by the WordPress function `get_the_terms()`.
	 *
	 * @since 1.2
	 *
	 * @param int    $id       Object ID.
	 * @param string $taxonomy Polylang taxonomy depending if we are looking for a post (or term, or else) language.
	 * @return WP_Term|false The term associated to the object in the requested taxonomy if it exists, `false` otherwise.
	 */
	public function get_object_term( $id, $taxonomy ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return false;
		}

		$term = get_object_term_cache( $id, $taxonomy );

		if ( is_array( $term ) ) {
			return ! empty( $term ) ? reset( $term ) : false;
		}

		// Query terms.
		$terms        = array();
		$term         = false;
		$object_terms = wp_get_object_terms( $id, $this->tax_to_cache, array( 'update_term_meta_cache' => false ) );

		if ( is_array( $object_terms ) ) {
			foreach ( $object_terms as $t ) {
				$terms[ $t->taxonomy ] = $t;
				if ( $t->taxonomy === $taxonomy ) {
					$term = $t;
				}
			}
		}

		// Stores it the way WP expects it. Set an empty cache if no term was found in the taxonomy.
		foreach ( $this->tax_to_cache as $tax ) {
			$to_cache = ! empty( $terms[ $tax ] ) ? array( $terms[ $tax ] ) : array();
			wp_cache_add( $id, $to_cache, $tax . '_relationships' );
		}

		return $term;
	}

	/**
	 * A WHERE clause to add to sql queries when filtering by language is needed directly in query.
	 *
	 * @since 1.2
	 *
	 * @param PLL_Language|PLL_Language[]|string|string[] $lang A `PLL_Language` object, or a comma separated list of language slugs, or an array of language slugs or objects.
	 * @return string The WHERE clause.
	 */
	public function where_clause( $lang ) {
		$tax_tt_prop_name = $this->tax_tt_prop_name;

		/*
		 * $lang is an object.
		 * This is generally the case if the query is coming from Polylang.
		 */
		if ( $lang instanceof PLL_Language ) {
			return ' AND pll_tr.term_taxonomy_id = ' . absint( $lang->$tax_tt_prop_name );
		}

		/*
		 * $lang is an array of objects, an array of slugs, or a comma separated list of slugs.
		 * The comma separated list of slugs can happen if the query is coming from outside with a 'lang' parameter.
		 */
		$languages        = is_array( $lang ) ? $lang : explode( ',', $lang );
		$languages_tt_ids = array();

		foreach ( $languages as $language ) {
			$language = $this->model->get_language( $language );

			if ( ! empty( $language ) ) {
				$languages_tt_ids[] = absint( $language->$tax_tt_prop_name );
			}
		}

		if ( empty( $languages_tt_ids ) ) {
			return '';
		}

		return ' AND pll_tr.term_taxonomy_id IN ( ' . implode( ',', $languages_tt_ids ) . ' )';
	}

	/**
	 * Sanitizes an ID as positive integer.
	 * Kind of similar to `absint()`, but rejects negetive integers instead of making them positive.
	 *
	 * @since 3.2
	 *
	 * @param mixed $id A supposedly numeric ID.
	 * @return int A positive integer. `0` for non numeric values and negative integers.
	 *
	 * @phpstan-return int<0,max>
	 */
	public function sanitize_int_id( $id ) {
		return is_numeric( $id ) && $id >= 1 ? abs( (int) $id ) : 0;
	}

	/**
	 * Sanitizes an array of IDs as positive integers.
	 * `0` values are removed.
	 *
	 * @since 3.2
	 *
	 * @param mixed $ids An array of numeric IDs.
	 * @return int[]
	 *
	 * @phpstan-return array<positive-int>
	 */
	public function sanitize_int_ids_list( $ids ) {
		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return array();
		}

		$ids = array_map( array( $this, 'sanitize_int_id' ), $ids );

		return array_filter( $ids );
	}
}
