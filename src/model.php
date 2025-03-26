<?php
/**
 * @package Polylang
 */

use WP_Syntex\Polylang\Model;
use WP_Syntex\Polylang\Options\Options;

/**
 * Setups the language and translations model based on WordPress taxonomies.
 *
 * @since 1.2
 *
 * @method bool               has_languages()                                     Checks if there are languages or not. See `Model\Languages::has()`.
 * @method array              get_languages_list(array $args = array())           Returns the list of available languages. See `Model\Languages::get_list()`.
 * @method bool               are_languages_ready()                               Tells if get_languages_list() can be used. See `Model\Languages::are_ready()`.
 * @method void               set_languages_ready()                               Sets the internal property `$languages_ready` to `true`, telling that get_languages_list() can be used. See `Model\Languages::set_ready()`.
 * @method PLL_Language|false get_language(mixed $value)                          Returns the language by its term_id, tl_term_id, slug or locale. See `Model\Languages::get()`.
 * @method true|WP_Error      add_language(array $args)                           Adds a new language and creates a default category for this language. See `Model\Languages::add()`.
 * @method bool               delete_language(int $lang_id)                       Deletes a language. See `Model\Languages::delete()`.
 * @method true|WP_Error      update_language(array $args)                        Updates language properties. See `Model\Languages::update()`.
 * @method PLL_Language|false get_default_language()                              Returns the default language. See `Model\Languages::get_default()`.
 * @method void               update_default_lang(string $slug)                   Updates the default language. See `Model\Languages::update_default()`.
 * @method void               maybe_create_language_terms()                       Maybe adds the missing language terms for 3rd party language taxonomies. See `Model\Languages::maybe_create_terms()`.
 * @method string[]           get_translated_post_types(bool $filter = true)      Returns post types that need to be translated. See `Model\Post_Types::get_translated()`.
 * @method bool               is_translated_post_type(string|string[] $post_type) Returns true if Polylang manages languages and translations for this post type. See `Model\Post_Types::is_translated()`.
 * @method string[]           get_translated_taxonomies(bool $filter = true)      Returns taxonomies that need to be translated. See `Model\Taxonomies::get_translated()`.
 * @method bool               is_translated_taxonomy(string|string[] $tax)        Returns true if Polylang manages languages and translations for this taxonomy. See `Model\Taxonomies::is_translated()`.
 * @method string[]           get_filtered_taxonomies(bool $filter = true)        Return taxonomies that need to be filtered (post_format like). See `Model\Taxonomies::get_filtered()`.
 * @method bool               is_filtered_taxonomy(string|string[] $tax)          Returns true if Polylang filters this taxonomy per language. See `Model\Taxonomies::is_filtered()`.
 * @method string[]           get_filtered_taxonomies_query_vars()                Returns the query vars of all filtered taxonomies. See `Model\Taxonomies::get_filtered_query_vars()`.
 */
class PLL_Model {
	/**
	 * Internal non persistent cache object.
	 *
	 * @var PLL_Cache<mixed>
	 */
	public $cache;

	/**
	 * Stores the plugin options.
	 *
	 * @var Options
	 */
	public $options;

	/**
	 * Translatable objects registry.
	 *
	 * @since 3.4
	 *
	 * @var PLL_Translatable_Objects
	 */
	public $translatable_objects;

	/**
	 * Translated post model.
	 *
	 * @var PLL_Translated_Post
	 */
	public $post;

	/**
	 * Translated term model.
	 *
	 * @var PLL_Translated_Term
	 */
	public $term;

	/**
	 * Model for the languages.
	 *
	 * @var Model\Languages
	 */
	public $languages;

	/**
	 * Model for taxonomies translated by Polylang.
	 *
	 * @var Model\Post_Types
	 */
	public $post_types;

	/**
	 * Model for taxonomies filtered/translated by Polylang.
	 *
	 * @var Model\Taxonomies
	 */
	public $taxonomies;

