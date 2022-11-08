<?php
/**
 * @package Polylang
 */

/**
 * Setups the posts languages and translations model.
 *
 * @since 1.8
 */
class PLL_Translated_Post extends PLL_Translated_Object {

	/**
	 * Taxonomy name for the languages.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $tax_language = 'language';

	/**
	 * Name of the `PLL_Language` property that stores the term_taxonomy ID.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $tax_tt_prop_name = 'term_taxonomy_id';

	/**
	 * Taxonomy name for the translation groups.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $tax_translations = 'post_translations';

	/**
	 * Object type to use when checking capabilities.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $type = 'post';

	/**
	 * Name of the DB column containing the post's ID.
	 *
	 * @var string
	 * @see PLL_Object_With_Language::join_clause()
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $db_id_column = 'ID';

	/**
	 * Constructor.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Model $model Instance of `PLL_Model`.
	 */
	public function __construct( PLL_Model &$model ) {
		$this->db_default_alias = $GLOBALS['wpdb']->posts;

		parent::__construct( $model );

		$this->init();
	}

	/**
	 * Adds hooks.
	 *
	 * @since 3.3
	 *
	 * @return self
	 */
	public function init() {
		// Registers completely the language taxonomy.
		add_action( 'setup_theme', array( $this, 'register_taxonomy' ), 1 );

		// Setups post types to translate.
		add_action( 'registered_post_type', array( $this, 'registered_post_type' ) );

		// Forces updating posts cache.
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		return $this;
	}

	/**
	 * Stores the post's language in the database.
	 *
	 * @since 0.6
	 * @since 3.3 Renamed the parameter $post_id into $id.
	 *
	 * @param int                     $id   Post ID.
	 * @param int|string|PLL_Language $lang Language (term_id or slug or object).
	 * @return bool True on success (or if the given language is already assigned to the object). False otherwise.
	 */
	public function set_language( $id, $lang ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return false;
		}

		$old_lang = $this->get_language( $id );
		$old_lang = $old_lang ? $old_lang->slug : '';

		$lang = $this->model->get_language( $lang );
		$lang = $lang ? $lang->slug : '';

		if ( $old_lang === $lang ) {
			return true;
		}

		return is_array( wp_set_post_terms( $id, $lang, $this->tax_language ) );
	}

	/**
	 * Deletes a translation of a post.
	 *
	 * @since 0.5
	 *
	 * @param int $id Post ID.
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
	 * @since 3.3 Renamed the parameter $post_id into $id.
	 *
	 * @param int    $id Post ID
	 * @param string $context Optional, 'edit' or 'view', defaults to 'view'.
	 * @return bool
	 */
	public function current_user_can_read( $id, $context = 'view' ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return false;
		}

		$post = get_post( $id );

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
