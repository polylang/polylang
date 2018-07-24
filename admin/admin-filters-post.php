<?php

/**
 * Manages filters and actions related to posts on admin side
 *
 * @since 1.2
 */
class PLL_Admin_Filters_Post extends PLL_Admin_Filters_Post_Base {
	public $options, $curlang;

	/**
	 * Constructor: setups filters and actions
	 *
	 * @since 1.2
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );
		$this->options = &$polylang->options;
		$this->curlang = &$polylang->curlang;

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// Filters posts, pages and media by language
		add_action( 'parse_query', array( $this, 'parse_query' ) );

		// Adds the Languages box in the 'Edit Post' and 'Edit Page' panels
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );

		// Ajax response for changing the language in the post metabox
		add_action( 'wp_ajax_post_lang_choice', array( $this, 'post_lang_choice' ) );
		add_action( 'wp_ajax_pll_posts_not_translated', array( $this, 'ajax_posts_not_translated' ) );

		// Adds actions and filters related to languages when creating, saving or deleting posts and pages
		add_action( 'load-post.php', array( $this, 'edit_post' ) );
		add_action( 'load-edit.php', array( $this, 'bulk_edit_posts' ) );
		add_action( 'wp_ajax_inline-save', array( $this, 'inline_edit_post' ), 0 ); // Before WordPress
		add_action( 'save_post', array( $this, 'save_post' ), 21, 3 ); // Priority 21 to come after advanced custom fields ( 20 ) and before the event calendar which breaks everything after 25
		add_action( 'set_object_terms', array( $this, 'set_object_terms' ), 10, 4 );
		add_filter( 'wp_insert_post_parent', array( $this, 'wp_insert_post_parent' ), 10, 4 );
		add_action( 'before_delete_post', array( $this, 'delete_post' ) );
		if ( $this->options['media_support'] ) {
			add_action( 'delete_attachment', array( $this, 'delete_post' ) ); // Action shared with media
		}

		// Filters the pages by language in the parent dropdown list in the page attributes metabox
		add_filter( 'page_attributes_dropdown_pages_args', array( $this, 'page_attributes_dropdown_pages_args' ), 10, 2 );

		// Sets the language in Tiny MCE
		add_filter( 'tiny_mce_before_init', array( $this, 'tiny_mce_before_init' ) );
	}

	/**
	 * Outputs a javascript list of terms ordered by language and hierarchical taxonomies
	 * to filter the category checklist per post language in quick edit
	 * Outputs a javascript list of pages ordered by language
	 * to filter the parent dropdown per post language in quick edit
	 *
	 * @since 1.7
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();

		// Hierarchical taxonomies
		if ( 'edit' == $screen->base && $taxonomies = get_object_taxonomies( $screen->post_type, 'object' ) ) {
			// Get translated hierarchical taxonomies
			foreach ( $taxonomies as $taxonomy ) {
				if ( $taxonomy->hierarchical && $taxonomy->show_in_quick_edit && $this->model->is_translated_taxonomy( $taxonomy->name ) ) {
					$hierarchical_taxonomies[] = $taxonomy->name;
				}
			}

			if ( ! empty( $hierarchical_taxonomies ) ) {
				$terms = get_terms( $hierarchical_taxonomies, array( 'get' => 'all' ) );

				foreach ( $terms as $term ) {
					if ( $lang = $this->model->term->get_language( $term->term_id ) ) {
						$term_languages[ $lang->slug ][ $term->taxonomy ][] = $term->term_id;
					}
				}

				// Send all these data to javascript
				if ( ! empty( $term_languages ) ) {
					wp_localize_script( 'pll_post', 'pll_term_languages', $term_languages );
				}
			}
		}

		// Hierarchical post types
		if ( 'edit' == $screen->base && is_post_type_hierarchical( $screen->post_type ) ) {
			$pages = get_pages();

			foreach ( $pages as $page ) {
				if ( $lang = $this->model->post->get_language( $page->ID ) ) {
					$page_languages[ $lang->slug ][] = $page->ID;
				}
			}

			// Send all these data to javascript
			if ( ! empty( $page_languages ) ) {
				wp_localize_script( 'pll_post', 'pll_page_languages', $page_languages );
			}
		}
	}

	/**
	 * Filters posts, pages and media by language
	 *
	 * @since 0.1
	 *
	 * @param object $query a WP_Query object
	 */
	public function parse_query( $query ) {
		$pll_query = new PLL_Query( $query, $this->model );
		$pll_query->filter_query( $this->curlang );
	}