	/**
	 * Constructor.
	 * Setups translated objects sub models.
	 * Setups filters and actions.
	 *
	 * @since 1.2
	 * @since 3.7 Type of parameter `$options` changed from `array` to `Options`.
	 *
	 * @param Options $options Polylang options.
	 */
	public function __construct( Options &$options ) {
		$this->options              = &$options;
		$this->cache                = new PLL_Cache();
		$this->translatable_objects = new PLL_Translatable_Objects();
		$this->languages            = new Model\Languages( $this->options, $this->translatable_objects, $this->cache );

		$this->post = $this->translatable_objects->register( new PLL_Translated_Post( $this ) ); // Translated post sub model.
		$this->term = $this->translatable_objects->register( new PLL_Translated_Term( $this ) ); // Translated term sub model.

		$this->post_types = new Model\Post_Types( $this->post );
		$this->taxonomies = new Model\Taxonomies( $this->term );

		// We need to clean languages cache when editing a language and when modifying the permalink structure.
		add_action( 'edited_term_taxonomy', array( $this, 'clean_languages_cache' ), 10, 2 );
		add_action( 'update_option_permalink_structure', array( $this, 'clean_languages_cache' ) );
		add_action( 'update_option_siteurl', array( $this, 'clean_languages_cache' ) );
		add_action( 'update_option_home', array( $this, 'clean_languages_cache' ) );

		add_filter( 'get_terms_args', array( $this, 'get_terms_args' ) );

		// Just in case someone would like to display the language description ;).
		add_filter( 'language_description', '__return_empty_string' );
	}

