<?php
/**
 * @package Polylang
 */

use WP_Syntex\Polylang\Options\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Sets the posts languages and translations model up.
 *
 * @since 1.8
 *
 * @phpstan-import-type DBInfoWithType from PLL_Translatable_Object_With_Types_Interface
 */
class PLL_Translated_Post extends PLL_Translated_Object implements PLL_Translatable_Object_With_Types_Interface {
	use PLL_Translatable_Object_With_Types_Trait;

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
	 * Identifier for each type of content to used for cache type.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $cache_type = 'posts';

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
	public function __construct( PLL_Model $model ) {
		parent::__construct( $model );

		// Keep hooks in constructor for backward compatibility.
		$this->init();
	}

	/**
	 * Registers the language taxonomy.
	 *
	 * @since 1.2
	 * @since 3.7 Protected and renamed from `register_taxonomy()`.
	 *
	 * @return void
	 */
	protected function register_language_taxonomy(): void {
		register_taxonomy(
			$this->tax_language,
			(array) $this->get_translated_object_types(),
			array(
				'public'             => false,
				'show_ui'            => false, // Hide the taxonomy on admin side, needed for WP 4.4.x.
				'show_in_nav_menus'  => false, // No metabox for nav menus, needed for WP 4.4.x.
				'publicly_queryable' => true, // Since WP 4.5.
				'query_var'          => 'lang', // See `add_language_taxonomy_query_var()`.
				'rewrite'            => false, // Rewrite rules are added through filters when needed.
				'_pll'               => true, // Polylang taxonomy.
			)
		);

		add_action( 'setup_theme', array( $this, 'add_language_taxonomy_query_var' ) );
	}

	/**
	 * Adds the language query var once the global `$wp` is available.
	 *
	 * @since 3.7
	 * @see WP_Taxonomy::add_rewrite_rules()
	 *
	 * @return void
	 */
	public function add_language_taxonomy_query_var(): void {
		$GLOBALS['wp']->add_query_var( 'lang' );
	}

