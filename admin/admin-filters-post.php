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
	/**
	 * Current language (used to filter the content).
	 *
	 * @var PLL_Language|null
	 */
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
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();

		if ( empty( $screen ) ) {
			return;
		}

		// Hierarchical taxonomies
		if ( 'edit' == $screen->base && $taxonomies = get_object_taxonomies( $screen->post_type, 'objects' ) ) {
			// Get translated hierarchical taxonomies
			$hierarchical_taxonomies = array();
			foreach ( $taxonomies as $taxonomy ) {
				if ( $taxonomy->hierarchical && $taxonomy->show_in_quick_edit && $this->model->is_translated_taxonomy( $taxonomy->name ) ) {
					$hierarchical_taxonomies[] = $taxonomy->name;
				}
			}

			if ( ! empty( $hierarchical_taxonomies ) ) {
				$terms          = get_terms( array( 'taxonomy' => $hierarchical_taxonomies, 'get' => 'all' ) );
				$term_languages = array();

				if ( is_array( $terms ) ) {
					foreach ( $terms as $term ) {
						if ( $lang = $this->model->term->get_language( $term->term_id ) ) {
							$term_languages[ $lang->slug ][ $term->taxonomy ][] = $term->term_id;
						}
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

			update_post_caches( $pages, $screen->post_type, true, false );

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
	 * Filters posts, pages and media by language.
	 *
	 * @since 0.1
	 *
	 * @param WP_Query $query WP_Query object.
	 * @return void
	 */
	public function parse_query( $query ) {
		$pll_query = new PLL_Query( $query, $this->model );
		$pll_query->filter_query( $this->curlang );
	}

	/**
	 * Save language and translation when editing a post (post.php)
	 *
	 * @since 2.3
	 *
	 * @return void
	 */
	public function edit_post() {
		if ( isset( $_POST['post_lang_choice'], $_POST['post_ID'] ) && $post_id = (int) $_POST['post_ID'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			check_admin_referer( 'pll_language', '_pll_nonce' );

			$post = get_post( $post_id );

			if ( empty( $post ) ) {
				return;
			}

			$post_type_object = get_post_type_object( $post->post_type );

			if ( empty( $post_type_object ) ) {
				return;
			}

			if ( current_user_can( $post_type_object->cap->edit_post, $post_id ) ) {
				$this->model->post->set_language( $post_id, $this->model->get_language( sanitize_key( $_POST['post_lang_choice'] ) ) );

				if ( isset( $_POST['post_tr_lang'] ) ) {
					$this->save_translations( $post_id, array_map( 'absint', $_POST['post_tr_lang'] ) );
				}
			}
		}
	}

	/**
	 * Save language when bulk editing a post
	 *
	 * @since 2.3
	 *
	 * @return void
	 */
	public function bulk_edit_posts() {
		if ( isset( $_GET['bulk_edit'], $_GET['inline_lang_choice'], $_REQUEST['post'] ) && -1 !== $_GET['inline_lang_choice'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			check_admin_referer( 'bulk-posts' );

			if ( $lang = $this->model->get_language( sanitize_key( $_GET['inline_lang_choice'] ) ) ) {
				$post_ids = array_map( 'intval', (array) $_REQUEST['post'] );
				foreach ( $post_ids as $post_id ) {
					if ( current_user_can( 'edit_post', $post_id ) ) {
						$this->model->post->update_language( $post_id, $lang );
					}
				}
			}
		}
	}

	/**
	 * Save language when inline editing a post
	 *
	 * @since 2.3
	 *
	 * @return void
	 */
	public function inline_edit_post() {
		check_admin_referer( 'inlineeditnonce', '_inline_edit' );

		if ( isset( $_POST['post_ID'], $_POST['inline_lang_choice'] ) ) {
			$post_id = (int) $_POST['post_ID'];
			$lang = $this->model->get_language( sanitize_key( $_POST['inline_lang_choice'] ) );
			if ( $post_id && $lang && current_user_can( 'edit_post', $post_id ) ) {
				$this->model->post->update_language( $post_id, $lang );
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
