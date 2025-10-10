<?php
/**
 * @package Polylang
 */

use WP_Syntex\Polylang\Model\Languages;

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
	 * Model for the languages.
	 *
	 * @var Languages
	 */
	protected $languages;

	/**
	 * Polylang's options.
	 *
	 * @var \WP_Syntex\Polylang\Options\Options
	 */
	protected $options;

	/**
	 * Internal non persistent cache object.
	 *
	 * @var PLL_Cache<mixed>
	 */
	protected $cache;

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
	 * @param PLL_Model $model Instance of `PLL_Model`.
	 */
	public function __construct( PLL_Model $model ) {
		$this->languages      = $model->languages;
		$this->options        = $model->options;
		$this->cache          = $model->cache;
		$this->tax_to_cache[] = $this->tax_language;

		/*
		 * Register our taxonomy as soon as possible.
		 */
		$this->register_language_taxonomy();
	}

	/**
	 * Registers the language taxonomy.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	protected function register_language_taxonomy(): void {
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

		$lang = $this->languages->get( $lang );
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

		return $this->languages->get( $lang->term_id );
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
	 * Wraps `wp_get_object_terms()` to cache it for multiple objects.
	 *
	 * @since 3.8
	 *
	 * @param int[]  $object_ids Array of object IDs.
	 * @param string $taxonomy   Taxonomy name.
	 * @return array<int,WP_Term|null> Array of terms with object ID as key.
	 */
	protected function get_object_terms( array $object_ids, string $taxonomy ) {
		$object_ids = $this->sanitize_int_ids_list( $object_ids );
		if ( empty( $object_ids ) ) {
			return array();
		}

		$cached_values = $this->get_from_object_term_cache( $object_ids, $taxonomy );

		// Flatten the array to prime the terms cache.
		$all_term_ids = array();
		foreach ( $cached_values as $term_ids ) {
			$all_term_ids = array_merge( $all_term_ids, $term_ids );
		}
		_prime_term_caches( $all_term_ids, false );

		$terms = array();
		foreach ( $cached_values as $object_id => $term_ids ) {
			if ( ! empty( $term_ids ) ) {
				$term_id = reset( $term_ids ); // There is only one term for language or translation groups.

				/** @var WP_Term $term */
				$term                = get_term( $term_id );
				$terms[ $object_id ] = $term;
			}
		}

		return $terms;
	}

	/**
	 * Caches all object-relationship terms and returns them for the specified taxonomy.
	 *
	 * @since 3.8
	 *
	 * @param int[]  $object_ids Array of object IDs to retrieve terms for.
	 * @param string $taxonomy   Taxonomy name.
	 *
	 * @return int[][]
	 */
	private function get_from_object_term_cache( array $object_ids, string $taxonomy ) {
		$non_cached_ids = array();
		foreach ( $this->tax_to_cache as $tax ) {
			$non_cached_ids = array_merge( $non_cached_ids, _get_non_cached_ids( $object_ids, "{$tax}_relationships" ) );
		}

		if ( empty( $non_cached_ids ) ) {
			return wp_cache_get_multiple( $object_ids, "{$taxonomy}_relationships" );
		}

		$terms = wp_get_object_terms(
			array_unique( $non_cached_ids ),
			$this->tax_to_cache,
			array(
				'fields'                 => 'all_with_object_id',
				'update_term_meta_cache' => false,
			)
		);

		if ( ! is_array( $terms ) ) {
			return wp_cache_get_multiple( $object_ids, "{$taxonomy}_relationships" );
		}

		$object_terms = array();
		foreach ( $terms as $term ) {
			$object_terms[ $term->taxonomy ][ $term->object_id ][] = $term->term_id;
		}

		foreach ( $non_cached_ids as $id ) {
			foreach ( $this->tax_to_cache as $tax ) {
				if ( ! isset( $object_terms[ $tax ][ $id ] ) ) {
					$object_terms[ $tax ][ $id ] = array();
				}
			}
		}

		foreach ( $object_terms as $tax => $data ) {
			wp_cache_add_multiple( $data, "{$tax}_relationships" );
		}

		return wp_cache_get_multiple( $object_ids, "{$taxonomy}_relationships" );
	}

	/**
	 * Returns terms associated to the given object in the given taxonomy.
	 *
	 * @since 1.2
	 * @since 3.8 Returns null if the associated term doesn't exist.
	 *
	 * @param int    $object_id Object ID.
	 * @param string $taxonomy  Polylang taxonomy depending if we are looking for a post (or term, or else) language.
	 * @return WP_Term|null The term associated to the object in the requested taxonomy if it exists, `null` otherwise.
	 */
	public function get_object_term( $object_id, $taxonomy ) {
		$terms = $this->get_object_terms( array( $object_id ), $taxonomy );
		return $terms[ $object_id ] ?? null;
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
			$language = $this->languages->get( $language );

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

		foreach ( $this->languages->get_list() as $language ) {
			$language_ids[] = $language->get_tax_prop( $this->get_tax_language(), 'term_taxonomy_id' );
		}

		$language_ids = array_filter( $language_ids );

		if ( empty( $language_ids ) ) {
			return array();
		}

		$object_ids = $this->query_objects_with_no_lang( $language_ids, $limit, $args );

		return array_values( $this->sanitize_int_ids_list( $object_ids ) );
	}

	/**
	 * Returns object IDs without language.
	 * Can be overridden by child classes in case queried object doesn't use
	 * `wp_cache_set_last_changed()` or another cache system.
	 *
	 * @since 3.4
	 * @since 3.7 Changed all parameters.
	 *
	 * @param int[] $language_ids List of language `term_taxonomy_id`.
	 * @param int   $limit        Max number of objects to return. `-1` to return all of them.
	 * @param array $args         The object args.
	 * @return string[] An array of numeric object IDs.
	 *
	 * @phpstan-param array<positive-int> $language_ids
	 * @phpstan-param -1|positive-int $limit
	 * @phpstan-param array<empty> $args
	 */
	protected function query_objects_with_no_lang( array $language_ids, $limit, array $args = array() ) {
		$key          = md5( maybe_serialize( $language_ids ) . maybe_serialize( $args ) . $limit );
		$last_changed = wp_cache_get_last_changed( $this->cache_type );
		$cache_key    = "{$this->cache_type}_no_lang:{$key}:{$last_changed}";
		$object_ids   = wp_cache_get( $cache_key, $this->cache_type );

		if ( is_array( $object_ids ) ) {
			return $object_ids;
		}

		$object_ids = $this->get_raw_objects_with_no_lang( $language_ids, $limit, $args );
		wp_cache_set( $cache_key, $object_ids, $this->cache_type );

		return $object_ids;
	}

	/**
	 * Sanitizes an ID as positive integer.
	 * Kind of similar to `absint()`, but rejects negative integers instead of making them positive.
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
	 * Fetches the IDs of the objects without language.
	 *
	 * @since 3.7
	 *
	 * @param int[] $language_ids List of language `term_taxonomy_id`.
	 * @param int   $limit        Max number of objects to return. `-1` to return all of them.
	 * @param array $args         The object args.
	 * @return string[]
	 *
	 * @phpstan-param array<positive-int> $language_ids
	 * @phpstan-param -1|positive-int $limit
	 * @phpstan-param array<empty> $args
	 */
	protected function get_raw_objects_with_no_lang( array $language_ids, $limit, array $args = array() ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		global $wpdb;

		$db = $this->get_db_infos();

		return $wpdb->get_col(
			$wpdb->prepare(
				sprintf(
					"SELECT %%i FROM %%i
					WHERE %%i NOT IN (
						SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN (%s)
					)
					LIMIT %%d",
					implode( ',', array_fill( 0, count( $language_ids ), '%d' ) )
				),
				array_merge(
					array( $db['id_column'], $db['table'], $db['id_column'] ),
					$language_ids,
					array( $limit >= 1 ? $limit : 4294967295 )
				)
			)
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
	 * Returns the description to use for the "language properties" in the REST API.
	 *
	 * @since 3.7
	 * @see WP_Syntex\Polylang\REST\V2\Languages::get_item_schema()
	 *
	 * @return string
	 */
	public function get_rest_description(): string {
		/* translators: %s is the name of a database table. */
		return sprintf( __( 'Language taxonomy properties for table %s.', 'polylang' ), $this->get_db_infos()['table'] );
	}

	/**
	 * Returns database-related information that can be used in some of this class methods.
	 * These are specific to the table containing the objects.
	 *
	 * @see PLL_Translatable_Object::join_clause()
	 * @see PLL_Translatable_Object::get_raw_objects_with_no_lang()
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
