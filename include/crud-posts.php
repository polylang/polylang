<?php
/**
 * @package Polylang
 */

/**
 * Adds actions and filters related to languages when creating, updating or deleting posts.
 * Actions and filters triggered when reading posts are handled separately.
 *
 * @since 2.4
 */
class PLL_CRUD_Posts {
	/**
	 * @var PLL_Model
	 */
	protected $model;

	/**
	 * Preferred language to assign to a new post.
	 *
	 * @var PLL_Language|null
	 */
	protected $pref_lang;

	/**
	 * Current language.
	 *
	 * @var PLL_Language|null
	 */
	protected $curlang;

	/**
	 * Reference to the Polylang options array.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Constructor
	 *
	 * @since 2.4
	 *
	 * @param object $polylang The Polylang object.
	 */
	public function __construct( &$polylang ) {
		$this->options   = &$polylang->options;
		$this->model     = &$polylang->model;
		$this->pref_lang = &$polylang->pref_lang;
		$this->curlang   = &$polylang->curlang;

		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		add_action( 'set_object_terms', array( $this, 'set_object_terms' ), 10, 4 );
		add_filter( 'wp_insert_post_parent', array( $this, 'wp_insert_post_parent' ), 10, 2 );
		add_action( 'before_delete_post', array( $this, 'delete_post' ) );
		add_action( 'post_updated', array( $this, 'force_tags_translation' ), 10, 3 );

		// Specific for media
		if ( $polylang->options['media_support'] ) {
			add_action( 'add_attachment', array( $this, 'set_default_language' ) );
			add_action( 'delete_attachment', array( $this, 'delete_post' ) );
			add_filter( 'wp_delete_file', array( $this, 'wp_delete_file' ) );
		}
	}

	/**
	 * Allows to set a language by default for posts if it has no language yet.
	 *
	 * @since 1.5
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function set_default_language( $post_id ) {
		if ( ! $this->model->post->get_language( $post_id ) ) {
			if ( ! empty( $_GET['new_lang'] ) && $lang = $this->model->get_language( sanitize_key( $_GET['new_lang'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				// Defined only on admin.
				$this->model->post->set_language( $post_id, $lang );
			} elseif ( ! isset( $this->pref_lang ) && ! empty( $_REQUEST['lang'] ) && $lang = $this->model->get_language( sanitize_key( $_REQUEST['lang'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				// Testing $this->pref_lang makes this test pass only on admin.
				$this->model->post->set_language( $post_id, $lang );
			} elseif ( ( $parent_id = wp_get_post_parent_id( $post_id ) ) && $parent_lang = $this->model->post->get_language( $parent_id ) ) {
				$this->model->post->set_language( $post_id, $parent_lang );
			} elseif ( isset( $this->pref_lang ) ) {
				// Always defined on admin, never defined on frontend.
				$this->model->post->set_language( $post_id, $this->pref_lang );
			} elseif ( ! empty( $this->curlang ) ) {
				// Only on frontend due to the previous test always true on admin.
				$this->model->post->set_language( $post_id, $this->curlang );
			} else {
				// In all other cases set to default language.
				$this->model->post->set_language( $post_id, $this->options['default_lang'] );
			}
		}
	}

	/**
	 * Called when a post ( or page ) is saved, published or updated.
	 *
	 * @since 0.1
	 * @since 2.3 Does not save the language and translations anymore, unless the post has no language yet.
	 *
	 * @param int     $post_id Post id of the post being saved.
	 * @param WP_Post $post    The post being saved.
	 * @return void
	 */
	public function save_post( $post_id, $post ) {
		// Does nothing except on post types which are filterable.
		if ( $this->model->is_translated_post_type( $post->post_type ) ) {
			if ( $id = wp_is_post_revision( $post_id ) ) {
				$post_id = $id;
			}

			$lang = $this->model->post->get_language( $post_id );

			if ( empty( $lang ) ) {
				$this->set_default_language( $post_id );
			}

			/**
			 * Fires after the post language and translations are saved.
			 *
			 * @since 1.2
			 *
			 * @param int     $post_id      Post id.
			 * @param WP_Post $post         Post object.
			 * @param int[]   $translations The list of translations post ids.
			 */
			do_action( 'pll_save_post', $post_id, $post, $this->model->post->get_translations( $post_id ) );
		}
	}

