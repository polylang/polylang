<?php
/**
 * @package Polylang
 */

/**
 * Setup filters common to admin and frontend
 *
 * @since 1.4
 */
class PLL_Filters {
	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * Instance of a child class of PLL_Links_Model.
	 *
	 * @var PLL_Links_Model
	 */
	public $links_model;

	/**
	 * Current language.
	 *
	 * @var PLL_Language|null
	 */
	public $curlang;

	/**
	 * Constructor: setups filters
	 *
	 * @since 1.4
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		global $wp_version;

		$this->links_model = &$polylang->links_model;
		$this->model = &$polylang->model;
		$this->options = &$polylang->options;
		$this->curlang = &$polylang->curlang;

		// Deletes our cache for sticky posts when the list is updated.
		add_action( 'update_option_sticky_posts', array( $this, 'delete_sticky_posts_cache' ) );
		add_action( 'add_option_sticky_posts', array( $this, 'delete_sticky_posts_cache' ) );
		add_action( 'delete_option_sticky_posts', array( $this, 'delete_sticky_posts_cache' ) );

		// Filters the comments according to the current language
		add_action( 'parse_comment_query', array( $this, 'parse_comment_query' ) );
		add_filter( 'comments_clauses', array( $this, 'comments_clauses' ), 10, 2 );

		// Filters the get_pages function according to the current language
		if ( version_compare( $wp_version, '6.3-alpha', '<' ) ) {
			// Backward compatibility with WP < 6.3.
			add_filter( 'get_pages', array( $this, 'get_pages' ), 10, 2 );
		}
		add_filter( 'get_pages_query_args', array( $this, 'get_pages_query_args' ), 10, 2 );

		// Rewrites next and previous post links to filter them by language
		add_filter( 'get_previous_post_join', array( $this, 'posts_join' ), 10, 5 );
		add_filter( 'get_next_post_join', array( $this, 'posts_join' ), 10, 5 );
		add_filter( 'get_previous_post_where', array( $this, 'posts_where' ), 10, 5 );
		add_filter( 'get_next_post_where', array( $this, 'posts_where' ), 10, 5 );

		// Converts the locale to a valid W3C locale
		add_filter( 'language_attributes', array( $this, 'language_attributes' ) );

		// Translate the site title in emails sent to users
		add_filter( 'password_change_email', array( $this, 'translate_user_email' ) );
		add_filter( 'email_change_email', array( $this, 'translate_user_email' ) );

		// Translates the privacy policy page
		add_filter( 'option_wp_page_for_privacy_policy', array( $this, 'translate_page_for_privacy_policy' ), 20 ); // Since WP 4.9.6
		add_filter( 'map_meta_cap', array( $this, 'fix_privacy_policy_page_editing' ), 10, 4 );

		// Personal data exporter
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_personal_data_exporter' ), 0 ); // Since WP 4.9.6

		// Fix for `term_exists()`.
		add_filter( 'term_exists_default_query_args', array( $this, 'term_exists_default_query_args' ), 0, 3 ); // Since WP 6.0.0.
	}

	/**
	 * Deletes the cache for multilingual sticky posts.
	 *
	 * @since 2.8.4
	 *
	 * @return void
	 */
	public function delete_sticky_posts_cache() {
		wp_cache_delete( 'sticky_posts', 'options' );
	}

	/**
	 * Get the language to filter a comments query.
	 *
	 * @since 2.0
	 * @since 3.1 Always returns an array. Renamed from get_comments_queried_language().
	 *
	 * @param WP_Comment_Query $query WP_Comment_Query object.
	 * @return PLL_Language[] The languages to use in the filter.
	 */
	protected function get_comments_queried_languages( $query ) {
		// Don't filter comments if comment ids or post ids are specified.
		$plucked = wp_array_slice_assoc( $query->query_vars, array( 'comment__in', 'parent', 'post_id', 'post__in', 'post_parent' ) );
		$fields = array_filter( $plucked );
		if ( ! empty( $fields ) ) {
			return array();
		}

		// Don't filter comments if a non translated post type is specified.
		if ( ! empty( $query->query_vars['post_type'] ) && ! $this->model->is_translated_post_type( $query->query_vars['post_type'] ) ) {
			return array();
		}

		// If comments are queried with a 'lang' parameter, keeps only language codes.
		if ( isset( $query->query_vars['lang'] ) ) {
			$languages = is_string( $query->query_vars['lang'] ) ? explode( ',', $query->query_vars['lang'] ) : $query->query_vars['lang'];
			if ( is_array( $languages ) ) {
				$languages = array_map( array( $this->model, 'get_language' ), $languages );
				return array_filter( $languages );
			}
		}

		if ( ! empty( $this->curlang ) ) {
			return array( $this->curlang );
		}

		return array();
	}