	/**
	 * Adds the Language box in the 'Edit Post' and 'Edit Page' panels ( as well as in custom post types panels )
	 *
	 * @since 0.1
	 *
	 * @param string $post_type Current post type
	 * @param object $post      Current post
	 */
	public function add_meta_boxes( $post_type, $post ) {
		if ( $this->model->is_translated_post_type( $post_type ) ) {
			add_meta_box( 'ml_box', __( 'Languages', 'polylang' ), array( $this, 'post_language' ), $post_type, 'side', 'high' );
		}
	}

	/**
	 * Displays the Languages metabox in the 'Edit Post' and 'Edit Page' panels
	 *
	 * @since 0.1
	 */
	public function post_language() {
		global $post_ID;
		$post_id = $post_ID;
		$post_type = get_post_type( $post_ID );

		$lang = ( $lg = $this->model->post->get_language( $post_ID ) ) ? $lg :
			( isset( $_GET['new_lang'] ) ? $this->model->get_language( $_GET['new_lang'] ) :
			$this->pref_lang );

		$dropdown = new PLL_Walker_Dropdown();

		wp_nonce_field( 'pll_language', '_pll_nonce' );

		// NOTE: the class "tags-input" allows to include the field in the autosave $_POST ( see autosave.js )
		printf( '
			<p><strong>%1$s</strong></p>
			<label class="screen-reader-text" for="%2$s">%1$s</label>
			<div id="select-%3$s-language">%4$s</div>',
			esc_html__( 'Language', 'polylang' ),
			$id = ( 'attachment' === $post_type ) ? sprintf( 'attachments[%d][language]', $post_ID ) : 'post_lang_choice',
			'attachment' === $post_type ? 'media' : 'post',
			$dropdown->walk( $this->model->get_languages_list(), array(
				'name'     => $id,
				'class'    => 'post_lang_choice tags-input',
				'selected' => $lang ? $lang->slug : '',
				'flag'     => true,
			) )
		);

		/**
		 * Fires before displaying the list of translations in the Languages metabox for posts
		 *
		 * @since 1.8
		 */
		do_action( 'pll_before_post_translations', $post_type );

		echo '<div id="post-translations" class="translations">';
		if ( $lang ) {
			include PLL_ADMIN_INC . '/view-translations-' . ( 'attachment' == $post_type ? 'media' : 'post' ) . '.php';
		}
		echo '</div>' . "\n";
	}

