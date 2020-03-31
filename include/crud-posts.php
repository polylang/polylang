<?php
/**
 * @package Polylang
 */

/**
 * Adds actions and filters related to languages when creating, updating or deleting posts
 * Actions a filters used when reaing posts are handled separately
 *
 * @since 2.4
 */
class PLL_CRUD_Posts {

	/**
	 * Constructor
	 *
	 * @since 2.4
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->model = &$polylang->model;
		$this->pref_lang = &$polylang->pref_lang;
		$this->curlang = &$polylang->curlang;

		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		add_action( 'set_object_terms', array( $this, 'set_object_terms' ), 10, 4 );
		add_filter( 'wp_insert_post_parent', array( $this, 'wp_insert_post_parent' ), 10, 4 );
		add_action( 'before_delete_post', array( $this, 'delete_post' ) );

		// Specific for media
		if ( $polylang->options['media_support'] ) {
			add_action( 'add_attachment', array( $this, 'set_default_language' ) );
			add_action( 'delete_attachment', array( $this, 'delete_post' ) );
			add_filter( 'wp_delete_file', array( $this, 'wp_delete_file' ) );
		}
	}

	/**
	 * Allows to set a language by default for posts if it has no language yet
	 *
	 * @since 1.5
	 *
	 * @param int $post_id
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
				// Always defined on admin, never defined on frontend
				$this->model->post->set_language( $post_id, $this->pref_lang );
			} else {
				// Only on frontend due to the previous test always true on admin
				$this->model->post->set_language( $post_id, $this->curlang );
			}
		}
	}

	/**
	 * Called when a post ( or page ) is saved, published or updated
	 *
	 * @since 0.1
	 * @since 2.3 Does not save the language and translations anymore, unless the post has no language yet
	 *
	 * @param int    $post_id
	 * @param object $post
	 */
	public function save_post( $post_id, $post ) {
		// Does nothing except on post types which are filterable
		if ( $this->model->is_translated_post_type( $post->post_type ) ) {
			if ( $id = wp_is_post_revision( $post_id ) ) {
				$post_id = $id;
			}

			$lang = $this->model->post->get_language( $post_id );

			if ( empty( $lang ) ) {
				$this->set_default_language( $post_id );
			}

			/**
			 * Fires after the post language and translations are saved
			 *
			 * @since 1.2
			 *
			 * @param int    $post_id      Post id
			 * @param object $post         Post object
			 * @param array  $translations The list of translations post ids
			 */
			do_action( 'pll_save_post', $post_id, $post, $this->model->post->get_translations( $post_id ) );
		}
	}

	/**
	 * Make sure saved terms are in the right language (especially tags with same name in different languages)
	 *
	 * @since 2.3
	 *
	 * @param int    $object_id Object ID.
	 * @param array  $terms     An array of object terms.
	 * @param array  $tt_ids    An array of term taxonomy IDs.
	 * @param string $taxonomy  Taxonomy slug.
	 */
	public function set_object_terms( $object_id, $terms, $tt_ids, $taxonomy ) {
		static $avoid_recursion;

		if ( ! $avoid_recursion && $this->model->is_translated_taxonomy( $taxonomy ) && ! empty( $terms ) ) {
			$lang = $this->model->post->get_language( $object_id );

			if ( ! empty( $lang ) && is_array( $terms ) ) {
				// Convert to term ids if we got tag names
				$strings = array_filter( $terms, 'is_string' );
				if ( ! empty( $strings ) ) {
					$_terms = get_terms( $taxonomy, array( 'name' => $strings, 'object_ids' => $object_id, 'fields' => 'ids' ) );
					$terms = array_merge( array_diff( $terms, $strings ), $_terms );
				}

				$term_ids = array_combine( $terms, $terms );
				$languages = array_map( array( $this->model->term, 'get_language' ), $term_ids );
				$languages = array_filter( $languages ); // Remove terms without language.
				$languages = wp_list_pluck( $languages, 'slug' );
				$wrong_terms = array_diff( $languages, array( $lang->slug ) );

				if ( ! empty( $wrong_terms ) ) {
					// We got terms in a wrong language
					$wrong_term_ids = array_keys( $wrong_terms );
					$terms = get_the_terms( $object_id, $taxonomy );
					wp_remove_object_terms( $object_id, $wrong_term_ids, $taxonomy );

					if ( is_array( $terms ) ) {
						$newterms = array();

						foreach ( $terms as $term ) {
							if ( in_array( $term->term_id, $wrong_term_ids ) ) {
								// Check if the term is in the correct language or if a translation exist ( mainly for default category )
								if ( $newterm = $this->model->term->get( $term->term_id, $lang ) ) {
									$newterms[] = (int) $newterm;
								}

								// Or choose the correct language for tags ( initially defined by name )
								elseif ( $newterm = $this->model->term_exists( $term->name, $taxonomy, $term->parent, $lang ) ) {
									$newterms[] = (int) $newterm; // Cast is important otherwise we get 'numeric' tags
								}

								// Or create the term in the correct language
								elseif ( ! is_wp_error( $term_info = wp_insert_term( $term->name, $taxonomy ) ) ) {
									$newterms[] = (int) $term_info['term_id'];
								}
							}
						}

						$avoid_recursion = true;
						wp_set_object_terms( $object_id, array_unique( $newterms ), $taxonomy, true ); // Append
						$avoid_recursion = false;
					}
				}
			}
		}
	}

