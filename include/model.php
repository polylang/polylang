<?php
/**
 * @package Polylang
 */

/**
 * Setups the language and translations model based on WordPress taxonomies
 *
 * @since 1.2
 */
class PLL_Model {
	/**
	 * Internal non persistent cache object.
	 *
	 * @var PLL_Cache
	 */
	public $cache;

	/**
	 * Stores the plugin options.
	 *
	 * @var array
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
	 * Constructor.
	 * Setups translated objects sub models.
	 * Setups filters and actions.
	 *
	 * @since 1.2
	 *
	 * @param array $options Polylang options.
	 */
	public function __construct( &$options ) {
		$this->options = &$options;

		$this->cache = new PLL_Cache();
		$this->translatable_objects = new PLL_Translatable_Objects();
		$this->post = $this->translatable_objects->register( new PLL_Translated_Post( $this ) ); // Translated post sub model.
		$this->term = $this->translatable_objects->register( new PLL_Translated_Term( $this ) ); // Translated term sub model.

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
	 * Checks if there are languages or not.
	 *
	 * @since 3.3
	 *
	 * @return bool True if there are, false otherwise.
	 */
	public function has_languages() {
		if ( ! empty( $this->cache->get( 'languages' ) ) ) {
			return true;
		}

		if ( ! empty( get_transient( 'pll_languages_list' ) ) ) {
			return true;
		}

		return ! empty( $this->get_language_terms() );
	}

	/**
	 * Returns the list of available languages.
	 * - Stores the list in a db transient (except flags), unless `PLL_CACHE_LANGUAGES` is set to false.
	 * - Caches the list (with flags) in a `PLL_Cache` object.
	 *
	 * @since 0.1
	 *
	 * @param array $args {
	 *   @type bool  $hide_empty Hides languages with no posts if set to true ( defaults to false ).
	 *   @type string $fields    Returns only that field if set; {@see PLL_Language} for a list of fields.
	 * }
	 * @return array List of PLL_Language objects or PLL_Language object properties.
	 */
	public function get_languages_list( $args = array() ) {
		$languages = $this->cache->get( 'languages' );

		if ( ! is_array( $languages ) ) {
			if ( defined( 'PLL_CACHE_LANGUAGES' ) && ! PLL_CACHE_LANGUAGES ) {
				// Create the languages from taxonomies.
				$languages = $this->get_languages_from_taxonomies();
			} else {
				$languages = get_transient( 'pll_languages_list' );

				if ( ! is_array( $languages ) ) {
					// Create the languages from taxonomies.
					$languages = $this->get_languages_from_taxonomies();
				} else {
					// Create the languages directly from arrays stored in the transient.
					foreach ( $languages as $k => $v ) {
						$languages[ $k ] = PLL_Language_Factory::get( $v );
					}

					// Re-index.
					$languages = array_values( $languages );
				}
			}

			/**
			 * Filters the list of languages *after* it is stored in the persistent cache.
			 * /!\ This filter is fired *before* the $polylang object is available.
			 *
			 * @since 1.8
			 *
			 * @param PLL_Language[] $languages The list of language objects.
			 */
			$languages = apply_filters( 'pll_after_languages_cache', $languages );
			$this->cache->set( 'languages', $languages );
		}

		// Remove empty languages if requested.
		if ( ! empty( $args['hide_empty'] ) ) {
			foreach ( $languages as $key => $language ) {
				if ( empty( $language->get_tax_prop( 'language', 'count' ) ) ) {
					unset( $languages[ $key ] );
				}
			}
		}

		return empty( $args['fields'] ) ? $languages : wp_list_pluck( $languages, $args['fields'] );
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
	public function clean_languages_cache( $term = 0, $taxonomy = null ) {
		if ( empty( $taxonomy ) || 'language' === $taxonomy ) {
			delete_transient( 'pll_languages_list' );
			$this->cache->clean();
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
	 * Returns the language by its term_id, tl_term_id, slug or locale.
	 *
	 * @since 0.1
	 *
	 * @param mixed $value term_id, tl_term_id, slug or locale of the queried language.
	 * @return PLL_Language|false Language object, false if no language found.
	 */
	public function get_language( $value ) {
		if ( is_object( $value ) ) {
			return $value instanceof PLL_Language ? $value : $this->get_language( $value->term_id ); // will force cast to PLL_Language
		}

		if ( false === $return = $this->cache->get( 'language:' . $value ) ) {
			foreach ( $this->get_languages_list() as $lang ) {
				foreach ( $lang->get_tax_props( 'term_id' ) as $term_id ) {
					$this->cache->set( 'language:' . $term_id, $lang );
				}
				$this->cache->set( 'language:' . $lang->slug, $lang );
				$this->cache->set( 'language:' . $lang->locale, $lang );
				$this->cache->set( 'language:' . $lang->w3c, $lang );
			}
			$return = $this->cache->get( 'language:' . $value );
		}

		return $return;
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
	 * Returns post types that need to be translated.
	 * The post types list is cached for better better performance.
	 * The method waits for 'after_setup_theme' to apply the cache
	 * to allow themes adding the filter in functions.php.
	 *
	 * @since 1.2
	 *
	 * @param bool $filter True if we should return only valid registered post types.
	 * @return string[] Post type names for which Polylang manages languages and translations.
	 */
	public function get_translated_post_types( $filter = true ) {
		$object = $this->translatable_objects->get( 'post' );

		if ( empty( $object ) ) {
			return array();
		}

		return $object->get_translated_object_types( $filter );
	}

	/**
	 * Returns true if Polylang manages languages and translations for this post type.
	 *
	 * @since 1.2
	 *
	 * @param string|string[] $post_type Post type name or array of post type names.
	 * @return bool
	 */
	public function is_translated_post_type( $post_type ) {
		$object = $this->translatable_objects->get( 'post' );

		return ! empty( $object ) && $object->is_translated_object_type( $post_type );
	}

	/**
	 * Returns taxonomies that need to be translated.
	 * The taxonomies list is cached for better better performance.
	 * The method waits for 'after_setup_theme' to apply the cache
	 * to allow themes adding the filter in functions.php.
	 *
	 * @since 1.2
	 *
	 * @param bool $filter True if we should return only valid registered taxonomies.
	 * @return string[] Array of registered taxonomy names for which Polylang manages languages and translations.
	 */
	public function get_translated_taxonomies( $filter = true ) {
		$object = $this->translatable_objects->get( 'term' );

		if ( empty( $object ) ) {
			return array();
		}

		return $object->get_translated_object_types( $filter );
	}

	/**
	 * Returns true if Polylang manages languages and translations for this taxonomy.
	 *
	 * @since 1.2
	 *
	 * @param string|string[] $tax Taxonomy name or array of taxonomy names.
	 * @return bool
	 */
	public function is_translated_taxonomy( $tax ) {
		$object = $this->translatable_objects->get( 'term' );

		return ! empty( $object ) && $object->is_translated_object_type( $tax );
	}

	/**
	 * Return taxonomies that need to be filtered (post_format like).
	 *
	 * @since 1.7
	 *
	 * @param bool $filter True if we should return only valid registered taxonomies.
	 * @return string[] Array of registered taxonomy names.
	 */
	public function get_filtered_taxonomies( $filter = true ) {
		if ( did_action( 'after_setup_theme' ) ) {
			static $taxonomies = null;
		}

		if ( empty( $taxonomies ) ) {
			$taxonomies = array( 'post_format' => 'post_format' );

			/**
			 * Filters the list of taxonomies not translatable but filtered by language.
			 * Includes only the post format by default
			 * The filter must be added soon in the WordPress loading process:
			 * in a function hooked to ‘plugins_loaded’ or directly in functions.php for themes.
			 *
			 * @since 1.7
			 *
			 * @param string[] $taxonomies  List of taxonomy names.
			 * @param bool     $is_settings True when displaying the list of custom taxonomies in Polylang settings.
			 */
			$taxonomies = apply_filters( 'pll_filtered_taxonomies', $taxonomies, false );
		}

		return $filter ? array_intersect( $taxonomies, get_taxonomies() ) : $taxonomies;
	}

	/**
	 * Returns true if Polylang filters this taxonomy per language.
	 *
	 * @since 1.7
	 *
	 * @param string|string[] $tax Taxonomy name or array of taxonomy names.
	 * @return bool
	 */
	public function is_filtered_taxonomy( $tax ) {
		$taxonomies = $this->get_filtered_taxonomies( false );
		return ( is_array( $tax ) && array_intersect( $tax, $taxonomies ) || in_array( $tax, $taxonomies ) );
	}

	/**
	 * Returns the query vars of all filtered taxonomies.
	 *
	 * @since 1.7
	 *
	 * @return string[]
	 */
	public function get_filtered_taxonomies_query_vars() {
		$query_vars = array();
		foreach ( $this->get_filtered_taxonomies() as $filtered_tax ) {
			$tax = get_taxonomy( $filtered_tax );
			if ( ! empty( $tax ) && is_string( $tax->query_var ) ) {
				$query_vars[] = $tax->query_var;
			}
		}
		return $query_vars;
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
	 */
	public function term_exists( $term_name, $taxonomy, $parent, $language ) {
		global $wpdb;

		$language = $this->get_language( $language );
		if ( empty( $language ) ) {
			return 0;
		}

		$term_name = trim( wp_unslash( $term_name ) );
		$term_name = _wp_specialchars( $term_name );

		$select = "SELECT t.term_id FROM $wpdb->terms AS t";
		$join = " INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id";
		$join .= $this->term->join_clause();
		$where = $wpdb->prepare( ' WHERE tt.taxonomy = %s AND t.name = %s', $taxonomy, $term_name );
		$where .= $this->term->where_clause( $language );

		if ( $parent > 0 ) {
			$where .= $wpdb->prepare( ' AND tt.parent = %d', $parent );
		}

		// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_var( $select . $join . $where );
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
	public function term_exists_by_slug( $slug, $language, $taxonomy = '', $parent = 0 ) {
		global $wpdb;

		$language = $this->get_language( $language );
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
		return $wpdb->get_var( $select . $join . $where );
	}


	/**
	 * Gets the number of posts per language in a date, author or post type archive.
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
	 */
	public function count_posts( $lang, $q = array() ) {
		global $wpdb;

		$q = wp_parse_args( $q, array( 'post_type' => 'post', 'post_status' => 'publish' ) );

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

		$cache_key = 'pll_count_posts_' . md5( maybe_serialize( $q ) );
		$counts = wp_cache_get( $cache_key, 'counts' );

		if ( false === $counts ) {
			$counts = array();

			$select = "SELECT pll_tr.term_taxonomy_id, COUNT( * ) AS num_posts FROM {$wpdb->posts}";
			$join = $this->post->join_clause();
			$where = sprintf( " WHERE post_status = '%s'", esc_sql( $q['post_status'] ) );
			$where .= sprintf( " AND {$wpdb->posts}.post_type IN ( '%s' )", implode( "', '", esc_sql( $q['post_type'] ) ) );
			$where .= $this->post->where_clause( $this->get_languages_list() );
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
			foreach ( $this->get_filtered_taxonomies_query_vars() as $tax_qv ) {

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
	public function get_links_model() {
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
	 * Returns posts and terms ids without language ( used in settings ).
	 *
	 * @since 0.9
	 * @since 2.2.6 Add the $limit argument.
	 *
	 * @param int $limit Max number of posts or terms to return. Defaults to -1 (no limit).
	 * @return array {
	 *     Objects without language.
	 *
	 *     @type int[] $posts Array of post ids.
	 *     @type int[] $terms Array of term ids.
	 * }
	 *
	 * @phpstan-param -1|int<1, max> $limit
	 */
	public function get_objects_with_no_lang( $limit = -1 ) {
		/**
		 * Filters the max number of posts or terms to return when searching objects with no language.
		 * This filter can be used to decrease the memory usage in case the number of objects
		 * without language is too big. Using a negative value is equivalent to have no limit.
		 *
		 * @since 2.2.6
		 *
		 * @param int $limit Max number of posts or terms to retrieve from the database.
		 */
		$limit   = apply_filters( 'get_objects_with_no_lang_limit', $limit );
		$limit   = $limit < 1 ? -1 : max( (int) $limit, 1 );
		$objects = array();

		foreach ( $this->translatable_objects as $type => $object ) {
			// The trailing 's' in the array key is for backward compatibility.
			$objects[ "{$type}s" ] = $object->get_objects_with_no_lang( $limit );
		}

		/**
		 * Filters the list of untranslated posts ids and terms ids
		 *
		 * @since 0.9
		 *
		 * @param array|false $objects false if no ids found, list of post and/or term ids otherwise.
		 */
		return apply_filters( 'pll_get_objects_with_no_lang', empty( array_filter( $objects ) ) ? false : $objects );
	}

	/**
	 * Returns ids of post without language.
	 *
	 * @since 3.1
	 *
	 * @param string|string[] $post_types A translated post type or an array of translated post types.
	 * @param int             $limit      Max number of posts to return.
	 * @return int[]
	 */
	public function get_posts_with_no_lang( $post_types, $limit ) {
		$object = $this->translatable_objects->get( 'post' );

		if ( empty( $object ) ) {
			return array();
		}

		return $object->get_objects_with_no_lang( $limit );
	}

	/**
	 * Returns ids of terms without language.
	 *
	 * @since 3.1
	 *
	 * @param string|string[] $taxonomies A translated taxonomy or an array of taxonomies post types.
	 * @param int             $limit      Max number of terms to return.
	 * @return int[]
	 */
	public function get_terms_with_no_lang( $taxonomies, $limit ) {
		$object = $this->translatable_objects->get( 'term' );

		if ( empty( $object ) ) {
			return array();
		}

		return $object->get_objects_with_no_lang( $limit );
	}

	/**
	 * Filters the ORDERBY clause of the languages query.
	 * This allows to order languages by `term_group` and `term_id`.
	 *
	 * @since 3.2.3
	 *
	 * @param  string   $orderby    `ORDERBY` clause of the terms query.
	 * @param  array    $args       An array of term query arguments.
	 * @param  string[] $taxonomies An array of taxonomy names.
	 * @return string
	 */
	public function filter_language_terms_orderby( $orderby, $args, $taxonomies ) {
		if ( ! is_array( $taxonomies ) || count( $taxonomies ) > 1 ) {
			return $orderby;
		}

		if ( 'language' !== reset( $taxonomies ) ) {
			return $orderby;
		}

		if ( empty( $orderby ) || ! is_string( $orderby ) ) {
			return $orderby;
		}

		if ( ! preg_match( '@^(?<alias>[^.]+)\.term_group$@', $orderby, $matches ) ) {
			return $orderby;
		}

		return sprintf( '%1$s.term_group, %1$s.term_id', $matches['alias'] );
	}

	/**
	 * Returns the list of available languages, based on the language taxonomy terms.
	 * Stores the list in a db transient and in a `PLL_Cache` object.
	 *
	 * @since 3.4
	 *
	 * @return PLL_Language[] An array of `PLL_Language` objects, array keys are the type.
	 *
	 * @phpstan-return list<PLL_Language>
	 */
	protected function get_languages_from_taxonomies() {
		/*
		 * Only terms of the taxonomy 'language' include a 'term_group' for the order.
		 * `array_reverse()` allows to make sure that the next loop fills the array
		 *  with these terms first, allowing to keep the languages order.
		 */
		$reversed_terms = array_reverse( $this->get_language_terms() );

		$terms_by_slug = array();

		foreach ( $reversed_terms as $term ) {
			if ( $term instanceof WP_Term ) {
				// Except for language taxonomy term slugs, remove 'pll_' prefix from the other language taxonomy term slugs.
				$key = 'language' === $term->taxonomy ? $term->slug : substr( $term->slug, 4 );
				$terms_by_slug[ $key ][ $term->taxonomy ] = $term;
			}
		}

		// Restore the right order after the first `array_reverse()`.
		$terms_by_slug = array_reverse( $terms_by_slug );

		$languages = array();

		foreach ( $terms_by_slug as $lang_terms ) {
			$languages[] = PLL_Language_Factory::get_from_terms( $lang_terms );
		}

		// We will need the languages list to allow its access in the filter below.
		$this->cache->set( 'languages', $languages );

		/**
		 * Filters the list of languages *before* it is stored in the persistent cache.
		 * /!\ This filter is fired *before* the $polylang object is available.
		 *
		 * @since 1.7.5
		 *
		 * @param PLL_Language[] $languages The list of language objects.
		 * @param PLL_Model      $model     PLL_Model object.
		 */
		$languages = apply_filters( 'pll_languages_list', $languages, $this );

		/*
		 * Don't store directly objects as it badly break with some hosts ( GoDaddy ) due to race conditions when using object cache.
		 * Thanks to captin411 for catching this!
		 * @see https://wordpress.org/support/topic/fatal-error-pll_model_languages_list?replies=8#post-6782255
		 */
		$languages_data = array_map(
			function ( $language ) {
				return $language->get_object_vars();
			},
			$languages
		);
		set_transient( 'pll_languages_list', $languages_data );

		/** @var list<PLL_Language> $languages */
		return $languages;
	}

	/**
	 * Returns the list of existing language terms.
	 * - Returns all terms, that are or not assigned to posts.
	 * - Terms are ordered by `term_group` and `term_id` (see `PLL_Model->filter_language_terms_orderby()`).
	 *
	 * @since 3.2.3
	 *
	 * @return WP_Term[]
	 */
	protected function get_language_terms() {
		add_filter( 'get_terms_orderby', array( $this, 'filter_language_terms_orderby' ), 10, 3 );
		$terms = get_terms(
			array(
				'taxonomy'   => $this->translatable_objects->get_taxonomy_names( array( 'language' ) ),
				'orderby'    => 'term_group',
				'hide_empty' => false,
			)
		);
		remove_filter( 'get_terms_orderby', array( $this, 'filter_language_terms_orderby' ) );

		return empty( $terms ) || is_wp_error( $terms ) ? array() : $terms;
	}
}