	/**
	 * Adds hooks.
	 *
	 * @since 3.4
	 *
	 * @return static
	 */
	public function init() {
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
	 * The post types list is cached for better performance.
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
		$post_types = $this->cache->get( 'post_types' );

		if ( false === $post_types ) {
			$post_types = array( 'post' => 'post', 'page' => 'page', 'wp_block' => 'wp_block' );

			if ( ! empty( $this->options['post_types'] ) ) {
				$post_types = array_merge( $post_types, array_combine( $this->options['post_types'], $this->options['post_types'] ) );
			}

			if ( empty( $this->options['media_support'] ) ) {
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
			$post_types = (array) apply_filters( 'pll_get_post_types', $post_types, false );

			if ( did_action( 'after_setup_theme' ) && ! doing_action( 'switch_blog' ) ) {
				$this->cache->set( 'post_types', $post_types );
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
	 */
	public function is_translated_object_type( $object_type ) {
		$post_types = $this->get_translated_object_types( false );
		return ( is_array( $object_type ) && array_intersect( $object_type, $post_types ) ) || in_array( $object_type, $post_types ) || ( 'any' === $object_type && ! empty( $post_types ) );
	}

	/**
	 * Checks if registered post type must be translated.
	 *
	 * @since 1.2
	 *
	 * @param string $post_type Post type name.
	 * @return void
	 */
	public function registered_post_type( $post_type ) {
		if ( $this->is_translated_object_type( $post_type ) ) {
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
		if ( ! empty( $query->query['post_type'] ) && $this->is_translated_object_type( $query->query['post_type'] ) ) {
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
	 * Creates a media translation
	 *
	 * @since 1.8
	 * @since 3.7 Moved from PLL_CRUD_Posts.
	 *
	 * @param int                 $post_id Original attachment id.
	 * @param string|PLL_Language $lang    New translation language.
	 * @return int Attachment id of the translated media.
	 */
	public function create_media_translation( $post_id, $lang ) {
		if ( empty( $post_id ) ) {
			return 0;
		}

		$post = get_post( $post_id, ARRAY_A );

		if ( empty( $post ) ) {
			return 0;
		}

		$lang = $this->languages->get( $lang ); // Make sure we get a valid language slug.

		if ( empty( $lang ) ) {
			return 0;
		}

		// Create a new attachment ( translate attachment parent if exists ).
		add_filter( 'pll_enable_duplicate_media', '__return_false', 99 ); // Avoid a conflict with automatic duplicate at upload.
		unset( $post['ID'] ); // Will force the creation.
		if ( ! empty( $post['post_parent'] ) ) {
			$post['post_parent'] = (int) $this->get_translation( $post['post_parent'], $lang->slug );
		}
		$post['tax_input'] = array( 'language' => array( $lang->slug ) ); // Assigns the language.

		// Loads the strings translations with the attachment's target language.
		PLL()->load_strings_translations( $lang->slug );

		$tr_id = wp_insert_attachment( wp_slash( $post ) );
		remove_filter( 'pll_enable_duplicate_media', '__return_false', 99 ); // Restore automatic duplicate at upload.

		// Copy metadata.
		$data = wp_get_attachment_metadata( $post_id, true ); // Unfiltered.
		if ( is_array( $data ) ) {
			wp_update_attachment_metadata( $tr_id, wp_slash( $data ) ); // Directly uses update_post_meta, so expects slashed.
		}

		// Copy attached file.
		if ( $file = get_attached_file( $post_id, true ) ) { // Unfiltered.
			update_attached_file( $tr_id, wp_slash( $file ) ); // Directly uses update_post_meta, so expects slashed.
		}

		// Copy alternative text. Direct use of the meta as there is no filtered wrapper to manipulate it.
		if ( $text = get_post_meta( $post_id, '_wp_attachment_image_alt', true ) ) {
			add_post_meta( $tr_id, '_wp_attachment_image_alt', wp_slash( $text ) );
		}

		$this->set_language( $tr_id, $lang );

		$translations = $this->get_translations( $post_id );
		$translations[ $lang->slug ] = $tr_id;
		$this->save_translations( $tr_id, $translations );

		/**
		 * Fires after a media translation is created
		 *
		 * @since 1.6.4
		 *
		 * @param int    $post_id Post id of the source media.
		 * @param int    $tr_id   Post id of the new media translation.
		 * @param string $slug    Language code of the new translation.
		 */
		do_action( 'pll_translate_media', $post_id, $tr_id, $lang->slug );

		// Restores the strings translations with the current language.
		if ( PLL()->curlang instanceof PLL_Language ) {
			PLL()->load_strings_translations( PLL()->curlang->slug );
		}

		return $tr_id;
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
		global $wpdb;

		$args = array( 'numberposts' => 20 ); // Limit to 20 posts by default.
		/**
		 * Filters the query args when auto suggesting untranslated posts in the Languages metabox.
		 *
		 * @since 1.7
		 * @since 3.4 Handled arguments restricted to `numberposts` to limit queried posts.
		 *            No `WP_Query` is made anymore, a custom one is used instead.
		 *
		 * @param array $args WP_Query arguments
		 */
		$args = apply_filters( 'pll_ajax_posts_not_translated_args', $args );

		$limit             = $args['numberposts'];
		$search_like       = '%' . $wpdb->esc_like( $search ) . '%';
		$untranslated_like = '%' . $wpdb->esc_like( $untranslated_in->slug ) . '%';
		$posts             = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->posts}
				INNER JOIN {$wpdb->term_relationships} AS tr1 ON ({$wpdb->posts}.ID = tr1.object_id)
				AND tr1.term_taxonomy_id IN (%d)
				AND (({$wpdb->posts}.post_title LIKE %s)
					OR ({$wpdb->posts}.post_excerpt LIKE %s)
					OR ({$wpdb->posts}.post_content LIKE %s)
				)
				AND {$wpdb->posts}.post_type = %s
				AND {$wpdb->posts}.post_status NOT IN ('trash', 'auto-draft')
				WHERE {$wpdb->posts}.ID NOT IN (
					SELECT {$wpdb->posts}.ID FROM {$wpdb->posts}
						LEFT JOIN {$wpdb->term_relationships} AS tr2 ON ({$wpdb->posts}.ID = tr2.object_id)
						INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tt.term_taxonomy_id = tr2.term_taxonomy_id)
							AND (tt.taxonomy = 'post_translations')
							AND tt.description LIKE %s
				)
				LIMIT 0, %d",
				$lang->get_tax_prop( $this->tax_language, 'term_taxonomy_id' ),
				$search_like,
				$search_like,
				$search_like,
				$type,
				$untranslated_like,
				$limit
			)
		);

		foreach ( $posts as $i => $post ) {
			if ( ! $this->current_user_can_read( $post->ID, 'edit' ) ) {
				unset( $posts[ $i ] );
				continue;
			}

			$posts[ $i ] = new WP_Post( $post );
		}

		return $posts;
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
		return __( 'Language taxonomy properties for post types.', 'polylang' );
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
	 *     @type string $type_column   Name of the column containing the object's type.
	 *     @type string $default_alias Default alias corresponding to the object's table.
	 * }
	 * @phpstan-return DBInfoWithType
	 */
	protected function get_db_infos() {
		return array(
			'table'         => $GLOBALS['wpdb']->posts,
			'id_column'     => 'ID',
			'type_column'   => 'post_type',
			'default_alias' => $GLOBALS['wpdb']->posts,
		);
	}

	/**
	 * Wraps `wp_insert_post` with language feature.
	 *
	 * @since 3.7
	 *
	 * @param array        $postarr {
	 *     Optional. An array of elements that make up a post to insert.
	 *     @See wp_insert_post() for accepted arguments.
	 *
	 *     @type string[] $translations The translation group to assign to the post with language slug as keys and post ID as values.
	 * }
	 * @param PLL_Language $language The post language object.
	 * @return int|WP_Error The post ID on success. The value `WP_Error` on failure.
	 */
	public function insert( array $postarr, PLL_Language $language ) {
		$post_id = wp_insert_post( $postarr, true );
		if ( is_wp_error( $post_id ) ) {
			// Something went wrong!
			return $post_id;
		}

		$this->set_language( $post_id, $language );

		if ( ! empty( $postarr['translations'] ) ) {
			$this->save_translations( $post_id, $postarr['translations'] );
		}

		return $post_id;
	}

	/**
	 * Wraps `wp_update_post` with language feature.
	 *
	 * @since 3.7
	 *
	 * @param array $postarr {
	 *     Optional. An array of elements that make up a post to update.
	 *     @See wp_insert_post() for accepted arguments.
	 *
	 *     @type PLL_Language|string $lang         The post language object or slug.
	 *     @type string[]            $translations The translation group to assign to the post with language slug as keys and post ID as values.
	 * }
	 * @return int|WP_Error The post ID on success. The value `WP_Error` on failure.
	 */
	public function update( array $postarr ) {
		if ( ! empty( $postarr['lang'] ) ) {
			$post = get_post( $postarr['ID'] );
			if ( ! $post instanceof WP_Post ) {
				return new WP_Error( 'invalid_post', __( 'Invalid post ID.', 'polylang' ) );
			}

			$language = $this->languages->get( $postarr['lang'] );
			if ( ! $language instanceof PLL_Language ) {
				return new WP_Error( 'invalid_language', __( 'Please provide a valid language.', 'polylang' ) );
			}

			$this->set_language( $postarr['ID'], $language );
		}

		$post_id = wp_update_post( $postarr, true );
		if ( is_wp_error( $post_id ) ) {
			// Something went wrong!
			return $post_id;
		}

		if ( ! empty( $postarr['translations'] ) ) {
			$this->save_translations( $postarr['ID'], $postarr['translations'] );
		}

		return $post_id;
	}
}