	/**
	 * Adds a language dependent cache domain when querying comments.
	 * Useful as the 'lang' parameter is not included in cache key by WordPress.
	 * Needed since WP 4.6 as comments have been added to persistent cache. See #36906, #37419.
	 *
	 * @since 2.0
	 *
	 * @param WP_Comment_Query $query WP_Comment_Query object.
	 * @return void
	 */
	public function parse_comment_query( $query ) {
		$lang = $this->get_comments_queried_languages( $query );
		if ( ! empty( $lang ) ) {
			$lang = wp_list_pluck( $lang, 'slug' );
			$key = '_' . implode( ',', $lang );
			$query->query_vars['cache_domain'] = empty( $query->query_vars['cache_domain'] ) ? 'pll' . $key : $query->query_vars['cache_domain'] . $key;
		}
	}

	/**
	 * Filters the comments according to the current language.
	 * Used by the recent comments widget and admin language filter.
	 *
	 * @since 0.2
	 *
	 * @param string[]         $clauses SQL clauses.
	 * @param WP_Comment_Query $query   WP_Comment_Query object.
	 * @return string[] Modified $clauses.
	 */
	public function comments_clauses( $clauses, $query ) {
		global $wpdb;

		$lang = $this->get_comments_queried_languages( $query );

		if ( ! empty( $lang ) ) {
			$lang = wp_list_pluck( $lang, 'slug' );

			// If this clause is not already added by WP.
			if ( ! strpos( $clauses['join'], '.ID' ) ) {
				$clauses['join'] .= " JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->comments.comment_post_ID";
			}

			$clauses['join'] .= $this->model->post->join_clause();
			$clauses['where'] .= $this->model->post->where_clause( $lang );
		}
		return $clauses;
	}