	/**
	 * Ajax response for changing the language in the post metabox
	 *
	 * @since 0.2
	 */
	public function post_lang_choice() {
		check_ajax_referer( 'pll_language', '_pll_nonce' );

		global $post_ID; // Obliged to use the global variable for wp_popular_terms_checklist
		$post_id = $post_ID = (int) $_POST['post_id'];
		$lang = $this->model->get_language( $_POST['lang'] );

		$post_type = $_POST['post_type'];
		$post_type_object = get_post_type_object( $post_type );
		if ( ! current_user_can( $post_type_object->cap->edit_post, $post_ID ) ) {
			wp_die( -1 );
		}

		$this->model->post->set_language( $post_ID, $lang ); // Save language, useful to set the language when uploading media from post

		ob_start();
		if ( $lang ) {
			include PLL_ADMIN_INC . '/view-translations-' . ( 'attachment' == $post_type ? 'media' : 'post' ) . '.php';
		}
		$x = new WP_Ajax_Response( array( 'what' => 'translations', 'data' => ob_get_contents() ) );
		ob_end_clean();

		// Categories
		if ( isset( $_POST['taxonomies'] ) ) {
			// Not set for pages
			foreach ( $_POST['taxonomies'] as $taxname ) {
				$taxonomy = get_taxonomy( $taxname );

				ob_start();
				$popular_ids = wp_popular_terms_checklist( $taxonomy->name );
				$supplemental['populars'] = ob_get_contents();
				ob_end_clean();

				ob_start();
				// Use $post_ID to remember checked terms in case we come back to the original language
				wp_terms_checklist( $post_ID, array( 'taxonomy' => $taxonomy->name, 'popular_cats' => $popular_ids ) );
				$supplemental['all'] = ob_get_contents();
				ob_end_clean();

				$supplemental['dropdown'] = wp_dropdown_categories( array(
					'taxonomy'         => $taxonomy->name,
					'hide_empty'       => 0,
					'name'             => 'new' . $taxonomy->name . '_parent',
					'orderby'          => 'name',
					'hierarchical'     => 1,
					'show_option_none' => '&mdash; ' . $taxonomy->labels->parent_item . ' &mdash;',
					'echo'             => 0,
				) );

				$x->Add( array( 'what' => 'taxonomy', 'data' => $taxonomy->name, 'supplemental' => $supplemental ) );
			}
		}

		// Parent dropdown list ( only for hierarchical post types )
		if ( in_array( $post_type, get_post_types( array( 'hierarchical' => true ) ) ) ) {
			$post = get_post( $post_ID );

			// Args and filter from 'page_attributes_meta_box' in wp-admin/includes/meta-boxes.php of WP 4.2.1
			$dropdown_args = array(
				'post_type'        => $post->post_type,
				'exclude_tree'     => $post->ID,
				'selected'         => $post->post_parent,
				'name'             => 'parent_id',
				'show_option_none' => __( '(no parent)' ),
				'sort_column'      => 'menu_order, post_title',
				'echo'             => 0,
			);

			/** This filter is documented in wp-admin/includes/meta-boxes.php */
			$dropdown_args = apply_filters( 'page_attributes_dropdown_pages_args', $dropdown_args, $post ); // Since WP 3.3

			$x->Add( array( 'what' => 'pages', 'data' => wp_dropdown_pages( $dropdown_args ) ) );
		}

		// Flag
		$x->Add( array( 'what' => 'flag', 'data' => empty( $lang->flag ) ? esc_html( $lang->slug ) : $lang->flag ) );

		// Sample permalink
		$x->Add( array( 'what' => 'permalink', 'data' => get_sample_permalink_html( $post_ID ) ) );

		$x->send();
	}