	/**
	 * Makes sure that saved terms are in the right language.
	 *
	 * @since 2.3
	 *
	 * @param int            $object_id Object ID.
	 * @param int[]|string[] $terms     An array of object term IDs or slugs.
	 * @param int[]          $tt_ids    An array of term taxonomy IDs.
	 * @param string         $taxonomy  Taxonomy slug.
	 * @return void
	 */
	public function set_object_terms( $object_id, $terms, $tt_ids, $taxonomy ) {
		static $avoid_recursion;

		if ( $avoid_recursion || empty( $terms ) || ! is_array( $terms ) || ! $this->model->is_translated_taxonomy( $taxonomy ) ) {
			return;
		}

		$lang = $this->model->post->get_language( $object_id );

		if ( empty( $lang ) ) {
			return;
		}

		// Use the term_taxonomy_ids to get all the requested terms in 1 query.
		$new_terms = get_terms(
			array(
				'taxonomy'         => $taxonomy,
				'term_taxonomy_id' => array_map( 'intval', $tt_ids ),
				'lang'             => '',
			)
		);

		if ( empty( $new_terms ) || ! is_array( $new_terms ) ) {
			// Terms not found.
			return;
		}

		$new_term_ids_translated = $this->translate_terms( $new_terms, $taxonomy, $lang );

		// Query the object's term.
		$orig_terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'object_ids' => $object_id,
				'lang'       => '',
			)
		);

		if ( is_array( $orig_terms ) ) {
			$orig_term_ids            = wp_list_pluck( $orig_terms, 'term_id' );
			$orig_term_ids_translated = $this->translate_terms( $orig_terms, $taxonomy, $lang );

			// Terms that are not in the translated list.
			$remove_term_ids = array_diff( $orig_term_ids, $orig_term_ids_translated );

			if ( ! empty( $remove_term_ids ) ) {
				wp_remove_object_terms( $object_id, $remove_term_ids, $taxonomy );
			}
		} else {
			$orig_term_ids            = array();
			$orig_term_ids_translated = array();
		}

		// Terms to add.
		$add_term_ids = array_unique( array_merge( $orig_term_ids_translated, $new_term_ids_translated ) );
		$add_term_ids = array_diff( $add_term_ids, $orig_term_ids );

		if ( ! empty( $add_term_ids ) ) {
			$avoid_recursion = true;
			wp_set_object_terms( $object_id, $add_term_ids, $taxonomy, true ); // Append.
			$avoid_recursion = false;
		}
	}

	/**
	 * Make sure that the post parent is in the correct language.
	 *
	 * @since 1.8
	 *
	 * @param int $post_parent Post parent ID.
	 * @param int $post_id     Post ID.
	 * @return int
	 */
	public function wp_insert_post_parent( $post_parent, $post_id ) {
		$lang = $this->model->post->get_language( $post_id );
		$parent_post_type = $post_parent > 0 ? get_post_type( $post_parent ) : null;
		// Dont break the hierarchy in case the post has no language
		if ( ! empty( $lang ) && ! empty( $parent_post_type ) && $this->model->is_translated_post_type( $parent_post_type ) ) {
			$post_parent = $this->model->post->get_translation( $post_parent, $lang );
		}

		return $post_parent;
	}

	/**
	 * Called when a post, page or media is deleted
	 * Don't delete translations if this is a post revision thanks to AndyDeGroo who catched this bug
	 * http://wordpress.org/support/topic/plugin-polylang-quick-edit-still-breaks-translation-linking-of-pages-in-072
	 *
	 * @since 0.1
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function delete_post( $post_id ) {
		if ( ! wp_is_post_revision( $post_id ) ) {
			$this->model->post->delete_translation( $post_id );
		}
	}

	/**
	 * Prevents WP deleting files when there are still media using them.
	 *
	 * @since 0.9
	 *
	 * @param string $file Path to the file to delete.
	 * @return string Empty or unmodified path.
	 */
	public function wp_delete_file( $file ) {
		global $wpdb;

		$uploadpath = wp_upload_dir();

		// Get the main attached file.
		$attached_file = substr_replace( $file, '', 0, strlen( trailingslashit( $uploadpath['basedir'] ) ) );
		$attached_file = preg_replace( '#-\d+x\d+\.([a-z]+)$#', '.$1', $attached_file );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM $wpdb->postmeta
				WHERE meta_key = '_wp_attached_file' AND meta_value = %s",
				$attached_file
			)
		);

		if ( ! empty( $ids ) ) {
			return ''; // Prevent deleting the file.
		}

		return $file;
	}

	/**
	 * Creates a media translation
	 *
	 * @since 1.8
	 *
	 * @param int           $post_id Original attachment id.
	 * @param string|object $lang    New translation language.
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

		$lang = $this->model->get_language( $lang ); // Make sure we get a valid language slug.

		if ( empty( $lang ) ) {
			return 0;
		}

		// Create a new attachment ( translate attachment parent if exists ).
		add_filter( 'pll_enable_duplicate_media', '__return_false', 99 ); // Avoid a conflict with automatic duplicate at upload.
		unset( $post['ID'] ); // Will force the creation.
		if ( ! empty( $post['post_parent'] ) ) {
			$post['post_parent'] = (int) $this->model->post->get_translation( $post['post_parent'], $lang->slug );
		}
		$post['tax_input'] = array( 'language' => array( $lang->slug ) ); // Assigns the language.
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

		$this->model->post->set_language( $tr_id, $lang );

		$translations = $this->model->post->get_translations( $post_id );
		$translations[ $lang->slug ] = $tr_id;
		$this->model->post->save_translations( $tr_id, $translations );

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
		return $tr_id;
	}

	/**
	 * Ensure that tags are in the correct language when a post is updated, due to `tags_input` parameter being removed in `wp_update_post()`.
	 *
	 * @since 3.4.5
	 *
	 * @param int     $post_id      Post ID, unused.
	 * @param WP_Post $post_after   Post object following the update.
	 * @param WP_Post $post_before  Post object before the update.
	 * @return void
	 */
	public function force_tags_translation( $post_id, $post_after, $post_before ) {
		if ( ! is_object_in_taxonomy( $post_before->post_type, 'post_tag' ) ) {
			return;
		}

		$terms = get_the_terms( $post_before, 'post_tag' );

		if ( empty( $terms ) || ! is_array( $terms ) ) {
			return;
		}

		$term_ids = wp_list_pluck( $terms, 'term_id' );

		// Let's ensure that `PLL_CRUD_Posts::set_object_terms()` will do its job.
		wp_set_post_terms( $post_id, $term_ids, 'post_tag' );
	}

	/**
	 * Makes sure that all terms in the given list are in the given language.
	 * If not the case, the terms are translated or created (for a hierarchical taxonomy, terms are created recursively).
	 *
	 * @since 3.5
	 *
	 * @param WP_Term[]    $terms    List of terms to translate.
	 * @param string       $taxonomy The terms' taxonomy.
	 * @param PLL_Language $language The language to translate the terms into.
	 * @return int[] List of `term_id`s.
	 *
	 * @phpstan-return array<positive-int>
	 */
	private function translate_terms( array $terms, string $taxonomy, PLL_Language $language ): array {
		$term_ids_translated = array();

		foreach ( $terms as $term ) {
			$term_ids_translated[] = $this->translate_term( $term, $taxonomy, $language );
		}

		return array_filter( $term_ids_translated );
	}

	/**
	 * Translates the given term into the given language.
	 * If the translation doesn't exist, it is created (for a hierarchical taxonomy, terms are created recursively).
	 *
	 * @since 3.5
	 *
	 * @param WP_Term      $term     The term to translate.
	 * @param string       $taxonomy The term's taxonomy.
	 * @param PLL_Language $language The language to translate the term into.
	 * @return int A `term_id` on success, `0` on failure.
	 *
	 * @phpstan-return int<0, max>
	 */
	private function translate_term( WP_Term $term, string $taxonomy, PLL_Language $language ): int {
		// Check if the term is in the correct language or if a translation exists.
		$tr_term_id = $this->model->term->get( $term->term_id, $language );

		if ( ! empty( $tr_term_id ) ) {
			// Already in the correct language.
			return $tr_term_id;
		}

		// Or choose the correct language for tags (initially defined by name).
		$tr_term_id = $this->model->term_exists( $term->name, $taxonomy, $term->parent, $language );

		if ( ! empty( $tr_term_id ) ) {
			return $tr_term_id;
		}

		// Or create the term in the correct language.
		$tr_parent_term_id = 0;

		if ( $term->parent > 0 && is_taxonomy_hierarchical( $taxonomy ) ) {
			$parent = get_term( $term->parent, $taxonomy );

			if ( $parent instanceof WP_Term ) {
				// Translate the parent recursively.
				$tr_parent_term_id = $this->translate_term( $parent, $taxonomy, $language );
			}
		}

		$lang_callback   = function ( $lang, $tax, $slug ) use ( $language, $term, $taxonomy ) {
			if ( ! $lang instanceof PLL_Language && $tax === $taxonomy && $slug === $term->slug ) {
				return $language;
			}
			return $lang;
		};
		$parent_callback = function ( $parent_id, $tax, $slug ) use ( $tr_parent_term_id, $term, $taxonomy ) {
			if ( empty( $parent_id ) && $tax === $taxonomy && $slug === $term->slug ) {
				return $tr_parent_term_id;
			}
			return $parent_id;
		};
		add_filter( 'pll_inserted_term_language', $lang_callback, 10, 3 );
		add_filter( 'pll_inserted_term_parent', $parent_callback, 10, 3 );
		$new_term_info = wp_insert_term(
			$term->name,
			$taxonomy,
			array(
				'parent' => $tr_parent_term_id,
				'slug'   => $term->slug, // Useless but prevents the use of `sanitize_title()` and for consistency with `$lang_callback`.
			)
		);
		remove_filter( 'pll_inserted_term_language', $lang_callback );
		remove_filter( 'pll_inserted_term_parent', $parent_callback );

		if ( is_wp_error( $new_term_info ) ) {
			// Term creation failed.
			return 0;
		}

		$tr_term_id = max( 0, (int) $new_term_info['term_id'] );

		if ( empty( $tr_term_id ) ) {
			return 0;
		}

		$this->model->term->set_language( $tr_term_id, $language );

		$trs = $this->model->term->get_translations( $term->term_id );

		$trs[ $language->slug ] = $tr_term_id;

		$this->model->term->save_translations( $term->term_id, $trs );

		return $tr_term_id;
	}
}
