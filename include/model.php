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
	 * @return void
	 */
	public function __construct( &$options ) {
		$this->options = &$options;

		$this->cache = new PLL_Cache();
		$this->post = new PLL_Translated_Post( $this ); // Translated post sub model.
		$this->term = new PLL_Translated_Term( $this ); // Translated term sub model.

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
		if ( false !== $this->cache->get( 'languages' ) ) {
			return true;
		}

		if ( false !== get_transient( 'pll_languages_list' ) ) {
			return true;
		}

		return ! empty( $this->get_language_terms() );
	}

	/**
	 * Returns the list of available languages.
	 * - Stores the list in a db transient (except flags), unless the constant `PLL_CACHE_LANGUAGES` is set to false.
	 * - Caches the list (with flags) in a `PLL_Cache` object.
	 *
	 * @since 0.1
	 *
	 * @param array<bool|string> $args {
	 *     @type bool   $hide_empty Hides languages with no posts if set to true. Default is false.
	 *     @type string $fields     Returns only that field if set; {@see PLL_Language} for a list of fields.
	 * }
	 * @return array<PLL_Language|string|int|null> List of `PLL_Language` objects or `PLL_Language` object properties.
	 *
	 * @phpstan-param array{hide_empty?:bool,fields?:string} $args
	 */
	public function get_languages_list( array $args = array() ) {
		if ( false === $languages = $this->cache->get( 'languages' ) ) {
			$languages = array();

			// Create the languages from taxonomies.
			if ( ( defined( 'PLL_CACHE_LANGUAGES' ) && ! PLL_CACHE_LANGUAGES ) || false === ( $languages = get_transient( 'pll_languages_list' ) ) ) {
				$languages = array();

				$post_languages = $this->get_language_terms();

				$term_languages = get_terms( 'term_language', array( 'hide_empty' => false ) );
				$term_languages = empty( $term_languages ) || is_wp_error( $term_languages ) ?
					array() : array_combine( wp_list_pluck( $term_languages, 'slug' ), $term_languages );

				if ( ! empty( $post_languages ) && ! empty( $term_languages ) ) {
					foreach ( $post_languages as $k => $v ) {
						if ( isset( $term_languages[ 'pll_' . $v->slug ] ) ) {
							$languages[ $k ] = new PLL_Language( $v, $term_languages[ 'pll_' . $v->slug ] );
						}
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
					set_transient( 'pll_languages_list', array_map( 'get_object_vars', $languages ) );
				}
			}

			// Create the languages directly from arrays stored in transients.
			else {
				foreach ( $languages as $k => $v ) {
					$languages[ $k ] = new PLL_Language( $v );
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

		$args = wp_parse_args( $args, array( 'hide_empty' => false ) );

		// Remove empty languages if requested.
		if ( $args['hide_empty'] ) {
			$languages = wp_list_filter( $languages, array( 'count' => 0 ), 'NOT' );
		}

		if ( empty( $args['fields'] ) || ! is_string( $args['fields'] ) ) {
			/** @var array<PLL_Language> */
			return $languages;
		}

		/** @var array<string|int|null> */
		return wp_list_pluck( $languages, $args['fields'] );
	}

	/**
	 * Cleans language cache.
	 * Can be called directly with no parameter.
	 * Called by the 'edited_term_taxonomy' filter with 2 parameters when count needs to be updated.
	 *
	 * @since 1.2
	 *
	 * @param int    $term     Not used.
	 * @param string $taxonomy Taxonomy name.
	 * @return void
	 */
	public function clean_languages_cache( $term = 0, $taxonomy = '' ) {
		if ( empty( $taxonomy ) || 'language' === $taxonomy ) {
			delete_transient( 'pll_languages_list' );
			$this->cache->clean();
		}
	}

	/**
	 * Filters `WP_Term_Query`'s arguments to prevent querying term metas when only our taxonomies are queried.
	 *
	 * @since 2.3
	 *
	 * @param array $args `WP_Term_Query`'s arguments.
	 * @return array
	 */
	public function get_terms_args( $args ) {
		if ( isset( $args['taxonomy'] ) && ! array_diff( (array) $args['taxonomy'], array( 'language', 'term_language', 'post_translations', 'term_translations' ) ) ) {
			$args['update_term_meta_cache'] = false;
		}
		return $args;
	}

	/**
	 * Returns a language by its `term_id`, `tl_term_id`, `slug`, `locale`, or `w3c`.
	 *
	 * @since 0.1
	 *
	 * @param PLL_Language|object|string|int $value `term_id`, `tl_term_id`, `slug`, `locale`, or `w3c` of the queried
	 *                                              language.
	 * @return PLL_Language|false                   `PLL_Language` object, false if no language found.
	 */
	public function get_language( $value ) {
		if ( is_object( $value ) ) {
			return $value instanceof PLL_Language ? $value : $this->get_language( $value->term_id ); // Will force cast to PLL_Language.
		}

		if ( false === $return = $this->cache->get( 'language:' . $value ) ) {
			foreach ( $this->get_languages_list() as $lang ) {
				$this->cache->set( 'language:' . $lang->term_id, $lang );
				$this->cache->set( 'language:' . $lang->tl_term_id, $lang );
				$this->cache->set( 'language:' . $lang->slug, $lang );
				$this->cache->set( 'language:' . $lang->locale, $lang );
				$this->cache->set( 'language:' . $lang->w3c, $lang );
			}
			$return = $this->cache->get( 'language:' . $value );
		}

		return $return;
	}

	/**
	 * Filters `WP_Term_Query`'s query clauses to filter them by languages.
	 *
	 * @since 1.2
	 *
	 * @param string[]           $clauses The list of sql clauses in terms query.
	 * @param PLL_Language|false $lang    `PLL_Language` object.
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
	 * The post types list is cached for better performance.
	 * The method waits for 'after_setup_theme' to apply the cache
	 * to allow themes adding the filter in `functions.php`.
	 *
	 * @since 1.2
	 *
	 * @param bool $filter False to also return not-registered post types. Default is true.
	 * @return string[]    Post type names for which Polylang manages languages and translations.
	 */
	public function get_translated_post_types( $filter = true ) {
		if ( false === $post_types = $this->cache->get( 'post_types' ) ) {
			$post_types = array( 'post' => 'post', 'page' => 'page', 'wp_block' => 'wp_block' );

			if ( ! empty( $this->options['media_support'] ) ) {
				$post_types['attachment'] = 'attachment';
			}

			if ( ! empty( $this->options['post_types'] ) && is_array( $this->options['post_types'] ) ) {
				$post_types = array_merge( $post_types, array_combine( $this->options['post_types'], $this->options['post_types'] ) );
			}

			/**
			 * Filters the list of post types available for translation.
			 * The default are post types which have the parameter `public` set to true.
			 * The filter must be added soon in the WordPress loading process:
			 * in a function hooked to `plugins_loaded` or directly in functions.php for themes.
			 *
			 * @since 0.8
			 *
			 * @param string[] $post_types  List of post type names.
			 * @param bool     $is_settings True when displaying the list of custom post types in Polylang settings.
			 */
			$post_types = apply_filters( 'pll_get_post_types', $post_types, false );

			if ( did_action( 'after_setup_theme' ) ) {
				$this->cache->set( 'post_types', $post_types );
			}
		}

		return $filter ? array_intersect( $post_types, get_post_types() ) : $post_types;
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
		$post_types = $this->get_translated_post_types( false );
		return ( is_array( $post_type ) && array_intersect( $post_type, $post_types ) || in_array( $post_type, $post_types ) || 'any' === $post_type && ! empty( $post_types ) );
	}

	/**
	 * Returns taxonomies that need to be translated.
	 *
	 * @since 1.2
	 *
	 * @param bool $filter False to also return not-registered taxonomies. Default is true.
	 * @return string[]    Array of registered taxonomy names for which Polylang manages languages and translations.
	 */
	public function get_translated_taxonomies( $filter = true ) {
		if ( false === $taxonomies = $this->cache->get( 'taxonomies' ) ) {
			$taxonomies = array( 'category' => 'category', 'post_tag' => 'post_tag' );

			if ( ! empty( $this->options['taxonomies'] ) && is_array( $this->options['taxonomies'] ) ) {
				$taxonomies = array_merge( $taxonomies, array_combine( $this->options['taxonomies'], $this->options['taxonomies'] ) );
			}

			/**
			 * Filters the list of taxonomies available for translation.
			 * The default are taxonomies which have the parameter `public` set to true.
			 * The filter must be added soon in the WordPress loading process:
			 * in a function hooked to `plugins_loaded` or directly in functions.php for themes.
			 *
			 * @since 0.8
			 *
			 * @param string[] $taxonomies  List of taxonomy names.
			 * @param bool     $is_settings True when displaying the list of custom taxonomies in Polylang settings.
			 */
			$taxonomies = apply_filters( 'pll_get_taxonomies', $taxonomies, false );
			if ( did_action( 'after_setup_theme' ) ) {
				$this->cache->set( 'taxonomies', $taxonomies );
			}
		}

		return $filter ? array_intersect( $taxonomies, get_taxonomies() ) : $taxonomies;
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
		$taxonomies = $this->get_translated_taxonomies( false );
		return ( is_array( $tax ) && array_intersect( $tax, $taxonomies ) || in_array( $tax, $taxonomies ) );
	}

	/**
	 * Returns taxonomies that need to be filtered (post_format like).
	 *
	 * @since 1.7
	 *
	 * @param bool $filter False to also return not-registered taxonomies. Default is true.
	 * @return string[]    Array of registered taxonomy names.
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
			 * in a function hooked to `plugins_loaded` or directly in functions.php for themes.
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
	 * @return array<string>
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
	 * It is possible to have several terms with the same name in the same taxonomy (one per language)
	 * but the native `term_exists()` will return true even if only one exists.
	 * So here the function adds the language parameter.
	 *
	 * @since 1.4
	 * @global wpdb $wpdb
	 *
	 * @param string              $term_name The term name.
	 * @param string              $taxonomy  Taxonomy name.
	 * @param int                 $parent    Parent term id.
	 * @param string|PLL_Language $language  The language slug or object.
	 * @return int|null                      The `term_id` of the found term. Null otherwise.
	 */
	public function term_exists( $term_name, $taxonomy, $parent, $language ) {
		global $wpdb;

		$language = $this->get_language( $language );

		if ( empty( $language ) ) {
			return null;
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
		$result = $wpdb->get_var( $select . $join . $where );

		return null === $result ? null : (int) $result;
	}

	/**
	 * Checks if a term slug exists in a given language, taxonomy, hierarchy.
	 *
	 * @since 1.9
	 * @since 2.8 Moved from `PLL_Share_Term_Slug::term_exists()` to `PLL_Model::term_exists_by_slug()`.
	 * @global wpdb $wpdb
	 *
	 * @param string              $slug     The term slug to test.
	 * @param string|PLL_Language $language The language slug or object.
	 * @param string              $taxonomy Optional taxonomy name.
	 * @param int                 $parent   Optional parent term id.
	 * @return int|null                     The `term_id` of the found term. Null otherwise.
	 */
	public function term_exists_by_slug( $slug, $language, $taxonomy = '', $parent = 0 ) {
		global $wpdb;

		$language = $this->get_language( $language );

		if ( empty( $language ) ) {
			return null;
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
		$result = $wpdb->get_var( $select . $join . $where );

		return null === $result ? null : (int) $result;
	}


	/**
	 * Returns the number of posts per language in a date, author, or post type archive.
	 *
	 * @since 1.2
	 * @global wpdb $wpdb
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
	 *   post_type?:string|string[],
	 *   m?:int<0,max>,
	 *   year?:int<0,max>,
	 *   monthnum?:int<0,max>,
	 *   day?:int<0,max>,
	 *   author?:int<0,max>,
	 *   author_name?:string,
	 *   post_format?:string,
	 *   post_status?:string
	 * } $q
	 * @phpstan-return int<0,max>
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
		$counts    = wp_cache_get( $cache_key, 'counts' );

		if ( ! is_array( $counts ) ) {
			$counts = array();

			$select  = "SELECT pll_tr.term_taxonomy_id, COUNT( * ) AS num_posts FROM {$wpdb->posts}";
			$join    = $this->post->join_clause();
			$where   = sprintf( " WHERE post_status = '%s'", esc_sql( $q['post_status'] ) );
			$where  .= sprintf( " AND {$wpdb->posts}.post_type IN ( '%s' )", implode( "', '", esc_sql( $q['post_type'] ) ) );
			$where  .= $this->post->where_clause( $this->get_languages_list() );
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

		/** @var int<0,max> */
		return empty( $counts[ $lang->term_taxonomy_id ] ) ? 0 : (int) $counts[ $lang->term_taxonomy_id ];
	}

	/**
	 * Setups the links model based on options.
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
		 * /!\ Tthis filter is fired *before* the $polylang object is available.
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
	 * @since 2.2.6 Added the $limit argument.
	 *
	 * @param int $limit Max number of posts or terms to return. Defaults to 0 (no limit).
	 * @return array<array<int>>|false {
	 *     Object IDs without language.
	 *
	 *     @type int[] $posts Array of post ids.
	 *     @type int[] $terms Array of term ids.
	 * }
	 *
	 * @phpstan-param int<0,max> $limit
	 * @phpstan-return array{
	 *     posts:array<int<1,max>>,
	 *     terms:array<int<1,max>>
	 * }|false
	 */
	public function get_objects_with_no_lang( $limit = 0 ) {
		/**
		 * Filters the max number of posts or terms to return when searching objects with no language.
		 * This filter can be used to decrease the memory usage in case the number of objects
		 * without language is too big. Using a negative value is equivalent to have no limit.
		 *
		 * @since 2.2.6
		 *
		 * @param int $limit Max number of posts or terms to retrieve from the database.
		 */
		$limit = apply_filters( 'get_objects_with_no_lang_limit', $limit );
		/** @var int<0,max> */
		$limit = max( 0, (int) $limit );

		$posts = $this->get_posts_with_no_lang( $this->get_translated_post_types(), $limit );
		$terms = $this->get_terms_with_no_lang( $this->get_translated_taxonomies(), $limit );

		if ( ! empty( $posts ) || ! empty( $terms ) ) {
			$objects = array(
				'posts' => $posts,
				'terms' => $terms,
			);
		} else {
			$objects = false;
		}

		/**
		 * Filters the list of untranslated posts ids and terms ids
		 *
		 * @since 0.9
		 *
		 * @param array|false $objects false if no ids found, list of post and/or term ids otherwise.
		 *
		 * @phpstan-param array{
		 *     posts:array<int<1,max>>,
		 *     terms:array<int<1,max>>
		 * }|false $objects
		 * @phpstan-var array{
		 *     posts:array<int<1,max>>,
		 *     terms:array<int<1,max>>
		 * }|false $objects
		 */
		return apply_filters( 'pll_get_objects_with_no_lang', $objects );
	}

	/**
	 * Returns the ids of posts without language.
	 *
	 * @since 3.1
	 *
	 * @param string|string[] $post_types A translated post type or an array of translated post types.
	 * @param int             $limit      Max number of posts to return.
	 * @return int[]
	 *
	 * @phpstan-param int<0,max> $limit
	 * @phpstan-return array<int<1,max>>
	 */
	public function get_posts_with_no_lang( $post_types, $limit ) {
		/** @var array<int<1,max>> */
		return get_posts(
			array(
				'numberposts' => $limit,
				'nopaging'    => $limit <= 0,
				'post_type'   => $post_types,
				'post_status' => 'any',
				'fields'      => 'ids',
				'tax_query'   => array(
					array(
						'taxonomy' => 'language',
						'terms'    => $this->get_languages_list( array( 'fields' => 'term_id' ) ),
						'operator' => 'NOT IN',
					),
				),
			)
		);
	}

	/**
	 * Returns the ids of terms without language.
	 *
	 * @since 3.1
	 * @global wpdb $wpdb
	 *
	 * @param string|string[] $taxonomies A translated taxonomy or an array of taxonomies post types.
	 * @param int             $limit      Max number of terms to return.
	 * @return int[]
	 *
	 * @phpstan-param int<0,max> $limit
	 * @phpstan-return array<int<1,max>>
	 */
	public function get_terms_with_no_lang( $taxonomies, $limit ) {
		global $wpdb;

		$taxonomies = (array) $taxonomies;

		$sql = sprintf(
			"SELECT {$wpdb->term_taxonomy}.term_id FROM {$wpdb->term_taxonomy}
			WHERE taxonomy IN ('%s')
			AND {$wpdb->term_taxonomy}.term_id NOT IN (
				SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN (%s)
			)
			%s",
			implode( "','", esc_sql( $taxonomies ) ),
			implode( ',', array_map( 'intval', $this->get_languages_list( array( 'fields' => 'tl_term_taxonomy_id' ) ) ) ),
			$limit > 0 ? sprintf( 'LIMIT %d', intval( $limit ) ) : ''
		);

		$key          = md5( $sql );
		$last_changed = wp_cache_get_last_changed( 'terms' );
		$cache_key    = "terms_no_lang:{$key}:{$last_changed}";

		$term_ids = wp_cache_get( $cache_key, 'terms' );

		if ( ! is_array( $term_ids ) ) {
			$term_ids = $wpdb->get_col( $sql ); // PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
			wp_cache_set( $cache_key, $term_ids, 'terms' );
		}

		return $this->term->sanitize_int_ids_list( $term_ids );
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
	 * Returns the list of existing language terms.
	 * - Returns all terms, that are or not assigned to posts.
	 * - Terms are ordered by `term_group` and `term_id` (see `PLL_Model->filter_language_terms_orderby()`).
	 *
	 * @since 3.2.3
	 *
	 * @return array<WP_Term>
	 */
	protected function get_language_terms() {
		add_filter( 'get_terms_orderby', array( $this, 'filter_language_terms_orderby' ), 10, 3 );
		$post_languages = get_terms( array( 'taxonomy' => 'language', 'hide_empty' => false, 'orderby' => 'term_group' ) );
		remove_filter( 'get_terms_orderby', array( $this, 'filter_language_terms_orderby' ) );

		return empty( $post_languages ) || is_wp_error( $post_languages ) ? array() : $post_languages;
	}
}
