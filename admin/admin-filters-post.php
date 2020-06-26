<?php
/**
 * @package Polylang
 */

/**
 * Manages filters and actions related to posts on admin side
 *
 * @since 1.2
 */
class PLL_Admin_Filters_Post extends PLL_Admin_Filters_Post_Base {
	public $curlang;

	/**
	 * Constructor: setups filters and actions
	 *
	 * @since 1.2
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );
		$this->curlang = &$polylang->curlang;

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// Filters posts, pages and media by language
		add_action( 'parse_query', array( $this, 'parse_query' ) );

		// Adds actions and filters related to languages when creating, saving or deleting posts and pages
		add_action( 'load-post.php', array( $this, 'edit_post' ) );
		add_action( 'load-edit.php', array( $this, 'bulk_edit_posts' ) );
		add_action( 'wp_ajax_inline-save', array( $this, 'inline_edit_post' ), 0 ); // Before WordPress

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
			$hierarchical_taxonomies = array();
			foreach ( $taxonomies as $taxonomy ) {
				if ( $taxonomy->hierarchical && $taxonomy->show_in_quick_edit && $this->model->is_translated_taxonomy( $taxonomy->name ) ) {
					$hierarchical_taxonomies[] = $taxonomy->name;
				}
			}

			if ( ! empty( $hierarchical_taxonomies ) ) {
				$terms          = get_terms( $hierarchical_taxonomies, array( 'get' => 'all' ) );
				$term_languages = array();

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
			$pages = get_pages( array( 'sort_column' => 'menu_order, post_title' ) ); // Same arguments as the parent pages dropdown to avoid an extra query.
			update_post_caches( $pages, $screen->post_type );

			$page_languages = array();

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
	 * Save language and translation when editing a post (post.php)
	 *
	 * @since 2.3
	 */
	public function edit_post() {
		if ( isset( $_POST['post_lang_choice'], $_POST['post_ID'] ) && $post_id = (int) $_POST['post_ID'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			check_admin_referer( 'pll_language', '_pll_nonce' );

			$post = get_post( $post_id );
			$post_type_object = get_post_type_object( $post->post_type );

			if ( current_user_can( $post_type_object->cap->edit_post, $post_id ) ) {
				$this->model->post->set_language( $post_id, $this->model->get_language( sanitize_key( $_POST['post_lang_choice'] ) ) );

				if ( isset( $_POST['post_tr_lang'] ) ) {
					$this->save_translations( $post_id, array_map( 'absint', $_POST['post_tr_lang'] ) );
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
		if ( isset( $_GET['bulk_edit'], $_GET['inline_lang_choice'], $_REQUEST['post'] ) && -1 !== $_GET['inline_lang_choice'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			check_admin_referer( 'bulk-posts' );

			if ( $lang = $this->model->get_language( sanitize_key( $_GET['inline_lang_choice'] ) ) ) {
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
			$lang = $this->model->get_language( sanitize_key( $_POST['inline_lang_choice'] ) );
			if ( $post_id && $lang ) {
				$this->inline_save_language( $post_id, $lang );
			}
		}
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
