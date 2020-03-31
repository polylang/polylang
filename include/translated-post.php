<?php
/**
 * @package Polylang
 */

/**
 * Setups the posts languages and translations model
 *
 * @since 1.8
 */
class PLL_Translated_Post extends PLL_Translated_Object {

	/**
	 * Constructor
	 *
	 * @since 1.8
	 *
	 * @param object $model
	 */
	public function __construct( &$model ) {
		// init properties
		$this->object_type = null; // For taxonomies
		$this->type = 'post'; // For capabilities
		$this->tax_language = 'language';
		$this->tax_translations = 'post_translations';
		$this->tax_tt = 'term_taxonomy_id';

		parent::__construct( $model );

		// registers completely the language taxonomy
		add_action( 'setup_theme', array( $this, 'register_taxonomy' ), 1 );

		// setups post types to translate
		add_action( 'registered_post_type', array( $this, 'registered_post_type' ) );

		// forces updating posts cache
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
	}

	/**
	 * Store the post language in the database
	 *
	 * @since 0.6
	 *
	 * @param int               $post_id post id
	 * @param int|string|object $lang    language ( term_id or slug or object )
	 */
	public function set_language( $post_id, $lang ) {
		$old_lang = $this->get_language( $post_id );
		$old_lang = $old_lang ? $old_lang->slug : '';
		$lang = $lang ? $this->model->get_language( $lang )->slug : '';

		if ( $old_lang !== $lang ) {
			wp_set_post_terms( (int) $post_id, $lang, 'language' );
		}
	}

	/**
	 * Returns the language of a post
	 *
	 * @since 0.1
	 *
	 * @param int $post_id post id
	 * @return bool|object PLL_Language object, false if no language is associated to that post
	 */
	public function get_language( $post_id ) {
		$lang = $this->get_object_term( $post_id, 'language' );
		return ( $lang ) ? $this->model->get_language( $lang ) : false;
	}

	/**
	 * Deletes a translation
	 *
	 * @since 0.5
	 *
	 * @param int $id post id
	 */
	public function delete_translation( $id ) {
		parent::delete_translation( $id );
		wp_set_object_terms( $id, null, $this->tax_translations );
	}

	/**
	 * A join clause to add to sql queries when filtering by language is needed directly in query
	 *
	 * @since 1.2
	 *
	 * @param string $alias Alias for $wpdb->posts table
	 * @return string join clause
	 */
	public function join_clause( $alias = '' ) {
		global $wpdb;
		if ( empty( $alias ) ) {
			$alias = $wpdb->posts;
		}
		return " INNER JOIN $wpdb->term_relationships AS pll_tr ON pll_tr.object_id = $alias.ID";
	}

	/**
	 * Register the language taxonomy
	 *
	 * @since 1.2
	 */
	public function register_taxonomy() {
		register_taxonomy(
			'language',
			$this->model->get_translated_post_types(),
			array(
				'labels' => array(
					'name'          => __( 'Languages', 'polylang' ),
					'singular_name' => __( 'Language', 'polylang' ),
					'all_items'     => __( 'All languages', 'polylang' ),
				),
				'public'             => false,
				'show_ui'            => false, // hide the taxonomy on admin side, needed for WP 4.4.x
				'show_in_nav_menus'  => false, // no metabox for nav menus, needed for WP 4.4.x
				'publicly_queryable' => true, // since WP 4.5
				'query_var'          => 'lang',
				'rewrite'            => $this->model->options['force_lang'] < 2, // no rewrite for domains and sub-domains
				'_pll'               => true, // polylang taxonomy
			)
		);
	}

	/**
	 * Check if registered post type must be translated
	 *
	 * @since 1.2
	 *
	 * @param string $post_type post type name
	 */
	public function registered_post_type( $post_type ) {
		if ( $this->model->is_translated_post_type( $post_type ) ) {
			register_taxonomy_for_object_type( 'language', $post_type );
			register_taxonomy_for_object_type( 'post_translations', $post_type );
		}
	}

