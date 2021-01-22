<?php
/**
 * @package Polylang
 */

/**
 * Manages filters and actions related to the classic editor
 *
 * @since 2.4
 */
class PLL_Admin_Classic_Editor {
	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * @var PLL_Admin_Links
	 */
	public $links;

	/**
	 * Current language (used to filter the content).
	 *
	 * @var PLL_Language
	 */
	public $curlang;

	/**
	 * Preferred language to assign to new contents.
	 *
	 * @var PLL_Language
	 */
	public $pref_lang;

	/**
	 * Constructor: setups filters and actions
	 *
	 * @since 2.4
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->model = &$polylang->model;
		$this->links = &$polylang->links;
		$this->curlang = &$polylang->curlang;
		$this->pref_lang = &$polylang->pref_lang;

		// Adds the Languages box in the 'Edit Post' and 'Edit Page' panels
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		// Ajax response for changing the language in the post metabox
		add_action( 'wp_ajax_post_lang_choice', array( $this, 'post_lang_choice' ) );
		add_action( 'wp_ajax_pll_posts_not_translated', array( $this, 'ajax_posts_not_translated' ) );

		// Filters the pages by language in the parent dropdown list in the page attributes metabox
		add_filter( 'page_attributes_dropdown_pages_args', array( $this, 'page_attributes_dropdown_pages_args' ), 10, 2 );

		// Notice
		add_action( 'edit_form_top', array( $this, 'edit_form_top' ) );
	}

	/**
	 * Adds the Language box in the 'Edit Post' and 'Edit Page' panels ( as well as in custom post types panels )
	 *
	 * @since 0.1
	 *
	 * @param string $post_type Current post type
	 * @return void
	 */
	public function add_meta_boxes( $post_type ) {
		if ( $this->model->is_translated_post_type( $post_type ) ) {
			add_meta_box(
				'ml_box',
				__( 'Languages', 'polylang' ),
				array( $this, 'post_language' ),
				$post_type,
				'side',
				'high',
				array(
					'__back_compat_meta_box' => pll_use_block_editor_plugin(),
				)
			);
		}
	}

	/**
	 * Displays the Languages metabox in the 'Edit Post' and 'Edit Page' panels
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function post_language() {
		global $post_ID;
		$post_type = get_post_type( $post_ID );

		// phpcs:ignore WordPress.Security.NonceVerification, VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$from_post_id = isset( $_GET['from_post'] ) ? (int) $_GET['from_post'] : 0;

		$lang = ( $lg = $this->model->post->get_language( $post_ID ) ) ? $lg :
			( isset( $_GET['new_lang'] ) ? $this->model->get_language( sanitize_key( $_GET['new_lang'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification
			$this->pref_lang );

		$dropdown = new PLL_Walker_Dropdown();

		$id = ( 'attachment' === $post_type ) ? sprintf( 'attachments[%d][language]', (int) $post_ID ) : 'post_lang_choice';

		$dropdown_html = $dropdown->walk(
			$this->model->get_languages_list(),
			-1,
			array(
				'name'     => $id,
				'class'    => 'post_lang_choice tags-input',
				'selected' => $lang ? $lang->slug : '',
				'flag'     => true,
			)
		);

		wp_nonce_field( 'pll_language', '_pll_nonce' );

		// NOTE: the class "tags-input" allows to include the field in the autosave $_POST ( see autosave.js )
		printf(
			'<p><strong>%1$s</strong></p>
			<label class="screen-reader-text" for="%2$s">%1$s</label>
			<div id="select-%3$s-language">%4$s</div>',
			esc_html__( 'Language', 'polylang' ),
			esc_attr( $id ),
			( 'attachment' === $post_type ? 'media' : 'post' ),
			$dropdown_html // phpcs:ignore WordPress.Security.EscapeOutput
		);

		/**
		 * Fires before displaying the list of translations in the Languages metabox for posts
		 *
		 * @since 1.8
		 */
		do_action( 'pll_before_post_translations', $post_type );