	/**
	 * Filters get_pages() per language.
	 *
	 * @since 1.4
	 *
	 * @param WP_Post[] $pages An array of pages already queried.
	 * @param array     $args  Array of get_pages() arguments.
	 * @return WP_Post[] Modified list of pages.
	 */
	public function get_pages( $pages, $args ) {
		if ( isset( $args['lang'] ) && empty( $args['lang'] ) ) {
			return $pages;
		}

		$language = empty( $args['lang'] ) ? $this->curlang : $this->model->get_language( $args['lang'] );

		if ( empty( $language ) || empty( $pages ) || ! $this->model->is_translated_post_type( $args['post_type'] ) ) {
			return $pages;
		}

		static $once = false;

		if ( ! empty( $args['number'] ) && ! $once ) {
			// We are obliged to redo the get_pages() query if we want to get the right number.
			$once = true; // Avoid infinite loop.

			// Take care that 'exclude' argument accepts integer or strings too.
			$args['exclude'] = array_merge( wp_parse_id_list( $args['exclude'] ), $this->get_related_page_ids( $language, 'NOT IN', $args ) ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			$numbered_pages  = get_pages( $args );
			$pages           = ! $numbered_pages ? $pages : $numbered_pages;
		}

		$ids = wp_list_pluck( $pages, 'ID' );

		if ( ! $once ) {
			// Filters the queried list of pages by language.
			$ids = array_intersect( $ids, $this->get_related_page_ids( $language, 'IN', $args ) );

			foreach ( $pages as $key => $page ) {
				if ( ! in_array( $page->ID, $ids ) ) {
					unset( $pages[ $key ] );
				}
			}

			$pages = array_values( $pages ); // In case 3rd parties suppose the existence of $pages[0].
		}

		// Not done by WP but extremely useful for performance when manipulating taxonomies.
		update_object_term_cache( $ids, $args['post_type'] );

		$once = false; // In case get_pages() is called another time.
		return $pages;
	}

	/**
	 * Filters the WP_Query in get_pages() per language.
	 *
	 * @since 3.4.3
	 *
	 * @param array $query_args  Array of arguments passed to WP_Query.
	 * @param array $parsed_args Array of get_pages() arguments.
	 * @return array Array of arguments passed to WP_Query with the language.
	 */
	public function get_pages_query_args( $query_args, $parsed_args ) {
		if ( isset( $parsed_args['lang'] ) ) {
			$query_args['lang'] = $parsed_args['lang'];
		}

		return $query_args;
	}

	/**
	 * Get page ids related to a get_pages() in or not in a given language.
	 *
	 * @since 3.2
	 *
	 * @param PLL_Language $language The language to use in the relationship
	 * @param string       $relation 'IN' or 'NOT IN'.
	 * @param array        $args     Array of get_pages() arguments.
	 * @return int[]
	 */
	protected function get_related_page_ids( $language, $relation, $args ) {
		$r = array(
			'lang'        => '', // Ensure this query is not filtered.
			'numberposts' => -1,
			'nopaging'    => true,
			'post_type'   => $args['post_type'],
			'post_status' => $args['post_status'],
			'fields'      => 'ids',
			'tax_query'   => array(
				array(
					'taxonomy' => 'language',
					'field'    => 'term_taxonomy_id', // Since WP 3.5.
					'terms'    => $language->get_tax_prop( 'language', 'term_taxonomy_id' ),
					'operator' => $relation,
				),
			),
		);

		return get_posts( $r );
	}

	/**
	 * Modifies the sql request for get_adjacent_post to filter by the current language.
	 *
	 * @since 0.1
	 *
	 * @param string  $sql            The JOIN clause in the SQL.
	 * @param bool    $in_same_term   Whether post should be in a same taxonomy term.
	 * @param int[]   $excluded_terms Array of excluded term IDs.
	 * @param string  $taxonomy       Taxonomy. Used to identify the term used when `$in_same_term` is true.
	 * @param WP_Post $post           WP_Post object.
	 * @return string Modified JOIN clause.
	 */
	public function posts_join( $sql, $in_same_term, $excluded_terms, $taxonomy, $post ) {
		return $this->model->is_translated_post_type( $post->post_type ) && ! empty( $this->curlang ) ? $sql . $this->model->post->join_clause( 'p' ) : $sql;
	}

	/**
	 * Modifies the sql request for wp_get_archives and get_adjacent_post to filter by the current language.
	 *
	 * @since 0.1
	 *
	 * @param string  $sql            The WHERE clause in the SQL.
	 * @param bool    $in_same_term   Whether post should be in a same taxonomy term.
	 * @param int[]   $excluded_terms Array of excluded term IDs.
	 * @param string  $taxonomy       Taxonomy. Used to identify the term used when `$in_same_term` is true.
	 * @param WP_Post $post           WP_Post object.
	 * @return string Modified WHERE clause.
	 */
	public function posts_where( $sql, $in_same_term, $excluded_terms, $taxonomy, $post ) {
		return $this->model->is_translated_post_type( $post->post_type ) && ! empty( $this->curlang ) ? $sql . $this->model->post->where_clause( $this->curlang ) : $sql;
	}

	/**
	 * Converts WordPress locale to valid W3 locale in html language attributes
	 *
	 * @since 1.8
	 *
	 * @param string $output language attributes
	 * @return string
	 */
	public function language_attributes( $output ) {
		if ( $language = $this->model->get_language( is_admin() ? get_user_locale() : get_locale() ) ) {
			$output = str_replace( '"' . get_bloginfo( 'language' ) . '"', '"' . $language->get_locale( 'display' ) . '"', $output );
		}
		return $output;
	}

	/**
	 * Translates the site title in emails sent to the user (change email, reset password)
	 * It is necessary to filter the email because WP evaluates the site title before calling switch_to_locale()
	 *
	 * @since 2.1.3
	 *
	 * @param string[] $email Email contents.
	 * @return string[] Translated email contents.
	 */
	public function translate_user_email( $email ) {
		$blog_name = wp_specialchars_decode( pll__( get_option( 'blogname' ) ), ENT_QUOTES );
		$email['subject'] = sprintf( $email['subject'], $blog_name );
		$email['message'] = str_replace( '###SITENAME###', $blog_name, $email['message'] );
		return $email;
	}

	/**
	 * Translates the privacy policy page, on both frontend and admin
	 *
	 * @since 2.3.6
	 *
	 * @param int $id Privacy policy page id
	 * @return int
	 */
	public function translate_page_for_privacy_policy( $id ) {
		return empty( $this->curlang ) ? $id : $this->model->post->get( $id, $this->curlang );
	}

	/**
	 * Prevents edit and delete links for the translations of the privacy policy page for non admin
	 *
	 * @since 2.3.7
	 *
	 * @param array  $caps    The user's actual capabilities.
	 * @param string $cap     Capability name.
	 * @param int    $user_id The user ID.
	 * @param array  $args    Adds the context to the cap. The category id.
	 * @return array
	 */
	public function fix_privacy_policy_page_editing( $caps, $cap, $user_id, $args ) {
		if ( in_array( $cap, array( 'edit_page', 'edit_post', 'delete_page', 'delete_post' ) ) ) {
			$privacy_page = get_option( 'wp_page_for_privacy_policy' );
			if ( $privacy_page && array_intersect( $args, $this->model->post->get_translations( $privacy_page ) ) ) {
				$caps = array_merge( $caps, map_meta_cap( 'manage_privacy_options', $user_id ) );
			}
		}

		return $caps;
	}

	/**
	 * Register our personal data exporter
	 *
	 * @since 2.3.6
	 *
	 * @param array $exporters Personal data exporters
	 * @return array
	 */
	public function register_personal_data_exporter( $exporters ) {
		$exporters[] = array(
			'exporter_friendly_name' => __( 'Translated user descriptions', 'polylang' ),
			'callback'               => array( $this, 'user_data_exporter' ),
		);
		return $exporters;
	}

	/**
	 * Export translated user description as WP exports only the description in the default language
	 *
	 * @since 2.3.6
	 *
	 * @param string $email_address User email address
	 * @return array Personal data
	 */
	public function user_data_exporter( $email_address ) {
		$email_address       = trim( $email_address );
		$data_to_export      = array();
		$user_data_to_export = array();

		if ( $user = get_user_by( 'email', $email_address ) ) {
			foreach ( $this->model->get_languages_list() as $lang ) {
				if ( ! $lang->is_default && $value = get_user_meta( $user->ID, 'description_' . $lang->slug, true ) ) {
					$user_data_to_export[] = array(
						/* translators: %s is a language native name */
						'name'  => sprintf( __( 'User description - %s', 'polylang' ), $lang->name ),
						'value' => $value,
					);
				}
			}

			if ( ! empty( $user_data_to_export ) ) {
				$data_to_export[] = array(
					'group_id'    => 'user',
					'group_label' => __( 'User', 'polylang' ),
					'item_id'     => "user-{$user->ID}",
					'data'        => $user_data_to_export,
				);
			}
		}

		return array(
			'data' => $data_to_export,
			'done' => true,
		);
	}

	/**
	 * Filters default query arguments for checking if a term exists.
	 * In `term_exists()`, WP 6.0 uses `get_terms()`, which is filtered by language by Polylang.
	 * This filter prevents `term_exists()` to be filtered by language.
	 *
	 * @since 3.2
	 *
	 * @param  array      $defaults An array of arguments passed to get_terms().
	 * @param  int|string $term     The term to check. Accepts term ID, slug, or name.
	 * @param  string     $taxonomy The taxonomy name to use. An empty string indicates the search is against all taxonomies.
	 * @return array
	 */
	public function term_exists_default_query_args( $defaults, $term, $taxonomy ) {
		if ( ! empty( $taxonomy ) && ! $this->model->is_translated_taxonomy( $taxonomy ) ) {
			return $defaults;
		}

		if ( ! is_array( $defaults ) ) {
			$defaults = array();
		}

		if ( ! isset( $defaults['lang'] ) ) {
			$defaults['lang'] = '';
		}

		return $defaults;
	}
}
