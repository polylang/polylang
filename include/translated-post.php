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
	 * Constructor.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Model $model PLL_Model instance.
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
	 * Store the post language in the database.
	 *
	 * @since 0.6
	 *
	 * @param int                     $post_id Post id.
	 * @param int|string|PLL_Language $lang    Language (term_id or slug or object).
	 * @return void
	 */
	public function set_language( $post_id, $lang ) {
		$post_id = $this->sanitize_int_id( $post_id );

		if ( empty( $post_id ) ) {
			return;
		}

		$old_lang = $this->get_language( $post_id );
		$old_lang = $old_lang ? $old_lang->slug : '';

		$lang = $this->model->get_language( $lang );
		$lang = $lang ? $lang->slug : '';

		if ( $old_lang !== $lang ) {
			wp_set_post_terms( $post_id, $lang, $this->tax_language );
		}
	}

	/**
	 * Returns the language of a post
	 *
	 * @since 0.1
	 *
	 * @param int $post_id post id
	 * @return PLL_Language|false PLL_Language object, false if no language is associated to that post
	 */
	public function get_language( $post_id ) {
		$post_id = $this->sanitize_int_id( $post_id );

		if ( empty( $post_id ) ) {
			return false;
		}

		$lang = $this->get_object_term( $post_id, $this->tax_language );
		return ! empty( $lang ) ? $this->model->get_language( $lang ) : false;
	}

	/**
	 * Deletes a translation.
	 *
	 * @since 0.5
	 *
	 * @param int $id Post id.
	 * @return void
	 */
	public function delete_translation( $id ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return;
		}

		parent::delete_translation( $id );
		wp_set_object_terms( $id, array(), $this->tax_translations );
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
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		register_taxonomy(
			$this->tax_language,
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
	 * @return void
	 */
	public function registered_post_type( $post_type ) {
		if ( $this->model->is_translated_post_type( $post_type ) ) {
			register_taxonomy_for_object_type( $this->tax_language, $post_type );
			register_taxonomy_for_object_type( $this->tax_translations, $post_type );
		}
	}

	/**
	 * Forces calling 'update_object_term_cache' when querying posts or pages.
	 * This is especially useful for nav menus with a lot of pages as, without doing this,
	 * we would have one query per page in the menu to get the page language for the permalink.
	 *
	 * @since 1.8
	 *
	 * @param WP_Query $query Reference to the query object.
	 * @return void
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
		$post_id = $this->sanitize_int_id( $post_id );

		if ( empty( $post_id ) ) {
			return false;
		}

		$post = get_post( $post_id );

		if ( empty( $post ) ) {
			return false;
		}

		if ( 'inherit' === $post->post_status && $post->post_parent ) {
			$post = get_post( $post->post_parent );

			if ( empty( $post ) ) {
				return false;
			}
		}

		if ( 'inherit' === $post->post_status || in_array( $post->post_status, get_post_stati( array( 'public' => true ) ) ) ) {
			return true;
		}

		// Follow WP practices, which shows links to private posts ( when readable ), but not for draft posts ( ex: get_adjacent_post_link() )
		if ( in_array( $post->post_status, get_post_stati( array( 'private' => true ) ) ) ) {
			if ( ! is_user_logged_in() ) {
				return false;
			}

			$user = wp_get_current_user();

			if ( (int) $user->ID === (int) $post->post_author ) {
				return true;
			}

			$post_type_object = get_post_type_object( $post->post_type );

			return ! empty( $post_type_object ) && current_user_can( $post_type_object->cap->read_private_posts );
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
	 * not translated in another language ( $untranslated_in ).
	 *
	 * @since 2.6
	 *
	 * @param string       $type            Post type.
	 * @param PLL_Language $untranslated_in The language the posts must not be translated in.
	 * @param PLL_Language $lang            Language of the searched posts.
	 * @param string       $search          Limit the results to the posts matching this string.
	 * @return WP_Post[] Array of posts.
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
					'taxonomy' => $this->tax_language,
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
			if ( $post instanceof WP_Post && ! $this->get_translation( $post->ID, $untranslated_in ) && $this->current_user_can_read( $post->ID, 'edit' ) ) {
				$return[] = $post;
			}
		}

		return $return;
	}

}