		echo '<div id="post-translations" class="translations">';
		if ( $lang ) {
			if ( 'attachment' === $post_type ) {
				include __DIR__ . '/view-translations-media.php';
			} else {
				include __DIR__ . '/view-translations-post.php';
			}
		}
		echo '</div>' . "\n";
	}

	/**
	 * Ajax response for changing the language in the post metabox
	 *
	 * @since 0.2
	 *
	 * @return void
	 */
	public function post_lang_choice() {
		check_ajax_referer( 'pll_language', '_pll_nonce' );

		if ( ! isset( $_POST['post_id'], $_POST['lang'], $_POST['post_type'] ) ) {
			wp_die( 0 );
		}

		global $post_ID; // Obliged to use the global variable for wp_popular_terms_checklist
		$post_ID   = (int) $_POST['post_id'];
		$lang      = $this->model->get_language( sanitize_key( $_POST['lang'] ) );
		$post_type = sanitize_key( $_POST['post_type'] );

		if ( empty( $lang ) || ! post_type_exists( $post_type ) ) {
			wp_die( 0 );
		}

		$post_type_object = get_post_type_object( $post_type );

		if ( empty( $post_type_object ) ) {
			wp_die( 0 );
		}

		if ( ! current_user_can( $post_type_object->cap->edit_post, $post_ID ) ) {
			wp_die( -1 );
		}

		$this->model->post->set_language( $post_ID, $lang ); // Save language, useful to set the language when uploading media from post

		// We also need to save the translations to match the language change
		$translations = $this->model->post->get_translations( $post_ID );
		$translations = array_diff( $translations, array( $post_ID ) );
		$this->model->post->save_translations( $post_ID, $translations );

		ob_start();
		if ( 'attachment' === $post_type ) {
			include __DIR__ . '/view-translations-media.php';
		} else {
			include __DIR__ . '/view-translations-post.php';
		}
		$x = new WP_Ajax_Response( array( 'what' => 'translations', 'data' => ob_get_contents() ) );
		ob_end_clean();

		// Categories
		if ( isset( $_POST['taxonomies'] ) ) { // Not set for pages
			$supplemental = array();

			foreach ( array_map( 'sanitize_key', $_POST['taxonomies'] ) as $taxname ) {
				$taxonomy = get_taxonomy( $taxname );

				if ( ! empty( $taxonomy ) ) {
					ob_start();
					$popular_ids = wp_popular_terms_checklist( $taxonomy->name );
					$supplemental['populars'] = ob_get_contents();
					ob_end_clean();

					ob_start();
					// Use $post_ID to remember checked terms in case we come back to the original language
					wp_terms_checklist( $post_ID, array( 'taxonomy' => $taxonomy->name, 'popular_cats' => $popular_ids ) );
					$supplemental['all'] = ob_get_contents();
					ob_end_clean();

					$supplemental['dropdown'] = wp_dropdown_categories(
						array(
							'taxonomy'         => $taxonomy->name,
							'hide_empty'       => 0,
							'name'             => 'new' . $taxonomy->name . '_parent',
							'orderby'          => 'name',
							'hierarchical'     => 1,
							'show_option_none' => '&mdash; ' . $taxonomy->labels->parent_item . ' &mdash;',
							'echo'             => 0,
						)
					);

					$x->Add( array( 'what' => 'taxonomy', 'data' => $taxonomy->name, 'supplemental' => $supplemental ) );
				}
			}
		}

		// Parent dropdown list ( only for hierarchical post types )
		if ( in_array( $post_type, get_post_types( array( 'hierarchical' => true ) ) ) ) {
			$post = get_post( $post_ID );

			if ( ! empty( $post ) ) {
				// Args and filter from 'page_attributes_meta_box' in wp-admin/includes/meta-boxes.php of WP 4.2.1
				$dropdown_args = array(
					'post_type'        => $post->post_type,
					'exclude_tree'     => $post->ID,
					'selected'         => $post->post_parent,
					'name'             => 'parent_id',
					'show_option_none' => __( '(no parent)', 'polylang' ),
					'sort_column'      => 'menu_order, post_title',
					'echo'             => 0,
				);

				/** This filter is documented in wp-admin/includes/meta-boxes.php */
				$dropdown_args = apply_filters( 'page_attributes_dropdown_pages_args', $dropdown_args, $post ); // Since WP 3.3

				$x->Add( array( 'what' => 'pages', 'data' => wp_dropdown_pages( $dropdown_args ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
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
	 *
	 * @return void
	 */
	public function ajax_posts_not_translated() {
		check_ajax_referer( 'pll_language', '_pll_nonce' );

		if ( ! isset( $_GET['post_type'], $_GET['post_language'], $_GET['translation_language'], $_GET['term'], $_GET['pll_post_id'] ) ) {
			wp_die( 0 );
		}

		$post_type = sanitize_key( $_GET['post_type'] );

		if ( ! post_type_exists( $post_type ) ) {
			wp_die( 0 );
		}

		$term = wp_unslash( $_GET['term'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		$post_language = $this->model->get_language( sanitize_key( $_GET['post_language'] ) );
		$translation_language = $this->model->get_language( sanitize_key( $_GET['translation_language'] ) );

		$return = array();

		$untranslated_posts = $this->model->post->get_untranslated( $post_type, $post_language, $translation_language, $term );

		// format output
		foreach ( $untranslated_posts as $post ) {
			$return[] = array(
				'id'    => $post->ID,
				'value' => $post->post_title,
				'link'  => $this->links->edit_post_translation_link( $post->ID ),
			);
		}

		// Add current translation in list
		if ( $post_id = $this->model->post->get_translation( (int) $_GET['pll_post_id'], $translation_language ) ) {
			$post = get_post( $post_id );

			if ( ! empty( $post ) ) {
				array_unshift(
					$return,
					array(
						'id'    => $post_id,
						'value' => $post->post_title,
						'link'  => $this->links->edit_post_translation_link( $post_id ),
					)
				);
			}
		}

		wp_die( wp_json_encode( $return ) );
	}

	/**
	 * Filters the pages by language in the parent dropdown list in the page attributes metabox.
	 *
	 * @since 0.6
	 *
	 * @param array   $dropdown_args Arguments passed to wp_dropdown_pages().
	 * @param WP_Post $post          The page being edited.
	 * @return array Modified arguments.
	 */
	public function page_attributes_dropdown_pages_args( $dropdown_args, $post ) {
		$dropdown_args['lang'] = isset( $_POST['lang'] ) ? $this->model->get_language( sanitize_key( $_POST['lang'] ) ) : $this->model->post->get_language( $post->ID ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $dropdown_args['lang'] ) {
			$dropdown_args['lang'] = $this->pref_lang;
		}

		return $dropdown_args;
	}

	/**
	 * Displays a notice if the user has not sufficient rights to overwrite synchronized taxonomies and metas.
	 *
	 * @since 2.6
	 *
	 * @param WP_Post $post the post currently being edited.
	 * @return void
	 */
	public function edit_form_top( $post ) {
		if ( ! $this->model->post->current_user_can_synchronize( $post->ID ) ) {
			?>
			<div class="pll-notice notice notice-warning">
				<p>
					<?php
					esc_html_e( 'Some taxonomies or metadata may be synchronized with existing translations that you are not allowed to modify.', 'polylang' );
					echo ' ';
					esc_html_e( 'If you attempt to modify them anyway, your changes will not be saved.', 'polylang' );
					?>
				</p>
			</div>
			<?php
		}
	}
}
