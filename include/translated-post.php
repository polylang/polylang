<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sets the posts languages and translations model up.
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
	 * Identifier that must be unique for each type of content.
	 * Also used when checking capabilities.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $type = 'post';

	/**
	 * Taxonomy name for the translation groups.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $tax_translations = 'post_translations';

	/**
	 * Constructor.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Model $model Instance of `PLL_Model`.
	 */
	public function __construct( PLL_Model &$model ) {
		$this->db = array(
			'table'         => $GLOBALS['wpdb']->posts,
			'id_column'     => 'ID',
			'type_column'   => 'post_type',
			'default_alias' => $GLOBALS['wpdb']->posts,
		);

		parent::__construct( $model );

		// Keep hooks in constructor for backward compatibility.
		$this->init();
	}

	/**
	 * Adds hooks.
	 *
	 * @since 3.4
	 *
	 * @return static
	 */
	public function init() {
		// Registers completely the language taxonomy.
		add_action( 'setup_theme', array( $this, 'register_taxonomy' ), 1 );

		// Setups post types to translate.
		add_action( 'registered_post_type', array( $this, 'registered_post_type' ) );

		// Forces updating posts cache.
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		return parent::init();
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
	 * Returns object types (post types) that need to be translated.
	 * The post types list is cached for better better performance.
	 * The method waits for 'after_setup_theme' to apply the cache to allow themes adding the filter in functions.php.
	 *
	 * @since 3.4
	 *
	 * @param bool $filter True if we should return only valid registered object types.
	 * @return string[] Object type names for which Polylang manages languages.
	 *
	 * @phpstan-return array<non-empty-string, non-empty-string>
	 */
	public function get_translated_object_types( $filter = true ) {
		$post_types = $this->model->cache->get( 'post_types' );

		if ( false === $post_types ) {
			$post_types = array( 'post' => 'post', 'page' => 'page', 'wp_block' => 'wp_block' );

			if ( ! empty( $this->model->options['post_types'] ) && is_array( $this->model->options['post_types'] ) ) {
				$post_types = array_merge( $post_types, array_combine( $this->model->options['post_types'], $this->model->options['post_types'] ) );
			}

			if ( empty( $this->model->options['media_support'] ) ) {
				// In case the post type attachment is stored in the option.
				unset( $post_types['attachment'] );
			} else {
				$post_types['attachment'] = 'attachment';
			}

			/**
			 * Filters the list of post types available for translation.
			 * The default are post types which have the parameter ‘public’ set to true.
			 * The filter must be added soon in the WordPress loading process:
			 * in a function hooked to ‘plugins_loaded’ or directly in functions.php for themes.
			 *
			 * @since 0.8
			 *
			 * @param string[] $post_types  List of post type names (as array keys and values).
			 * @param bool     $is_settings True when displaying the list of custom post types in Polylang settings.
			 */
			$post_types = apply_filters( 'pll_get_post_types', $post_types, false );

			if ( did_action( 'after_setup_theme' ) ) {
				$this->model->cache->set( 'post_types', $post_types );
			}
		}

		/** @var array<non-empty-string, non-empty-string> $post_types */
		return $filter ? array_intersect( $post_types, get_post_types() ) : $post_types;
	}

	/**
	 * Returns true if Polylang manages languages for this object type.
	 *
	 * @since 3.4
	 *
	 * @param string|string[] $object_type Object type (post type) name or array of object type names.
	 * @return bool
	 *
	 * @phpstan-param non-empty-string|non-empty-string[] $object_type
	 */
	public function is_translated_object_type( $object_type ) {
		$post_types = $this->get_translated_object_types( false );
		return ( is_array( $object_type ) && array_intersect( $object_type, $post_types ) || in_array( $object_type, $post_types ) || 'any' === $object_type && ! empty( $post_types ) );
	}

	/**
	 * Returns the IDs of the objects without language.
	 *
	 * @since 3.4
	 *
	 * @param string[] $object_types An array of object types (post types).
	 * @param int      $limit        Max number of objects to return. `-1` to return all of them.
	 * @return int[] Array of object IDs.
	 *
	 * @phpstan-param non-empty-string[] $object_types
	 * @phpstan-param -1|positive-int $limit
	 * @phpstan-return list<positive-int>
	 */
	public function get_objects_with_no_lang( array $object_types, $limit ) {
		if ( empty( $object_types ) ) {
			return array();
		}

		$languages = $this->model->get_languages_list();

		foreach ( $languages as $i => $language ) {
			$languages[ $i ] = $language->get_tax_prop( $this->get_tax_language(), 'term_id' );
		}

		$languages = array_filter( $languages );

		if ( empty( $languages ) ) {
			return array();
		}

		/** @var list<positive-int> */
		return get_posts(
			array(
				'numberposts' => $limit,
				'nopaging'    => $limit <= 0,
				'post_type'   => $object_types,
				'post_status' => 'any',
				'fields'      => 'ids',
				'tax_query'   => array(
					array(
						'taxonomy' => 'language',
						'terms'    => $languages,
						'operator' => 'NOT IN',
					),
				),
			)
		);
	}

	/**
	 * Registers the language taxonomy.
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
				'show_ui'            => false, // Hide the taxonomy on admin side, needed for WP 4.4.x.
				'show_in_nav_menus'  => false, // No metabox for nav menus, needed for WP 4.4.x.
				'publicly_queryable' => true, // Since WP 4.5.
				'query_var'          => 'lang',
				'rewrite'            => $this->model->options['force_lang'] < 2, // No rewrite for domains and sub-domains.
				'_pll'               => true, // Polylang taxonomy.
			)
		);
	}

	/**
	 * Checks if registered post type must be translated.
	 *
	 * @since 1.2
	 *
	 * @param string $post_type Post type name.
	 * @return void
	 *
	 * @phpstan-param non-empty-string $post_type
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
	 * Checks if the current user can read the post.
	 *
	 * @since 1.5
	 * @since 3.4 Renamed the parameter $post_id into $id.
	 *
	 * @param int    $id Post ID
	 * @param string $context Optional, 'edit' or 'view'. Defaults to 'view'.
	 * @return bool
	 *
	 * @phpstan-param non-empty-string $context
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
				return is_user_logged_in() && ( current_user_can( 'edit_posts' ) || (int) $user->ID === (int) $post->post_author );
			}
		}

		return false;
	}

	/**
	 * Returns a list of posts in a language ($lang) not translated in another language ($untranslated_in).
	 *
	 * @since 2.6
	 *
	 * @param string       $type            Post type.
	 * @param PLL_Language $untranslated_in The language the posts must not be translated in.
	 * @param PLL_Language $lang            Language of the searched posts.
	 * @param string       $search          Limit the results to the posts matching this string.
	 * @return WP_Post[] Array of posts.
	 */
	public function get_untranslated( $type, PLL_Language $untranslated_in, PLL_Language $lang, $search = '' ) {
		$return = array();

		// Don't order by title: see https://wordpress.org/support/topic/find-translated-post-when-10-is-not-enough.
		$args = array(
			's'                => $search,
			'suppress_filters' => 0, // To make the post_fields filter work.
			'lang'             => 0, // Avoid admin language filter.
			'numberposts'      => 20, // Limit to 20 posts.
			'post_status'      => 'any',
			'post_type'        => $type,
			'tax_query'        => array(
				array(
					'taxonomy' => $this->tax_language,
					'field'    => 'term_taxonomy_id', // WP 3.5+.
					'terms'    => $lang->get_tax_prop( $this->tax_language, 'term_taxonomy_id' ),
				),
			),
		);

		/**
		 * Filter the query args when auto suggesting untranslated posts in the Languages metabox.
		 * This should help plugins to fix some edge cases.
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
