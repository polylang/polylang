<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class to use for object types that support at least one language.
 *
 * @since 3.4
 */
abstract class PLL_Object_With_Language {

	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * List of taxonomies to cache.
	 *
	 * @var string[]
	 * @see PLL_Object_With_Language::get_object_term()
	 *
	 * @phpstan-var list<non-empty-string>
	 */
	protected $tax_to_cache = array();

	/**
	 * Taxonomy name for the languages.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $tax_language;

	/**
	 * Identifier that must be unique for each type of content.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $type;

	/**
	 * Object type to use when registering the taxonomy.
	 * Left empty for posts.
	 *
	 * @var string|null
	 *
	 * @phpstan-var non-empty-string|null
	 */
	protected $object_type = null;

	/**
	 * Default alias corresponding to the object's DB table.
	 *
	 * @var string
	 * @see PLL_Object_With_Language::join_clause()
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $db_default_alias;

	/**
	 * Name of the DB column containing the object's ID.
	 *
	 * @var string
	 * @see PLL_Object_With_Language::join_clause()
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $db_id_column;

	/**
	 * Constructor.
	 *
	 * @since 3.4
	 *
	 * @param PLL_Model $model Instance of `PLL_Model`, passed by reference.
	 */
	public function __construct( PLL_Model &$model ) {
		$this->model          = $model;
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
	 * @since 3.4
	 *
	 * @return string
	 *
	 * @phpstan-return non-empty-string
	 */
	public function get_tax_language() {
		return $this->tax_language;
	}

	/**
	 * Returns the type of object.
	 *
	 * @since 3.4
	 *
	 * @return string
	 *
	 * @phpstan-return non-empty-string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Adds hooks.
	 *
	 * @since 3.4
	 *
	 * @return static
	 */
	public function init() {
		return $this;
	}

	/**
	 * Stores the object's language into the database.
	 *
	 * @since 3.4
	 *
	 * @param int                     $id   Object ID.
	 * @param PLL_Language|string|int $lang Language (object, slug, or term ID).
	 * @return bool True when successfully assigned. False otherwise (or if the given language is already assigned to
	 *              the object).
	 */
	public function set_language( $id, $lang ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return false;
		}

		$old_lang = $this->get_language( $id );
		$old_lang = $old_lang ? $old_lang->get_tax_prop( $this->tax_language, 'term_id' ) : 0;

		$lang = $this->model->get_language( $lang );
		$lang = $lang ? $lang->get_tax_prop( $this->tax_language, 'term_id' ) : 0;

		if ( $old_lang === $lang ) {
			return false;
		}

		return is_array( wp_set_object_terms( $id, $lang, $this->tax_language ) );
	}

	/**
	 * Assigns a new language to an object.
	 *
	 * @since 3.1
	 *
	 * @param int          $id   Object ID.
	 * @param PLL_Language $lang New language to assign to the object.
	 * @return bool True when successfully assigned. False otherwise (or if the given language is already assigned to
	 *              the object).
	 */
	public function update_language( $id, PLL_Language $lang ) {
		return $this->set_language( $id, $lang );
	}

	/**
	 * Returns the language of an object.
	 *
	 * @since 0.1
	 * @since 3.4 Renamed the parameter $post_id into $id.
	 *
	 * @param int $id Object ID.
	 * @return PLL_Language|false A `PLL_Language` object. `false` if no language is associated to that object or if the
	 *                            ID is invalid.
	 */
	public function get_language( $id ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return false;
		}

		// Get the language and make sure it is a PLL_Language object.
		$lang = $this->get_object_term( $id, $this->tax_language );

		if ( empty( $lang ) ) {
			return false;
		}

		return $this->model->get_language( $lang );
	}

	/**
	 * Removes the term language from the database.
	 *
	 * @since 3.4
	 *
	 * @param int $id Term ID.
	 * @return void
	 */
	public function delete_language( $id ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return;
		}

		wp_delete_object_term_relationships( $id, $this->tax_language );
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
		global $wp_version;

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
		$store_only_term_ids = version_compare( $wp_version, '6.0', '>=' );

		foreach ( $this->tax_to_cache as $tax ) {
			if ( empty( $terms[ $tax ] ) ) {
				$to_cache = array();
			} elseif ( $store_only_term_ids ) {
				$to_cache = array( $terms[ $tax ]->term_id );
			} else {
				// Backward compatibility with WP < 6.0.
				$to_cache = array( $terms[ $tax ] );
			}

			wp_cache_add( $id, $to_cache, "{$tax}_relationships" );
		}

		return $term;
	}

	/**
	 * A JOIN clause to add to sql queries when filtering by language is needed directly in query.
	 *
	 * @since 3.4
	 *
	 * @param string $alias Optional alias for object table.
	 * @return string The JOIN clause.
	 *
	 * @phpstan-return non-empty-string
	 */
	public function join_clause( $alias = '' ) {
		global $wpdb;

		if ( empty( $alias ) ) {
			$alias = $this->db_default_alias;
		}

		return " INNER JOIN {$wpdb->term_relationships} AS pll_tr ON pll_tr.object_id = {$alias}.{$this->db_id_column}";
	}

	/**
	 * A WHERE clause to add to sql queries when filtering by language is needed directly in query.
	 *
	 * @since 1.2
	 *
	 * @param PLL_Language|PLL_Language[]|string|string[] $lang A `PLL_Language` object, or a comma separated list of language slugs, or an array of language slugs or objects.
	 * @return string The WHERE clause.
	 *
	 * @phpstan-param PLL_Language|PLL_Language[]|non-empty-string|non-empty-string[] $lang
	 */
	public function where_clause( $lang ) {
		/*
		 * $lang is an object.
		 * This is generally the case if the query is coming from Polylang.
		 */
		if ( $lang instanceof PLL_Language ) {
			return ' AND pll_tr.term_taxonomy_id = ' . absint( $lang->get_tax_prop( $this->tax_language, 'term_taxonomy_id' ) );
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
				$languages_tt_ids[] = absint( $language->get_tax_prop( $this->tax_language, 'term_taxonomy_id' ) );
			}
		}

		if ( empty( $languages_tt_ids ) ) {
			return '';
		}

		return ' AND pll_tr.term_taxonomy_id IN ( ' . implode( ',', $languages_tt_ids ) . ' )';
	}

	/**
	 * Returns object types that need to be translated.
	 *
	 * @since 3.4
	 *
	 * @param bool $filter True if we should return only valid registered object types.
	 * @return string[] Object type names for which Polylang manages languages.
	 *
	 * @phpstan-return array<non-empty-string, non-empty-string>
	 */
	abstract public function get_translated_object_types( $filter = true );

	/**
	 * Returns true if Polylang manages languages for this object type.
	 *
	 * @since 3.4
	 *
	 * @param string|string[] $object_type Object type name or array of object type names.
	 * @return bool
	 *
	 * @phpstan-param non-empty-string|non-empty-string[] $object_type
	 */
	abstract public function is_translated_object_type( $object_type );

	/**
	 * Returns the IDs of the objects without language.
	 *
	 * @since 3.4
	 *
	 * @param string[] $object_types An array of object types.
	 * @param int      $limit        Max number of objects to return. `-1` to return all of them.
	 * @return int[] Array of object IDs.
	 *
	 * @phpstan-param non-empty-string[] $object_types
	 * @phpstan-param -1|positive-int $limit
	 * @phpstan-return list<positive-int>
	 */
	abstract public function get_objects_with_no_lang( array $object_types, $limit );

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
