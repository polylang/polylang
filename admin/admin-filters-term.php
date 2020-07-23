<?php
/**
 * @package Polylang
 */

/**
 * Manages filters and actions related to terms on admin side
 *
 * @since 1.2
 */
class PLL_Admin_Filters_Term {
	public $links, $model, $options, $pref_lang;
	protected $pre_term_name; // Used to store the term name before creating a slug if needed
	protected $post_id; // Used to store the current post_id when bulk editing posts

	/**
	 * Constructor: setups filters and actions
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->links = &$polylang->links;
		$this->model = &$polylang->model;
		$this->options = &$polylang->options;
		$this->pref_lang = &$polylang->pref_lang;

		foreach ( $this->model->get_translated_taxonomies() as $tax ) {
			// Adds the language field in the 'Categories' and 'Post Tags' panels
			add_action( $tax . '_add_form_fields', array( $this, 'add_term_form' ) );

			// Adds the language field and translations tables in the 'Edit Category' and 'Edit Tag' panels
			add_action( $tax . '_edit_form_fields', array( $this, 'edit_term_form' ) );
		}

		// Adds actions related to languages when creating or saving categories and post tags
		add_filter( 'wp_dropdown_cats', array( $this, 'wp_dropdown_cats' ) );
		add_action( 'create_term', array( $this, 'save_term' ), 900, 3 );
		add_action( 'edit_term', array( $this, 'save_term' ), 900, 3 ); // Late as it may conflict with other plugins, see http://wordpress.org/support/topic/polylang-and-wordpress-seo-by-yoast
		add_action( 'pre_post_update', array( $this, 'pre_post_update' ) );
		add_filter( 'pre_term_name', array( $this, 'pre_term_name' ) );
		add_filter( 'pre_term_slug', array( $this, 'pre_term_slug' ), 10, 2 );

		// Ajax response for edit term form
		add_action( 'wp_ajax_term_lang_choice', array( $this, 'term_lang_choice' ) );
		add_action( 'wp_ajax_pll_terms_not_translated', array( $this, 'ajax_terms_not_translated' ) );

		// Allows to get the default categories in all languages
		add_filter( 'option_default_category', array( $this, 'option_default_category' ) );
		add_action( 'update_option_default_category', array( $this, 'update_option_default_category' ), 10, 2 );

		// Updates the translations term ids when splitting a shared term
		add_action( 'split_shared_term', array( $this, 'split_shared_term' ), 10, 4 ); // WP 4.2
	}

	/**
	 * Adds the language field in the 'Categories' and 'Post Tags' panels
	 *
	 * @since 0.1
	 */
	public function add_term_form() {
		if ( isset( $_GET['taxonomy'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$taxonomy = sanitize_key( $_GET['taxonomy'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( isset( $_REQUEST['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$post_type = sanitize_key( $_REQUEST['post_type'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( isset( $GLOBALS['post_type'] ) ) {
			$post_type = $GLOBALS['post_type'];
		}

		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) || ! post_type_exists( $post_type ) ) {
			return;
		}

		$from_term_id = isset( $_GET['from_tag'] ) ? (int) $_GET['from_tag'] : 0; // phpcs:ignore WordPress.Security.NonceVerification

		$lang = isset( $_GET['new_lang'] ) ? $this->model->get_language( sanitize_key( $_GET['new_lang'] ) ) : $this->pref_lang; // phpcs:ignore WordPress.Security.NonceVerification

		$dropdown = new PLL_Walker_Dropdown();

		$dropdown_html = $dropdown->walk(
			$this->model->get_languages_list(),
			-1,
			array(
				'name'     => 'term_lang_choice',
				'value'    => 'term_id',
				'selected' => $lang ? $lang->term_id : '',
				'flag'     => true,
			)
		);

		wp_nonce_field( 'pll_language', '_pll_nonce' );

		printf(
			'<div class="form-field">
				<label for="term_lang_choice">%s</label>
				<div id="select-add-term-language">%s</div>
				<p>%s</p>
			</div>',
			esc_html__( 'Language', 'polylang' ),
			$dropdown_html, // phpcs:ignore
			esc_html__( 'Sets the language', 'polylang' )
		);

		if ( ! empty( $from_term_id ) ) {
			printf( '<input type="hidden" name="from_tag" value="%d" />', (int) $from_term_id );
		}

		// Adds translation fields
		echo '<div id="term-translations" class="form-field">';
		if ( $lang ) {
			include __DIR__ . '/view-translations-term.php';
		}
		echo '</div>' . "\n";
	}

	/**
	 * Adds the language field and translations tables in the 'Edit Category' and 'Edit Tag' panels
	 *
	 * @since 0.1
	 *
	 * @param object $tag
	 */
	public function edit_term_form( $tag ) {
		if ( isset( $_REQUEST['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$post_type = sanitize_key( $_REQUEST['post_type'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( isset( $GLOBALS['post_type'] ) ) {
			$post_type = $GLOBALS['post_type'];
		}

		if ( ! post_type_exists( $post_type ) ) {
			return;
		}

		$term_id  = $tag->term_id;
		$taxonomy = $tag->taxonomy; // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable

		$lang = $this->model->term->get_language( $term_id );
		$lang = empty( $lang ) ? $this->pref_lang : $lang;

		// Disable the language dropdown and the translations input fields for default categories to prevent removal
		$disabled = in_array( get_option( 'default_category' ), $this->model->term->get_translations( $term_id ) );

		$dropdown = new PLL_Walker_Dropdown();

		$dropdown_html = $dropdown->walk(
			$this->model->get_languages_list(),
			-1,
			array(
				'name'     => 'term_lang_choice',
				'value'    => 'term_id',
				'selected' => $lang ? $lang->term_id : '',
				'disabled' => $disabled,
				'flag'     => true,
			)
		);

		wp_nonce_field( 'pll_language', '_pll_nonce' );

		printf(
			'<tr class="form-field">
				<th scope="row">
					<label for="term_lang_choice">%s</label>
				</th>
				<td id="select-edit-term-language">
					%s<br />
					<p class="description">%s</p>
				</td>
			</tr>',
			esc_html__( 'Language', 'polylang' ),
			$dropdown_html, // phpcs:ignore
			esc_html__( 'Sets the language', 'polylang' )
		);

		echo '<tr id="term-translations" class="form-field">';
		if ( $lang ) {
			include __DIR__ . '/view-translations-term.php';
		}
		echo '</tr>' . "\n";
	}

	/**
	 * Translates term parent if exists when using "Add new" ( translation )
	 *
	 * @since 0.7
	 *
	 * @param string $output html markup for dropdown list of categories
	 * @return string modified html
	 */
	public function wp_dropdown_cats( $output ) {
		if ( isset( $_GET['taxonomy'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$taxonomy = sanitize_key( $_GET['taxonomy'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( isset( $taxonomy, $_GET['from_tag'], $_GET['new_lang'] ) && taxonomy_exists( $taxonomy ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$term = get_term( (int) $_GET['from_tag'], $taxonomy ); // phpcs:ignore WordPress.Security.NonceVerification

			if ( $term && $id = $term->parent ) {
				$lang = $this->model->get_language( sanitize_key( $_GET['new_lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				if ( $parent = $this->model->term->get_translation( $id, $lang ) ) {
					return str_replace( '"' . $parent . '"', '"' . $parent . '" selected="selected"', $output );
				}
			}
		}
		return $output;
	}

	/**
	 * Stores the current post_id when bulk editing posts for use in save_language and pre_term_slug
	 *
	 * @since 1.7
	 *
	 * @param int $post_id
	 */
	public function pre_post_update( $post_id ) {
		if ( isset( $_GET['bulk_edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->post_id = $post_id;
		}
	}

	/**
	 * Saves language
	 *
	 * @since 1.5
	 *
	 * @param int    $term_id
	 * @param string $taxonomy
	 */
	protected function save_language( $term_id, $taxonomy ) {
		global $wpdb;
		// Security checks are necessary to accept language modifications
		// as 'wp_update_term' can be called from outside WP admin

		// Edit tags
		if ( isset( $_POST['term_lang_choice'] ) ) {
			if ( isset( $_POST['action'] ) && sanitize_key( $_POST['action'] ) === 'add-' . $taxonomy ) { // phpcs:ignore WordPress.Security.NonceVerification
				check_ajax_referer( 'add-' . $taxonomy, '_ajax_nonce-add-' . $taxonomy ); // Category metabox
			} else {
				check_admin_referer( 'pll_language', '_pll_nonce' ); // Edit tags or tags metabox
			}

			$this->model->term->set_language( $term_id, $this->model->get_language( sanitize_key( $_POST['term_lang_choice'] ) ) );
		}

		// *Post* bulk edit, in case a new term is created
		elseif ( isset( $_GET['bulk_edit'], $_GET['inline_lang_choice'] ) ) {
			check_admin_referer( 'bulk-posts' );

			// Bulk edit does not modify the language
			// So we possibly create a tag in several languages
			if ( -1 == $_GET['inline_lang_choice'] ) {
				// The language of the current term is set a according to the language of the current post
				$this->model->term->set_language( $term_id, $this->model->post->get_language( $this->post_id ) );
				$term = get_term( $term_id, $taxonomy );

				// Get all terms with the same name
				// FIXME backward compatibility WP < 4.2
				// No WP function to get all terms with the exact same name so let's use a custom query
				// $terms = get_terms( $taxonomy, array( 'name' => $term->name, 'hide_empty' => false, 'fields' => 'ids' ) ); should be OK in 4.2
				// I may need to rework the loop below
				$terms = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT t.term_id FROM $wpdb->terms AS t
						INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
						WHERE tt.taxonomy = %s AND t.name = %s",
						$taxonomy,
						$term->name
					)
				);

				// If we have several terms with the same name, they are translations of each other
				if ( count( $terms ) > 1 ) {
					$translations = array();

					foreach ( $terms as $term ) {
						$translations[ $this->model->term->get_language( $term->term_id )->slug ] = $term->term_id;
					}

					$this->model->term->save_translations( $term_id, $translations );
				}
			}

			else {
				$this->model->term->set_language( $term_id, $this->model->get_language( sanitize_key( $_GET['inline_lang_choice'] ) ) );
			}
		}

		// Quick edit
		elseif ( isset( $_POST['inline_lang_choice'] ) ) {
			check_ajax_referer(
				isset( $_POST['action'] ) && 'inline-save' == $_POST['action'] ? 'inlineeditnonce' : 'taxinlineeditnonce', // Post quick edit or tag quick edit ?
				'_inline_edit'
			);

			$old_lang = $this->model->term->get_language( $term_id ); // Stores the old  language
			$lang = $this->model->get_language( sanitize_key( $_POST['inline_lang_choice'] ) ); // New language
			$translations = $this->model->term->get_translations( $term_id );

			// Checks if the new language already exists in the translation group
			if ( $old_lang && $old_lang->slug != $lang->slug ) {
				if ( array_key_exists( $lang->slug, $translations ) ) {
					$this->model->term->delete_translation( $term_id );
				}

				elseif ( array_key_exists( $old_lang->slug, $translations ) ) {
					unset( $translations[ $old_lang->slug ] );
					$this->model->term->save_translations( $term_id, $translations );
				}
			}

			$this->model->term->set_language( $term_id, $lang ); // Set new language
		}

		// Edit post
		elseif ( isset( $_POST['post_lang_choice'] ) ) { // FIXME should be useless now
			check_admin_referer( 'pll_language', '_pll_nonce' );
			$this->model->term->set_language( $term_id, $this->model->get_language( sanitize_key( $_POST['post_lang_choice'] ) ) );
		}
	}

	/**
	 * Save translations from our form
	 *
	 * @since 1.5
	 *
	 * @param int $term_id
	 * @return array
	 */
	protected function save_translations( $term_id ) {
		// Security check as 'wp_update_term' can be called from outside WP admin
		check_admin_referer( 'pll_language', '_pll_nonce' );

		$translations = array();

		// Save translations after checking the translated term is in the right language ( as well as cast id to int )
		if ( isset( $_POST['term_tr_lang'] ) ) {
			foreach ( array_map( 'absint', $_POST['term_tr_lang'] ) as $lang => $tr_id ) {
				$tr_lang = $this->model->term->get_language( $tr_id );
				$translations[ $lang ] = $tr_lang && $tr_lang->slug == $lang ? $tr_id : 0;
			}
		}

		$this->model->term->save_translations( $term_id, $translations );

		return $translations;
	}

	/**
	 * Called when a category or post tag is created or edited
	 * Saves language and translations
	 *
	 * @since 0.1
	 *
	 * @param int    $term_id
	 * @param int    $tt_id    term taxonomy id
	 * @param string $taxonomy
	 */
	public function save_term( $term_id, $tt_id, $taxonomy ) {
		// Does nothing except on taxonomies which are filterable
		if ( ! $this->model->is_translated_taxonomy( $taxonomy ) ) {
			return;
		}

		// Capability check
		// As 'wp_update_term' can be called from outside WP admin
		// 2nd test for creating tags when creating / editing a post
		$tax = get_taxonomy( $taxonomy );
		if ( current_user_can( $tax->cap->edit_terms ) || ( isset( $_POST['tax_input'][ $taxonomy ] ) && current_user_can( $tax->cap->assign_terms ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->save_language( $term_id, $taxonomy );

			if ( isset( $_POST['term_tr_lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$this->save_translations( $term_id );
			}
		}
	}

	/**
	 * Stores the term name for use in pre_term_slug
	 *
	 * @since 0.9.5
	 *
	 * @param string $name term name
	 * @return string unmodified term name
	 */
	public function pre_term_name( $name ) {
		return $this->pre_term_name = $name;
	}

	/**
	 * Creates the term slug in case the term already exists in another language
	 *
	 * @since 0.9.5
	 *
	 * @param string $slug
	 * @param string $taxonomy
	 * @return string
	 */
	public function pre_term_slug( $slug, $taxonomy ) {
		$name = sanitize_title( $this->pre_term_name );

		// If the term already exists in another language
		if ( ! $slug && $this->model->is_translated_taxonomy( $taxonomy ) && term_exists( $name, $taxonomy ) ) {
			if ( isset( $_POST['term_lang_choice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$lang = $this->model->get_language( sanitize_key( $_POST['term_lang_choice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}

			elseif ( isset( $_POST['inline_lang_choice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$lang = $this->model->get_language( sanitize_key( $_POST['inline_lang_choice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}

			// *Post* bulk edit, in case a new term is created
			elseif ( isset( $_GET['bulk_edit'], $_GET['inline_lang_choice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				// Bulk edit does not modify the language
				if ( -1 == $_GET['inline_lang_choice'] ) { // phpcs:ignore WordPress.Security.NonceVerification
					$lang = $this->model->post->get_language( $this->post_id );
				} else {
					$lang = $this->model->get_language( sanitize_key( $_GET['inline_lang_choice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				}
			}

			if ( ! empty( $lang ) && ! $this->model->term_exists_by_slug( $name, $lang, $taxonomy ) ) {
				$slug = $name . '-' . $lang->slug;
			}
		}

		return $slug;
	}

	/**
	 * Ajax response for edit term form
	 *
	 * @since 0.2
	 */
	public function term_lang_choice() {
		check_ajax_referer( 'pll_language', '_pll_nonce' );

		if ( ! isset( $_POST['taxonomy'], $_POST['post_type'], $_POST['lang'] ) ) {
			wp_die( 0 );
		}

		$lang      = $this->model->get_language( sanitize_key( $_POST['lang'] ) );
		$term_id   = isset( $_POST['term_id'] ) ? (int) $_POST['term_id'] : null; // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable
		$taxonomy  = sanitize_key( $_POST['taxonomy'] ); // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable
		$post_type = sanitize_key( $_POST['post_type'] );

		if ( ! post_type_exists( $post_type ) || ! taxonomy_exists( $taxonomy ) ) {
			wp_die( 0 );
		}

		ob_start();
		if ( $lang ) {
			include __DIR__ . '/view-translations-term.php';
		}
		$x = new WP_Ajax_Response( array( 'what' => 'translations', 'data' => ob_get_contents() ) );
		ob_end_clean();

		// Parent dropdown list ( only for hierarchical taxonomies )
		// $args copied from edit_tags.php except echo
		if ( is_taxonomy_hierarchical( $taxonomy ) ) {
			$args = array(
				'hide_empty'       => 0,
				'hide_if_empty'    => false,
				'taxonomy'         => $taxonomy,
				'name'             => 'parent',
				'orderby'          => 'name',
				'hierarchical'     => true,
				'show_option_none' => __( 'None', 'polylang' ),
				'echo'             => 0,
			);
			$x->Add( array( 'what' => 'parent', 'data' => wp_dropdown_categories( $args ) ) );
		}

		// Tag cloud
		// Tests copied from edit_tags.php
		else {
			$tax = get_taxonomy( $taxonomy );
			if ( ! is_null( $tax->labels->popular_items ) ) {
				$args = array( 'taxonomy' => $taxonomy, 'echo' => false );
				if ( current_user_can( $tax->cap->edit_terms ) ) {
					$args = array_merge( $args, array( 'link' => 'edit' ) );
				}

				if ( $tag_cloud = wp_tag_cloud( $args ) ) {
					$html = sprintf( '<div class="tagcloud"><h2>%1$s</h2>%2$s</div>', esc_html( $tax->labels->popular_items ), $tag_cloud );
					$x->Add( array( 'what' => 'tag_cloud', 'data' => $html ) );
				}
			}
		}

		// Flag
		$x->Add( array( 'what' => 'flag', 'data' => empty( $lang->flag ) ? esc_html( $lang->slug ) : $lang->flag ) );

		$x->send();
	}

	/**
	 * Ajax response for input in translation autocomplete input box
	 *
	 * @since 1.5
	 */
	public function ajax_terms_not_translated() {
		check_ajax_referer( 'pll_language', '_pll_nonce' );

		if ( ! isset( $_GET['term'], $_GET['post_type'], $_GET['taxonomy'], $_GET['term_language'], $_GET['translation_language'] ) ) {
			wp_die( 0 );
		}

		$s = wp_unslash( $_GET['term'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$post_type = sanitize_key( $_GET['post_type'] );
		$taxonomy  = sanitize_key( $_GET['taxonomy'] );

		if ( ! post_type_exists( $post_type ) || ! taxonomy_exists( $taxonomy ) ) {
			wp_die( 0 );
		}

		$term_language = $this->model->get_language( sanitize_key( $_GET['term_language'] ) );
		$translation_language = $this->model->get_language( sanitize_key( $_GET['translation_language'] ) );

		$terms  = array();
		$return = array();

		// Add current translation in list.
		// Not in add term as term_id is not set.
		if ( isset( $_GET['term_id'] ) && 'undefined' !== $_GET['term_id'] && $term_id = $this->model->term->get_translation( (int) $_GET['term_id'], $translation_language ) ) {
			$terms = array( get_term( $term_id, $taxonomy ) );
		}

		// It is more efficient to use one common query for all languages as soon as there are more than 2.
		foreach ( get_terms( $taxonomy, 'hide_empty=0&lang=0&name__like=' . $s ) as $term ) {
			$lang = $this->model->term->get_language( $term->term_id );

			if ( $lang && $lang->slug == $translation_language->slug && ! $this->model->term->get_translation( $term->term_id, $term_language ) ) {
				$terms[] = $term;
			}
		}

		// Format the ajax response.
		foreach ( $terms as $term ) {
			$return[] = array(
				'id'    => $term->term_id,
				'value' => rtrim( // Trim the seperator added at the end by WP.
					get_term_parents_list(
						$term->term_id,
						$term->taxonomy,
						array(
							'separator' => ' > ',
							'link' => false,
						)
					),
					' >'
				),
				'link'  => $this->links->edit_term_translation_link( $term->term_id, $term->taxonomy, $post_type ),
			);
		}

		wp_die( wp_json_encode( $return ) );
	}

	/**
	 * Filters the default category in note below the category list table and in settings->writing dropdown
	 *
	 * @since 1.2
	 *
	 * @param int $value
	 * @return int
	 */
	public function option_default_category( $value ) {
		if ( isset( $this->pref_lang ) && $tr = $this->model->term->get( $value, $this->pref_lang ) ) {
			$value = $tr;
		}
		return $value;
	}

	/**
	 * Checks if the new default category is translated in all languages
	 * If not, create the translations
	 *
	 * @since 1.7
	 *
	 * @param int $old_value
	 * @param int $value
	 */
	public function update_option_default_category( $old_value, $value ) {
		$default_cat_lang = $this->model->term->get_language( $value );

		// Assign a default language to default category
		if ( ! $default_cat_lang ) {
			$default_cat_lang = $this->model->get_language( $this->options['default_lang'] );
			$this->model->term->set_language( (int) $value, $default_cat_lang );
		}

		foreach ( $this->model->get_languages_list() as $language ) {
			if ( $language->slug != $default_cat_lang->slug && ! $this->model->term->get_translation( $value, $language ) ) {
				$this->model->create_default_category( $language );
			}
		}
	}

	/**
	 * Updates the translations term ids when splitting a shared term
	 * Splits translations if these are shared terms too
	 *
	 * @since 1.7
	 *
	 * @param int    $term_id          Shared term_id
	 * @param int    $new_term_id
	 * @param int    $term_taxonomy_id
	 * @param string $taxonomy
	 */
	public function split_shared_term( $term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {
		if ( ! $this->model->is_translated_taxonomy( $taxonomy ) ) {
			return;
		}

		// Avoid recursion
		static $avoid_recursion = false;
		if ( $avoid_recursion ) {
			return;
		}

		$avoid_recursion = true;
		$lang = $this->model->term->get_language( $term_id );
		$translations = array();

		foreach ( $this->model->term->get_translations( $term_id ) as $key => $tr_id ) {
			if ( $lang->slug == $key ) {
				$translations[ $key ] = $new_term_id;
			}
			else {
				$tr_term = get_term( $tr_id, $taxonomy );
				$translations[ $key ] = _split_shared_term( $tr_id, $tr_term->term_taxonomy_id );

				// Hack translation ids sent by the form to avoid overwrite in PLL_Admin_Filters_Term::save_translations
				if ( isset( $_POST['term_tr_lang'][ $key ] ) && $_POST['term_tr_lang'][ $key ] == $tr_id ) { // phpcs:ignore WordPress.Security.NonceVerification
					$_POST['term_tr_lang'][ $key ] = $translations[ $key ];
				}
			}
			$this->model->term->set_language( $translations[ $key ], $key );
		}

		$this->model->term->save_translations( $new_term_id, $translations );
		$avoid_recursion = false;
	}
}