	/**
	 * Backward compatibility for methods that have been moved to sub-models.
	 *
	 * @since 3.7
	 *
	 * @param string $name      Name of the method being called.
	 * @param array  $arguments Enumerated array containing the parameters passed to the $name'ed method.
	 * @return mixed
	 */
	public function __call( string $name, array $arguments ) {
		$methods = array(
			// Languages.
			'has_languages'               => array( $this->languages, 'has' ),
			'get_languages_list'          => array( $this->languages, 'get_list' ),
			'are_languages_ready'         => array( $this->languages, 'are_ready' ),
			'set_languages_ready'         => array( $this->languages, 'set_ready' ),
			'get_language'                => array( $this->languages, 'get' ),
			'add_language'                => array( $this->languages, 'add' ),
			'delete_language'             => array( $this->languages, 'delete' ),
			'update_language'             => array( $this->languages, 'update' ),
			'get_default_language'        => array( $this->languages, 'get_default' ),
			'update_default_lang'         => array( $this->languages, 'update_default' ),
			'maybe_create_language_terms' => array( $this->languages, 'maybe_create_terms' ),
			// Post types.
			'get_translated_post_types' => array( $this->post_types, 'get_translated' ),
			'is_translated_post_type'   => array( $this->post_types, 'is_translated' ),
			// Taxonomies.
			'get_translated_taxonomies'          => array( $this->taxonomies, 'get_translated' ),
			'is_translated_taxonomy'             => array( $this->taxonomies, 'is_translated' ),
			'get_filtered_taxonomies'            => array( $this->taxonomies, 'get_filtered' ),
			'is_filtered_taxonomy'               => array( $this->taxonomies, 'is_filtered' ),
			'get_filtered_taxonomies_query_vars' => array( $this->taxonomies, 'get_filtered_query_vars' ),
		);

		if ( isset( $methods[ $name ] ) ) {
			return call_user_func_array( $methods[ $name ], $arguments );
		}

		$debug = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			sprintf(
				'Call to undefined function PLL()->model->%1$s() in %2$s on line %3$s' . "\nError handler",
				esc_html( $name ),
				esc_html( $debug[0]['file'] ?? '' ),
				absint( $debug[0]['line'] ?? 0 )
			),
			E_USER_ERROR
		);
	}

	/**
	 * Cleans language cache
	 * can be called directly with no parameter
	 * called by the 'edited_term_taxonomy' filter with 2 parameters when count needs to be updated
	 *
	 * @since 1.2
	 *
	 * @param int    $term     not used
	 * @param string $taxonomy taxonomy name
	 * @return void
	 */
	public function clean_languages_cache( $term = 0, $taxonomy = null ): void {
		if ( empty( $taxonomy ) || 'language' === $taxonomy ) {
			$this->languages->clean_cache();
		}
	}

	/**
	 * Don't query term metas when only our taxonomies are queried
	 *
	 * @since 2.3
	 *
	 * @param array $args WP_Term_Query arguments
	 * @return array
	 */
	public function get_terms_args( $args ) {
		$taxonomies = $this->translatable_objects->get_taxonomy_names();

		if ( isset( $args['taxonomy'] ) && ! array_diff( (array) $args['taxonomy'], $taxonomies ) ) {
			$args['update_term_meta_cache'] = false;
		}
		return $args;
	}

	/**
	 * Adds terms clauses to the term query to filter them by languages.
	 *
	 * @since 1.2
	 *
	 * @param string[]           $clauses The list of sql clauses in terms query.
	 * @param PLL_Language|false $lang    PLL_Language object.
	 * @return string[]                   Modified list of clauses.
	 */
	public function terms_clauses( $clauses, $lang ) {
		if ( ! empty( $lang ) && false === strpos( $clauses['join'], 'pll_tr' ) ) {
			$clauses['join'] .= $this->term->join_clause();
			$clauses['where'] .= $this->term->where_clause( $lang );
		}
		return $clauses;
	}

	/**
	 * It is possible to have several terms with the same name in the same taxonomy ( one per language )
	 * but the native term_exists() will return true even if only one exists.
	 * So here the function adds the language parameter.
	 *
	 * @since 1.4
	 *
	 * @param string              $term_name The term name.
	 * @param string              $taxonomy  Taxonomy name.
	 * @param int                 $parent    Parent term id.
	 * @param string|PLL_Language $language  The language slug or object.
	 * @return int The `term_id` of the found term. 0 otherwise.
	 *
	 * @phpstan-return int<0, max>
	 */
	public function term_exists( $term_name, $taxonomy, $parent, $language ): int {
		global $wpdb;

		$language = $this->languages->get( $language );
		if ( empty( $language ) ) {
			return 0;
		}

		$term_name = trim( wp_unslash( $term_name ) );
		$term_name = _wp_specialchars( $term_name );

		$select = "SELECT t.term_id FROM $wpdb->terms AS t";
		$join   = " INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id";
		$join  .= $this->term->join_clause();
		$where  = $wpdb->prepare( ' WHERE tt.taxonomy = %s AND t.name = %s', $taxonomy, $term_name );
		$where .= $this->term->where_clause( $language );

		if ( $parent > 0 ) {
			$where .= $wpdb->prepare( ' AND tt.parent = %d', $parent );
		}

		// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
		$term_id = $wpdb->get_var( $select . $join . $where );
		return max( 0, (int) $term_id );
	}

	/**
	 * Checks if a term slug exists in a given language, taxonomy, hierarchy.
	 *
	 * @since 1.9
	 * @since 2.8 Moved from PLL_Share_Term_Slug::term_exists() to PLL_Model::term_exists_by_slug().
	 *
	 * @param string              $slug     The term slug to test.
	 * @param string|PLL_Language $language The language slug or object.
	 * @param string              $taxonomy Optional taxonomy name.
	 * @param int                 $parent   Optional parent term id.
	 * @return int The `term_id` of the found term. 0 otherwise.
	 */
	public function term_exists_by_slug( $slug, $language, $taxonomy = '', $parent = 0 ): int {
		global $wpdb;

		$language = $this->languages->get( $language );
		if ( empty( $language ) ) {
			return 0;
		}

		$select = "SELECT t.term_id FROM {$wpdb->terms} AS t";
		$join   = " INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id";
		$join  .= $this->term->join_clause();
		$where  = $wpdb->prepare( ' WHERE t.slug = %s', $slug );
		$where .= $this->term->where_clause( $language );

		if ( ! empty( $taxonomy ) ) {
			$where .= $wpdb->prepare( ' AND tt.taxonomy = %s', $taxonomy );
		}

		if ( $parent > 0 ) {
			$where .= $wpdb->prepare( ' AND tt.parent = %d', $parent );
		}

		// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $select . $join . $where );
	}

	/**
	 * Returns the number of posts per language in a date, author or post type archive.
	 *
	 * @since 1.2
	 *
	 * @param PLL_Language $lang PLL_Language instance.
	 * @param array        $q    {
	 *   WP_Query arguments:
	 *
	 *   @type string|string[] $post_type   Post type or array of post types.
	 *   @type int             $m           Combination YearMonth. Accepts any four-digit year and month.
	 *   @type int             $year        Four-digit year.
	 *   @type int             $monthnum    Two-digit month.
	 *   @type int             $day         Day of the month.
	 *   @type int             $author      Author id.
	 *   @type string          $author_name User 'user_nicename'.
	 *   @type string          $post_format Post format.
	 *   @type string          $post_status Post status.
	 * }
	 * @return int
	 *
	 * @phpstan-param array{
	 *     post_type?: non-falsy-string|array<non-falsy-string>,
	 *     post_status?: non-falsy-string,
	 *     m?: numeric-string,
	 *     year?: positive-int,
	 *     monthnum?: int<1, 12>,
	 *     day?: int<1, 31>,
	 *     author?: int<1, max>,
	 *     author_name?: non-falsy-string,
	 *     post_format?: non-falsy-string
	 * } $q
	 * @phpstan-return int<0, max>
	 */
	public function count_posts( $lang, $q = array() ): int {
		global $wpdb;

		$q = array_merge( array( 'post_type' => 'post', 'post_status' => 'publish' ), $q );

		if ( ! is_array( $q['post_type'] ) ) {
			$q['post_type'] = array( $q['post_type'] );
		}

		foreach ( $q['post_type'] as $key => $type ) {
			if ( ! post_type_exists( $type ) ) {
				unset( $q['post_type'][ $key ] );
			}
		}

		if ( empty( $q['post_type'] ) ) {
			$q['post_type'] = array( 'post' ); // We *need* a post type.
		}

		$cache_key = $this->cache->get_unique_key( 'pll_count_posts_', $q );
		$counts    = wp_cache_get( $cache_key, 'counts' );

		if ( ! is_array( $counts ) ) {
			$counts  = array();
			$select  = "SELECT pll_tr.term_taxonomy_id, COUNT( * ) AS num_posts FROM {$wpdb->posts}";
			$join    = $this->post->join_clause();
			$where   = sprintf( " WHERE post_status = '%s'", esc_sql( $q['post_status'] ) );
			$where  .= sprintf( " AND {$wpdb->posts}.post_type IN ( '%s' )", implode( "', '", esc_sql( $q['post_type'] ) ) );
			$where  .= $this->post->where_clause( $this->languages->get_list() );
			$groupby = ' GROUP BY pll_tr.term_taxonomy_id';

			if ( ! empty( $q['m'] ) ) {
				$q['m'] = '' . preg_replace( '|[^0-9]|', '', $q['m'] );
				$where .= $wpdb->prepare( " AND YEAR( {$wpdb->posts}.post_date ) = %d", substr( $q['m'], 0, 4 ) );
				if ( strlen( $q['m'] ) > 5 ) {
					$where .= $wpdb->prepare( " AND MONTH( {$wpdb->posts}.post_date ) = %d", substr( $q['m'], 4, 2 ) );
				}
				if ( strlen( $q['m'] ) > 7 ) {
					$where .= $wpdb->prepare( " AND DAYOFMONTH( {$wpdb->posts}.post_date ) = %d", substr( $q['m'], 6, 2 ) );
				}
			}

			if ( ! empty( $q['year'] ) ) {
				$where .= $wpdb->prepare( " AND YEAR( {$wpdb->posts}.post_date ) = %d", $q['year'] );
			}

			if ( ! empty( $q['monthnum'] ) ) {
				$where .= $wpdb->prepare( " AND MONTH( {$wpdb->posts}.post_date ) = %d", $q['monthnum'] );
			}

			if ( ! empty( $q['day'] ) ) {
				$where .= $wpdb->prepare( " AND DAYOFMONTH( {$wpdb->posts}.post_date ) = %d", $q['day'] );
			}

			if ( ! empty( $q['author_name'] ) ) {
				$author = get_user_by( 'slug', sanitize_title_for_query( $q['author_name'] ) );
				if ( $author ) {
					$q['author'] = $author->ID;
				}
			}

			if ( ! empty( $q['author'] ) ) {
				$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_author = %d", $q['author'] );
			}

			// Filtered taxonomies ( post_format ).
			foreach ( $this->taxonomies->get_filtered_query_vars() as $tax_qv ) {

				if ( ! empty( $q[ $tax_qv ] ) ) {
					$join .= " INNER JOIN {$wpdb->term_relationships} AS tr ON tr.object_id = {$wpdb->posts}.ID";
					$join .= " INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id";
					$join .= " INNER JOIN {$wpdb->terms} AS t ON t.term_id = tt.term_id";
					$where .= $wpdb->prepare( ' AND t.slug = %s', $q[ $tax_qv ] );
				}
			}

			// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
			$res = $wpdb->get_results( $select . $join . $where . $groupby, ARRAY_A );
			foreach ( (array) $res as $row ) {
				$counts[ $row['term_taxonomy_id'] ] = $row['num_posts'];
			}

			wp_cache_set( $cache_key, $counts, 'counts' );
		}

		$term_taxonomy_id = $lang->get_tax_prop( 'language', 'term_taxonomy_id' );
		return empty( $counts[ $term_taxonomy_id ] ) ? 0 : $counts[ $term_taxonomy_id ];
	}

	/**
	 * Setup the links model based on options.
	 *
	 * @since 1.2
	 *
	 * @return PLL_Links_Model
	 */
	public function get_links_model(): PLL_Links_Model {
		$c = array( 'Directory', 'Directory', 'Subdomain', 'Domain' );
		$class = get_option( 'permalink_structure' ) ? 'PLL_Links_' . $c[ $this->options['force_lang'] ] : 'PLL_Links_Default';

		/**
		 * Filters the links model class to use.
		 * /!\ this filter is fired *before* the $polylang object is available.
		 *
		 * @since 2.1.1
		 *
		 * @param string $class A class name: PLL_Links_Default, PLL_Links_Directory, PLL_Links_Subdomain, PLL_Links_Domain.
		 */
		$class = apply_filters( 'pll_links_model', $class );

		return new $class( $this );
	}

	/**
	 * Returns a list of object IDs without language (used in settings and wizard).
	 *
	 * @since 0.9
	 * @since 2.2.6 Added the `$limit` parameter.
	 * @since 3.4 Added the `$types` parameter.
	 *
	 * @param int      $limit Optional. Max number of IDs to return. Defaults to -1 (no limit).
	 * @param string[] $types Optional. Types to handle (@see PLL_Translatable_Object::get_type()). Defaults to
	 *                        an empty array (all types).
	 * @return int[][]|false {
	 *     IDs of objects without language.
	 *
	 *     @type int[] $posts Array of post ids.
	 *     @type int[] $terms Array of term ids.
	 * }
	 *
	 * @phpstan-param -1|positive-int $limit
	 */
	public function get_objects_with_no_lang( $limit = -1, array $types = array() ) {
		/**
		 * Filters the max number of IDs to return when searching objects with no language.
		 * This filter can be used to decrease the memory usage in case the number of objects
		 * without language is too big. Using a negative value is equivalent to have no limit.
		 *
		 * @since 2.2.6
		 * @since 3.4 Added the `$types` parameter.
		 *
		 * @param int      $limit Max number of IDs to retrieve from the database.
		 * @param string[] $types Types to handle (@see PLL_Translatable_Object::get_type()). An empty array means all
		 *                        types.
		 */
		$limit   = apply_filters( 'get_objects_with_no_lang_limit', $limit, $types );
		$limit   = $limit < 1 ? -1 : max( (int) $limit, 1 );
		$objects = array();

		foreach ( $this->translatable_objects as $type => $object ) {
			if ( ! empty( $types ) && ! in_array( $type, $types, true ) ) {
				continue;
			}

			$ids = $object->get_objects_with_no_lang( $limit );

			if ( empty( $ids ) ) {
				continue;
			}

			// The trailing 's' in the array key is for backward compatibility.
			$objects[ "{$type}s" ] = $ids;
		}

		$objects = ! empty( $objects ) ? $objects : false;

		/**
		 * Filters the list of IDs of untranslated objects.
		 *
		 * @since 0.9
		 * @since 3.4 Added the `$limit` and `$types` parameters.
		 *
		 * @param int[][]|false $objects List of lists of object IDs, `false` if no IDs found.
		 * @param int           $limit   Max number of IDs to retrieve from the database.
		 * @param string[]      $types   Types to handle (@see PLL_Translatable_Object::get_type()). An empty array
		 *                               means all types.
		 */
		return apply_filters( 'pll_get_objects_with_no_lang', $objects, $limit, $types );
	}

	/**
	 * Returns ids of post without language.
	 *
	 * @since 3.1
	 *
	 * @param string|string[] $post_types A translated post type or an array of translated post types.
	 * @param int             $limit      Max number of objects to return. `-1` to return all of them.
	 * @return int[]
	 *
	 * @phpstan-param -1|positive-int $limit
	 * @phpstan-return list<positive-int>
	 */
	public function get_posts_with_no_lang( $post_types, $limit ): array {
		return $this->translatable_objects->get( 'post' )->get_objects_with_no_lang( $limit, (array) $post_types );
	}

	/**
	 * Returns ids of terms without language.
	 *
	 * @since 3.1
	 *
	 * @param string|string[] $taxonomies A translated taxonomy or an array of taxonomies post types.
	 * @param int             $limit      Max number of objects to return. `-1` to return all of them.
	 * @return int[]
	 *
	 * @phpstan-param -1|positive-int $limit
	 * @phpstan-return list<positive-int>
	 */
	public function get_terms_with_no_lang( $taxonomies, $limit ): array {
		return $this->translatable_objects->get( 'term' )->get_objects_with_no_lang( $limit, (array) $taxonomies );
	}

	/**
	 * Assigns the default language to objects in mass.
	 *
	 * @since 1.2
	 * @since 3.4 Moved from PLL_Admin_Model class.
	 *            Removed `$limit` parameter, added `$lang` and `$types` parameters.
	 *
	 * @param PLL_Language|null $lang  Optional. The language to assign to objects. Defaults to `null` (default language).
	 * @param string[]          $types Optional. Types to handle (@see PLL_Translatable_Object::get_type()). Defaults
	 *                                 to an empty array (all types).
	 * @return void
	 */
	public function set_language_in_mass( $lang = null, array $types = array() ): void {
		if ( ! $lang instanceof PLL_Language ) {
			$lang = $this->languages->get_default();

			if ( empty( $lang ) ) {
				return;
			}
		}

		// 1000 is an arbitrary value that will be filtered by `get_objects_with_no_lang_limit`.
		$nolang = $this->get_objects_with_no_lang( 1000, $types );

		if ( empty( $nolang ) ) {
			return;
		}

		/**
		 * Keep track of types where we set the language:
		 * those are types where we may have more items to process if we have more than 1000 items in total.
		 * This will prevent unnecessary SQL queries in the next recursion: if we have 0 items in this recursion for
		 * a type, we'll still have 0 in the next one, no need for a new query.
		 */
		$types_with_objects = array();

		foreach ( $this->translatable_objects as $type => $object ) {
			if ( empty( $nolang[ "{$type}s" ] ) ) {
				continue;
			}

			if ( ! empty( $types ) && ! in_array( $type, $types, true ) ) {
				continue;
			}

			$object->set_language_in_mass( $nolang[ "{$type}s" ], $lang );
			$types_with_objects[] = $type;
		}

		if ( empty( $types_with_objects ) ) {
			return;
		}

		$this->set_language_in_mass( $lang, $types_with_objects );
	}
}