	/**
	 * Make sure that the post parent is in the correct language when using bulk edit
	 *
	 * @since 1.8
	 *
	 * @param int   $post_parent Post parent ID.
	 * @param int   $post_id     Post ID.
	 * @param array $new_postarr Array of parsed post data.
	 * @param array $postarr     Array of sanitized, but otherwise unmodified post data.
	 * @return int
	 */
	public function wp_insert_post_parent( $post_parent, $post_id, $new_postarr, $postarr ) {
		if ( isset( $postarr['bulk_edit'], $postarr['inline_lang_choice'] ) ) {
			check_admin_referer( 'bulk-posts' );
			$lang = -1 == $postarr['inline_lang_choice'] ?
				$this->model->post->get_language( $post_id ) :
				$this->model->get_language( $postarr['inline_lang_choice'] );
			// Dont break the hierarchy in case the post has no language
			if ( ! empty( $lang ) ) {
				$post_parent = $this->model->post->get_translation( $post_parent, $lang );
			}
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
	 * @param int $post_id
	 */
	public function delete_post( $post_id ) {
		if ( ! wp_is_post_revision( $post_id ) ) {
			$this->model->post->delete_translation( $post_id );
		}
	}

	/**
	 * Prevents WP deleting files when there are still media using them
	 * Thanks to Bruno "Aesqe" Babic and its plugin file gallery in which I took all the ideas for this function
	 *
	 * @since 0.9
	 *
	 * @param string $file Path to the file to delete.
	 * @return string Empty or unmodified path.
	 */
	public function wp_delete_file( $file ) {
		global $wpdb;

		$uploadpath = wp_upload_dir();

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM $wpdb->postmeta
				WHERE meta_key = '_wp_attached_file' AND meta_value = %s",
				substr_replace( $file, '', 0, strlen( trailingslashit( $uploadpath['basedir'] ) ) )
			)
		);

		if ( ! empty( $ids ) ) {
			// Regenerate intermediate sizes if it's an image ( since we could not prevent WP deleting them before ).
			require_once ABSPATH . 'wp-admin/includes/image.php'; // In case the file is deleted outside admin.
			wp_update_attachment_metadata( $ids[0], wp_slash( wp_generate_attachment_metadata( $ids[0], $file ) ) ); // Directly uses update_post_meta, so expects slashed.
			return ''; // Prevent deleting the main file.
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
			return $post_id;
		}

		$post = get_post( $post_id );

		if ( empty( $post ) ) {
			return $post;
		}

		$lang = $this->model->get_language( $lang ); // Make sure we get a valid language slug.

		// Create a new attachment ( translate attachment parent if exists ).
		add_filter( 'pll_enable_duplicate_media', '__return_false', 99 ); // Avoid a conflict with automatic duplicate at upload.
		$post->ID = null; // Will force the creation
		$post->post_parent = ( $post->post_parent && $tr_parent = $this->model->post->get_translation( $post->post_parent, $lang->slug ) ) ? $tr_parent : 0;
		$post->tax_input = array( 'language' => array( $lang->slug ) ); // Assigns the language.
		$tr_id = wp_insert_attachment( wp_slash( (array) $post ) );
		remove_filter( 'pll_enable_duplicate_media', '__return_false', 99 ); // Restore automatic duplicate at upload.

		// Copy metadata.
		if ( $data = wp_get_attachment_metadata( $post_id, true ) ) { // Unfiltered.
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
}
