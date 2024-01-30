<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class to use for object types that support at least one language.
 *
 * @since 3.4
 *
 * @phpstan-type DBInfo array{
 *     table: non-empty-string,
 *     id_column: non-empty-string,
 *     default_alias: non-empty-string
 * }
 */
abstract class PLL_Translatable_Object {

	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * List of taxonomies to cache.
	 *
	 * @var string[]
	 * @see PLL_Translatable_Object::get_object_term()
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
	 * Also used when checking capabilities.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $type;

	/**
	 * Identifier for each type of content to used for cache type.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $cache_type;

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

		$term_taxonomy_ids = wp_set_object_terms( $id, $lang, $this->tax_language );

		wp_cache_set( 'last_changed', microtime(), $this->cache_type );

		return is_array( $term_taxonomy_ids );
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

		return $this->model->get_language( $lang->term_id );
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

		$db = $this->get_db_infos();

		if ( empty( $alias ) ) {
			$alias = $db['default_alias'];
		}

		return " INNER JOIN {$wpdb->term_relationships} AS pll_tr ON pll_tr.object_id = {$alias}.{$db['id_column']}";
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
	 * Returns the IDs of the objects without language.
	 *
	 * @since 3.4
	 *
	 * @param int   $limit  Max number of objects to return. `-1` to return all of them.
	 * @param array $args   The object args.
	 * @return int[] Array of object IDs.
	 *
	 * @phpstan-param -1|positive-int $limit
	 * @phpstan-return list<positive-int>
	 */
	public function get_objects_with_no_lang( $limit, array $args = array() ) {
		$language_ids = array();

		foreach ( $this->model->get_languages_list() as $language ) {
			$language_ids[] = $language->get_tax_prop( $this->get_tax_language(), 'term_taxonomy_id' );
		}

		$language_ids = array_filter( $language_ids );

		if ( empty( $language_ids ) ) {
			return array();
		}

		$sql        = $this->get_objects_with_no_lang_sql( $language_ids, $limit, $args );
		$object_ids = $this->query_objects_with_no_lang( $sql );

		return array_values( $this->sanitize_int_ids_list( $object_ids ) );
	}

	/**
	 * Returns object IDs without language given a specific SQL query.
	 * Can be overridden by child classes in case queried object doesn't use
	 * `wp_cache_set_last_changed()` or another cache system.
	 *
	 * @since 3.4
	 *
	 * @param string $sql A prepared SQL query for object IDs with no language.
	 * @return string[] An array of numeric object IDs.
	 */
	protected function query_objects_with_no_lang( $sql ) {
		$key          = md5( $sql );
		$last_changed = wp_cache_get_last_changed( $this->cache_type );
		$cache_key    = "{$this->cache_type}_no_lang:{$key}:{$last_changed}";
		$object_ids   = wp_cache_get( $cache_key, $this->cache_type );

		if ( is_array( $object_ids ) ) {
			return $object_ids;
		}

		$object_ids = $GLOBALS['wpdb']->get_col( $sql ); // PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
		wp_cache_set( $cache_key, $object_ids, $this->cache_type );

		return $object_ids;
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

	/**
	 * Returns SQL query that fetches the IDs of the objects without language.
	 *
	 * @since 3.4
	 *
	 * @param int[] $language_ids List of language `term_taxonomy_id`.
	 * @param int   $limit        Max number of objects to return. `-1` to return all of them.
	 * @param array $args         The object args.
	 * @return string
	 *
	 * @phpstan-param array<positive-int> $language_ids
	 * @phpstan-param -1|positive-int $limit
	 * @phpstan-param array<empty> $args
	 */
	protected function get_objects_with_no_lang_sql( array $language_ids, $limit, array $args = array() ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$db = $this->get_db_infos();

		return sprintf(
			"SELECT {$db['table']}.{$db['id_column']} FROM {$db['table']}
			WHERE {$db['table']}.{$db['id_column']} NOT IN (
				SELECT object_id FROM {$GLOBALS['wpdb']->term_relationships} WHERE term_taxonomy_id IN (%s)
			)
			%s",
			PLL_Db_Tools::prepare_values_list( $language_ids ),
			$limit >= 1 ? sprintf( 'LIMIT %d', $limit ) : ''
		);
	}

	/**
	 * Assigns a language to object in mass.
	 *
	 * @since 1.2
	 * @since 3.4 Moved from PLL_Admin_Model class.
	 *
	 * @param int[]        $ids  Array of post ids or term ids.
	 * @param PLL_Language $lang Language to assign to the posts or terms.
	 * @return void
	 */
	public function set_language_in_mass( $ids, $lang ) {
		global $wpdb;

		$tt_id = $lang->get_tax_prop( $this->tax_language, 'term_taxonomy_id' );

		if ( empty( $tt_id ) ) {
			return;
		}
		$ids = array_map( 'intval', $ids );
		$ids = array_filter( $ids );

		if ( empty( $ids ) ) {
			return;
		}

		$values = array();

		foreach ( $ids as $id ) {
			$values[] = $wpdb->prepare( '( %d, %d )', $id, $tt_id );
		}

		// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "INSERT INTO {$wpdb->term_relationships} ( object_id, term_taxonomy_id ) VALUES " . implode( ',', array_unique( $values ) ) );

		// Updating term count is mandatory (thanks to AndyDeGroo).
		$lang->update_count();
		clean_term_cache( $ids, $this->tax_language );

		// Invalidate our cache.
		wp_cache_set( 'last_changed', microtime(), $this->cache_type );
	}

	/**
	 * Returns database-related information that can be used in some of this class methods.
	 * These are specific to the table containing the objects.
	 *
	 * @see PLL_Translatable_Object::join_clause()
	 * @see PLL_Translatable_Object::get_objects_with_no_lang_sql()
	 *
	 * @since 3.4.3
	 *
	 * @return string[] {
	 *     @type string $table         Name of the table.
	 *     @type string $id_column     Name of the column containing the object's ID.
	 *     @type string $default_alias Default alias corresponding to the object's table.
	 * }
	 * @phpstan-return DBInfo
	 */
	abstract protected function get_db_infos();
}