	/**
	 * Forces calling 'update_object_term_cache' when querying posts or pages
	 * this is especially useful for nav menus with a lot of pages
	 * without doing this, we would have one query per page in the menu to get the page language for the permalink
	 *
	 * @since 1.8
	 *
	 * @param object $query reference to the query object
	 */
	public function pre_get_posts( $query ) {
		if ( ! empty( $query->query['post_type'] ) && $this->model->is_translated_post_type( $query->query['post_type'] ) ) {
			$query->query_vars['update_post_term_cache'] = true;
		}
	}

	/**
	 * Checks if the current user can read the post
	 *
	 * @since 1.5
	 *
	 * @param int    $post_id Post ID
	 * @param string $context Optional, 'edit' or 'view', defaults to 'view'.
	 * @return bool
	 */
	public function current_user_can_read( $post_id, $context = 'view' ) {
		$post = get_post( $post_id );

		if ( empty( $post ) ) {
			return false;
		}

		if ( 'inherit' === $post->post_status && $post->post_parent ) {
			$post = get_post( $post->post_parent );
		}

		if ( 'inherit' === $post->post_status || in_array( $post->post_status, get_post_stati( array( 'public' => true ) ) ) ) {
			return true;
		}

		// Follow WP practices, which shows links to private posts ( when readable ), but not for draft posts ( ex: get_adjacent_post_link() )
		if ( in_array( $post->post_status, get_post_stati( array( 'private' => true ) ) ) ) {
			$post_type_object = get_post_type_object( $post->post_type );
			$user = wp_get_current_user();
			return is_user_logged_in() && ( current_user_can( $post_type_object->cap->read_private_posts ) || $user->ID == $post->post_author ); // Comparison must not be strict!
		}

		// In edit context, show draft and future posts.
		if ( 'edit' === $context ) {
			$states = get_post_stati(
				array(
					'protected'              => true,
					'show_in_admin_all_list' => true,
				)
			);

			if ( in_array( $post->post_status, $states ) ) {
				$user = wp_get_current_user();
				return is_user_logged_in() && ( current_user_can( 'edit_posts' ) || $user->ID == $post->post_author ); // Comparison must not be strict!
			}
		}

		return false;
	}

	/**
	 * Returns a list of posts in a language ( $lang )
	 * not translated in another language ( $untranslated_in )
	 *
	 * @since 2.6
	 *
	 * @param string $type            Post type
	 * @param string $untranslated_in The posts must not be translated in this language
	 * @param string $lang            Language of the search posts
	 * @param string $search          Limit results to posts matching this string
	 * @return array Array of posts
	 */
	public function get_untranslated( $type, $untranslated_in, $lang, $search = '' ) {
		$return = array();

		// Don't order by title: see https://wordpress.org/support/topic/find-translated-post-when-10-is-not-enough
		$args = array(
			's'                => $search,
			'suppress_filters' => 0, // To make the post_fields filter work
			'lang'             => 0, // Avoid admin language filter
			'numberposts'      => 20, // Limit to 20 posts
			'post_status'      => 'any',
			'post_type'        => $type,
			'tax_query'        => array(
				array(
					'taxonomy' => 'language',
					'field'    => 'term_taxonomy_id', // WP 3.5+
					'terms'    => $lang->term_taxonomy_id,
				),
			),
		);

		/**
		 * Filter the query args when auto suggesting untranslated posts in the Languages metabox
		 * This should help plugins to fix some edge cases
		 *
		 * @see https://wordpress.org/support/topic/find-translated-post-when-10-is-not-enough
		 *
		 * @since 1.7
		 *
		 * @param array $args WP_Query arguments
		 */
		$args  = apply_filters( 'pll_ajax_posts_not_translated_args', $args );
		$posts = get_posts( $args );

		foreach ( $posts as $post ) {
			if ( ! $this->get_translation( $post->ID, $untranslated_in ) && $this->current_user_can_read( $post->ID, 'edit' ) ) {
				$return[] = $post;
			}
		}

		return $return;
	}
}