	/**
	 * Ajax response for input in translation autocomplete input box
	 *
	 * @since 1.5
	 */
	public function ajax_posts_not_translated() {
		check_ajax_referer( 'pll_language', '_pll_nonce' );

		if ( ! post_type_exists( $_GET['post_type'] ) ) {
			die( 0 );
		}

		$post_language = $this->model->get_language( $_GET['post_language'] );
		$translation_language = $this->model->get_language( $_GET['translation_language'] );

		// Don't order by title: see https://wordpress.org/support/topic/find-translated-post-when-10-is-not-enough
		$args = array(
			's'                => wp_unslash( $_GET['term'] ),
			'suppress_filters' => 0, // To make the post_fields filter work
			'lang'             => 0, // Avoid admin language filter
			'numberposts'      => 20, // Limit to 20 posts
			'post_status'      => 'any',
			'post_type'        => $_GET['post_type'],
			'tax_query'        => array(
				array(
					'taxonomy' => 'language',
					'field'    => 'term_taxonomy_id', // WP 3.5+
					'terms'    => $translation_language->term_taxonomy_id,
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
		$args = apply_filters( 'pll_ajax_posts_not_translated_args', $args );
		$posts = get_posts( $args );

		$return = array();

		foreach ( $posts as $key => $post ) {
			if ( ! $this->model->post->get_translation( $post->ID, $post_language ) ) {
				$return[] = array(
					'id' => $post->ID,
					'value' => $post->post_title,
					'link' => $this->links->edit_post_translation_link( $post->ID ),
				);
			}
		}

		// Add current translation in list
		if ( $post_id = $this->model->post->get_translation( (int) $_GET['pll_post_id'], $translation_language ) ) {
			$post = get_post( $post_id );
			array_unshift( $return, array(
				'id' => $post_id,
				'value' => $post->post_title,
				'link' => $this->links->edit_post_translation_link( $post_id ),
			) );
		}

		wp_die( json_encode( $return ) );
	}

	/**
	 * Save language and translation when editing a post (post.php)
	 *
	 * @since 2.3
	 */
	public function edit_post() {
		if ( isset( $_POST['post_lang_choice'], $_POST['post_ID'] ) && $post_id = (int) $_POST['post_ID'] ) {
			check_admin_referer( 'pll_language', '_pll_nonce' );

			$post = get_post( $post_id );
			$post_type_object = get_post_type_object( $post->post_type );

			if ( current_user_can( $post_type_object->cap->edit_post, $post_id ) ) {
				$this->model->post->set_language( $post_id, $this->model->get_language( $_POST['post_lang_choice'] ) );

				if ( isset( $_POST['post_tr_lang'] ) ) {
					$this->save_translations( $post_id, $_POST['post_tr_lang'] );
				}
			}
		}
	}

	/**
	 * Save language when inline editing or bulk editing a post
	 * Fix translations if necessary
	 *
	 * @since 2.3
	 *
	 * @param int    $post_id Post ID
	 * @param object $lang    Language
	 */
	protected function inline_save_language( $post_id, $lang ) {
		$post = get_post( $post_id );
		$post_type_object = get_post_type_object( $post->post_type );

		if ( current_user_can( $post_type_object->cap->edit_post, $post_id ) ) {
			$old_lang = $this->model->post->get_language( $post_id ); // Stores the old  language
			$this->model->post->set_language( $post_id, $lang ); // set new language

			// Checks if the new language already exists in the translation group
			if ( $old_lang && $old_lang->slug != $lang->slug ) {
				$translations = $this->model->post->get_translations( $post_id );

				// If yes, separate this post from the translation group
				if ( array_key_exists( $lang->slug, $translations ) ) {
					$this->model->post->delete_translation( $post_id );
				}

				elseif ( array_key_exists( $old_lang->slug, $translations ) ) {
					unset( $translations[ $old_lang->slug ] );
					$this->model->post->save_translations( $post_id, $translations );
				}
			}
		}
	}

	/**
	 * Save language when bulk editing a post
	 *
	 * @since 2.3
	 */
	public function bulk_edit_posts() {
		if ( isset( $_GET['bulk_edit'], $_GET['inline_lang_choice'] ) && -1 !== $_GET['inline_lang_choice'] ) {
			check_admin_referer( 'bulk-posts' );

			if ( $lang = $this->model->get_language( $_GET['inline_lang_choice'] ) ) {
				$post_ids = array_map( 'intval', (array) $_REQUEST['post'] );
				foreach ( $post_ids as $post_id ) {
					$this->inline_save_language( $post_id, $lang );
				}
			}
		}
	}

	/**
	 * Save language when inline editing a post
	 *
	 * @since 2.3
	 */
	public function inline_edit_post() {
		check_admin_referer( 'inlineeditnonce', '_inline_edit' );

		if ( isset( $_POST['post_ID'], $_POST['inline_lang_choice'] ) ) {
			$post_id = (int) $_POST['post_ID'];
			$lang = $this->model->get_language( $_POST['inline_lang_choice'] );
			if ( $post_id && $lang ) {
				$this->inline_save_language( $post_id, $lang );
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
	 * @param bool   $update  Whether it is an update or not
	 */
	public function save_post( $post_id, $post, $update ) {
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
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      An array of object terms.
	 * @param array  $tt_ids     An array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
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
	 * Filters the pages by language in the parent dropdown list in the page attributes metabox
	 *
	 * @since 0.6
	 *
	 * @param array  $dropdown_args Arguments passed to wp_dropdown_pages
	 * @param object $post
	 * @return array Modified arguments
	 */
	public function page_attributes_dropdown_pages_args( $dropdown_args, $post ) {
		$dropdown_args['lang'] = isset( $_POST['lang'] ) ? $this->model->get_language( $_POST['lang'] ) : $this->model->post->get_language( $post->ID ); // ajax or not ?
		if ( ! $dropdown_args['lang'] ) {
			$dropdown_args['lang'] = $this->pref_lang;
		}

		return $dropdown_args;
	}

	/**
	 * Sets the language attribute and text direction for Tiny MCE
	 *
	 * @since 2.2
	 *
	 * @param array $mce_init TinyMCE config
	 * @return array
	 */
	public function tiny_mce_before_init( $mce_init ) {
		if ( ! empty( $this->curlang ) ) {
			$mce_init['wp_lang_attr'] = $this->curlang->get_locale( 'display' );
			$mce_init['directionality'] = $this->curlang->is_rtl ? 'rtl' : 'ltr';
		}
		return $mce_init;
	}
}
