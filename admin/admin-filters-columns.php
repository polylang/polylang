<?php
/**
 * @package Polylang
 */

/**
 * Adds the language column in posts and terms list tables
 * Manages quick edit and bulk edit as well
 *
 * @since 1.2
 */
class PLL_Admin_Filters_Columns {
	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * This class is instantiated on `wp_loaded` (prio 5), see `PLL_Admin::add_filters()`.
	 * `$polylang->links` is instantiated on `wp_loaded` (prio 1), see `PLL_Admin_Base::init()`.
	 * This means `$polylang->links` cannot be `null`.
	 *
	 * @var PLL_Admin_Links
	 */
	public $links;

	/**
	 * Language selected in the admin language filter.
	 *
	 * @var PLL_Language|null
	 */
	public $filter_lang;

	/**
	 * Constructor: setups filters and actions
	 *
	 * @since 1.2
	 *
	 * @param object $polylang The Polylang object.
	 */
	public function __construct( &$polylang ) {
		$this->links = &$polylang->links;
		$this->model = &$polylang->model;
		$this->filter_lang = &$polylang->filter_lang;

		// Hide the column of the filtered language.
		add_filter( 'hidden_columns', array( $this, 'hidden_columns' ) ); // Since WP 4.4.

		// Add the language and translations columns in 'All Posts', 'All Pages' and 'Media library' panels.
		foreach ( $this->model->get_translated_post_types() as $type ) {
			// Use the latest filter late as some plugins purely overwrite what's done by others :(
			// Specific case for media.
			add_filter( 'manage_' . ( 'attachment' == $type ? 'upload' : 'edit-' . $type ) . '_columns', array( $this, 'add_post_column' ), 100 );
			add_action( 'manage_' . ( 'attachment' == $type ? 'media' : $type . '_posts' ) . '_custom_column', array( $this, 'post_column' ), 10, 2 );
		}

		// Quick edit and bulk edit.
		add_filter( 'quick_edit_custom_box', array( $this, 'quick_edit_custom_box' ) );
		add_filter( 'bulk_edit_custom_box', array( $this, 'quick_edit_custom_box' ) );

		// Adds the language column in the 'Categories' and 'Post Tags' tables.
		foreach ( $this->model->get_translated_taxonomies() as $tax ) {
			add_filter( 'manage_edit-' . $tax . '_columns', array( $this, 'add_term_column' ) );
			add_filter( 'manage_' . $tax . '_custom_column', array( $this, 'term_column' ), 10, 3 );
		}

		// Ajax responses to update list table rows.
		add_action( 'wp_ajax_pll_update_post_rows', array( $this, 'ajax_update_post_rows' ) );
		add_action( 'wp_ajax_pll_update_term_rows', array( $this, 'ajax_update_term_rows' ) );
	}

	/**
	 * Adds languages and translations columns in posts, pages, media, categories and tags tables.
	 *
	 * @since 0.8.2
	 *
	 * @param string[] $columns List of table columns.
	 * @param string   $before  The column before which we want to add our languages.
	 * @return string[] Modified list of columns.
	 */
	protected function add_column( $columns, $before ) {
		if ( $n = array_search( $before, array_keys( $columns ) ) ) {
			$end = array_slice( $columns, $n );
			$columns = array_slice( $columns, 0, $n );
		}

		foreach ( $this->model->get_languages_list() as $language ) {
			$columns[ 'language_' . $language->slug ] = $this->get_flag_html( $language ) . '<span class="screen-reader-text">' . esc_html( $language->name ) . '</span>';
		}

		return isset( $end ) ? array_merge( $columns, $end ) : $columns;
	}

	/**
	 * Returns the first language column in posts, pages, media, categories and tags tables.
	 *
	 * @since 0.9
	 *
	 * @return string first language column name.
	 */
	protected function get_first_language_column() {
		foreach ( $this->model->get_languages_list() as $language ) {
			return 'language_' . $language->slug;
		}

		return '';
	}

	/**
	 * Hides the column for the filtered language.
	 *
	 * @since 2.7
	 *
	 * @param string[] $hidden Array of hidden columns.
	 * @return string[]
	 */
	public function hidden_columns( $hidden ) {
		if ( ! empty( $this->filter_lang ) ) {
			$hidden[] = 'language_' . $this->filter_lang->slug;
		}
		return $hidden;
	}

	/**
	 * Adds the language and translations columns ( before the comments column ) in the posts, pages and media library tables.
	 *
	 * @since 0.1
	 *
	 * @param string[] $columns List of posts table columns.
	 * @return string[] Modified list of columns.
	 */
	public function add_post_column( $columns ) {
		return $this->add_column( $columns, 'comments' );
	}

	/**
	 * Fills the language and translations columns in the posts, pages and media library tables
	 * take care that when doing ajax inline edit, the post may not be updated in database yet.
	 *
	 * @since 0.1
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function post_column( $column, $post_id ) {
		if ( false === strpos( $column, 'language_' ) ) {
			return;
		}

		$post_id = (int) $post_id;
		$inline  = wp_doing_ajax() && isset( $_REQUEST['action'], $_POST['inline_lang_choice'] ) && 'inline-save' === $_REQUEST['action']; // phpcs:ignore WordPress.Security.NonceVerification
		$lang    = $inline ? $this->model->get_language( sanitize_key( $_POST['inline_lang_choice'] ) ) : $this->model->post->get_language( $post_id ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( empty( $lang ) ) {
			return;
		}

		$language = $this->model->get_language( substr( $column, 9 ) );

		if ( empty( $language ) ) {
			return;
		}

		// Hidden field containing the post language for quick edit.
		if ( $column === $this->get_first_language_column() ) {
			printf( '<div class="hidden" id="lang_%d">%s</div>', $post_id, esc_html( $lang->slug ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$tr_id   = $this->model->post->get( $post_id, $language );
		$tr_post = $tr_id ? get_post( $tr_id ) : false;

		if ( ! $tr_post instanceof WP_Post ) {
			// Link to add a new translation: no translation for this language yet, or it doesn't exist anymore.
			echo $this->links->new_post_translation_link( $post_id, $language ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		// Link to edit (or not) the post or a translation.
		$url = $this->links->get_edit_post_translation_link( $tr_post->ID, $language );
		echo $this->get_item_edition_link( $url, $tr_post->ID, $tr_post->post_title, $language, $tr_post->ID === $post_id ? 'flag' : 'icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Quick edit & bulk edit
	 *
	 * @since 0.9
	 *
	 * @param string $column column name
	 * @return string unmodified $column
	 */
	public function quick_edit_custom_box( $column ) {
		if ( $column == $this->get_first_language_column() ) {

			$elements = $this->model->languages->filter( 'translator' )->get_list();
			if ( current_filter() == 'bulk_edit_custom_box' ) {
				array_unshift( $elements, (object) array( 'slug' => -1, 'name' => __( '&mdash; No Change &mdash;', 'polylang' ) ) );
			}

			$dropdown = new PLL_Walker_Dropdown();
			// The hidden field 'old_lang' allows to pass the old language to ajax request
			printf(
				'<fieldset class="inline-edit-col-left">
					<div class="inline-edit-col">
						<label class="alignleft">
							<span class="title">%s</span>
							%s
						</label>
					</div>
				</fieldset>',
				esc_html__( 'Language', 'polylang' ),
				$dropdown->walk( $elements, -1, array( 'name' => 'inline_lang_choice', 'id' => '' ) ) // phpcs:ignore WordPress.Security.EscapeOutput
			);
		}
		return $column;
	}

	/**
	 * Adds the language column ( before the posts column ) in the 'Categories' or 'Post Tags' table.
	 *
	 * @since 0.1
	 *
	 * @param string[] $columns List of terms table columns.
	 * @return string[] modified List of columns.
	 */
	public function add_term_column( $columns ) {
		$screen = get_current_screen();

		// Avoid displaying languages in screen options when editing a term.
		if ( $screen instanceof WP_Screen && 'term' === $screen->base ) {
			return $columns;
		}

		return $this->add_column( $columns, 'posts' );
	}

	/**
	 * Fills the language column in the taxonomy terms list table.
	 *
	 * @since 0.1
	 *
	 * @param string $out     Column output.
	 * @param string $column  Column name.
	 * @param int    $term_id Term ID.
	 * @return string
	 */
	public function term_column( $out, $column, $term_id ) {
		if ( false === strpos( $column, 'language_' ) ) {
			return $out;
		}

		$term_id = (int) $term_id;
		$inline  = wp_doing_ajax() && isset( $_REQUEST['action'], $_POST['inline_lang_choice'] ) && 'inline-save-tax' === $_REQUEST['action']; // phpcs:ignore WordPress.Security.NonceVerification
		$lang    = $inline ? $this->model->get_language( sanitize_key( $_POST['inline_lang_choice'] ) ) : $this->model->term->get_language( $term_id ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( empty( $lang ) ) {
			return $out;
		}

		if ( isset( $GLOBALS['post_type'] ) ) {
			$post_type = $GLOBALS['post_type'];
		} elseif ( isset( $_REQUEST['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$post_type = sanitize_key( $_REQUEST['post_type'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( isset( $GLOBALS['taxonomy'] ) ) {
			$taxonomy = $GLOBALS['taxonomy'];
		} elseif ( isset( $_REQUEST['taxonomy'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$taxonomy = sanitize_key( $_REQUEST['taxonomy'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( ! isset( $taxonomy, $post_type ) || ! post_type_exists( $post_type ) || ! taxonomy_exists( $taxonomy ) ) {
			return $out;
		}

		$language = $this->model->get_language( substr( $column, 9 ) );

		if ( empty( $language ) ) {
			return $out;
		}

		$tr_id   = $this->model->term->get( $term_id, $language );
		$tr_term = $tr_id ? get_term( $tr_id, $taxonomy ) : false;

		if ( ! $tr_term instanceof WP_Term ) {
			// Link to add a new translation: no translation for this language yet, or it doesn't exist anymore.
			return $out . $this->links->new_term_translation_link( $term_id, $taxonomy, $post_type, $language );
		}

		// Link to edit (or not) the term or a translation.
		$url  = $this->links->get_edit_term_translation_link( $tr_term->term_id, $taxonomy, $post_type, $language );
		$out .= $this->get_item_edition_link( $url, $tr_term->term_id, $tr_term->name, $language, $tr_term->term_id === $term_id ? 'flag' : 'icon' );

		if ( $this->get_first_language_column() !== $column ) {
			return $out;
		}

		$out .= sprintf( '<div class="hidden" id="lang_%d">%s</div>', $term_id, esc_html( $lang->slug ) );

		/**
		 * Filters the output of the first language column in the terms list table.
		 *
		 * @since 3.7
		 *
		 * @param string $output  First language column output.
		 * @param int    $term_id Term ID.
		 * @param string $lang    Language code.
		 */
		return apply_filters( 'pll_first_language_term_column', $out, $term_id, $lang->slug );
	}

	/**
	 * Update rows of translated posts when the language is modified in quick edit
	 *
	 * @since 1.7
	 *
	 * @return void
	 */
	public function ajax_update_post_rows() {
		check_ajax_referer( 'inlineeditnonce', '_pll_nonce' );

		if ( ! isset( $_POST['post_type'], $_POST['post_id'], $_POST['screen'] ) ) {
			wp_die( 0 );
		}

		$post_type = sanitize_key( $_POST['post_type'] );

		if ( ! post_type_exists( $post_type ) || ! $this->model->is_translated_post_type( $post_type ) ) {
			wp_die( 0 );
		}

		/** @var WP_Posts_List_Table $wp_list_table */
		$wp_list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => sanitize_key( $_POST['screen'] ) ) );

		$x = new WP_Ajax_Response();

		// Collect old translations
		$translations = empty( $_POST['translations'] ) ? array() : explode( ',', $_POST['translations'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$translations = array_map( 'intval', $translations );

		$translations = array_merge( $translations, array( (int) $_POST['post_id'] ) ); // Add current post

		foreach ( $translations as $post_id ) {
			$level = is_post_type_hierarchical( $post_type ) ? count( get_ancestors( $post_id, $post_type ) ) : 0;
			if ( $post = get_post( $post_id ) ) {
				ob_start();
				$wp_list_table->single_row( $post, $level );
				$data = (string) ob_get_clean();
				$x->add( array( 'what' => 'row', 'data' => $data, 'supplemental' => array( 'post_id' => $post_id ) ) );
			}
		}

		$x->send();
	}

	/**
	 * Update rows of translated terms when adding / deleting a translation or when the language is modified in quick edit
	 *
	 * @since 1.7
	 *
	 * @return void
	 */
	public function ajax_update_term_rows() {
		check_ajax_referer( 'pll_language', '_pll_nonce' );

		if ( ! isset( $_POST['taxonomy'], $_POST['term_id'], $_POST['screen'] ) ) {
			wp_die( 0 );
		}

		$taxonomy = sanitize_key( $_POST['taxonomy'] );

		if ( ! taxonomy_exists( $taxonomy ) || ! $this->model->is_translated_taxonomy( $taxonomy ) ) {
			wp_die( 0 );
		}

		/** @var WP_Terms_List_Table $wp_list_table */
		$wp_list_table = _get_list_table( 'WP_Terms_List_Table', array( 'screen' => sanitize_key( $_POST['screen'] ) ) );

		$x = new WP_Ajax_Response();

		// Collect old translations
		$translations = empty( $_POST['translations'] ) ? array() : explode( ',', $_POST['translations'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$translations = array_map( 'intval', $translations );

		$translations = array_merge( $translations, $this->model->term->get_translations( (int) $_POST['term_id'] ) ); // Add current translations
		$translations = array_unique( $translations ); // Remove duplicates

		foreach ( $translations as $term_id ) {
			$level = is_taxonomy_hierarchical( $taxonomy ) ? count( get_ancestors( $term_id, $taxonomy ) ) : 0;
			$tag   = get_term( $term_id, $taxonomy );

			if ( ! $tag instanceof WP_Term ) {
				continue;
			}

			ob_start();
			$wp_list_table->single_row( $tag, $level );
			$data = (string) ob_get_clean();
			$x->add( array( 'what' => 'row', 'data' => $data, 'supplemental' => array( 'term_id' => $term_id ) ) );
		}

		$x->send();
	}

	/**
	 * Returns the language flag or the language slug if there is no flag.
	 *
	 * @since 2.8
	 *
	 * @param PLL_Language $language PLL_Language object.
	 * @return string
	 */
	protected function get_flag_html( $language ) {
		return $language->flag ?: sprintf( '<abbr>%s</abbr>', esc_html( $language->slug ) );
	}

	/**
	 * Returns a link to edit an item (or an icon/flag if the current user is not allowed to).
	 *
	 * @since 3.8
	 *
	 * @param string       $url       URL of the edition link.
	 * @param int          $item_id   ID of the item.
	 * @param string       $item_name Name of the item.
	 * @param PLL_Language $language  Language of the item.
	 * @param string       $mode      Optional. How the link should be displayed: with a pen icon or a language's flag.
	 *                                Possible values are `icon` and `flag`. Default is `icon`.
	 * @return string
	 *
	 * @phpstan-param 'icon'|'flag' $mode
	 */
	private function get_item_edition_link( string $url, int $item_id, string $item_name, PLL_Language $language, string $mode = 'icon' ): string {
		if ( 'flag' === $mode ) {
			$flag  = $this->get_flag_html( $language );
			$class = 'pll_column_flag';
		} else {
			$flag  = '';
			$class = 'pll_icon_edit';
		}

		if ( empty( $url ) ) {
			// The current user is not allowed to edit the item.
			if ( 'flag' === $mode ) {
				/* translators: accessibility text, %s is a native language name */
				$hint = sprintf( __( 'You are not allowed to edit this item in %s', 'polylang' ), $language->name );
			} else {
				/* translators: accessibility text, %s is a native language name */
				$hint = sprintf( __( 'You are not allowed to edit a translation in %s', 'polylang' ), $language->name );
			}

			return sprintf(
				'<span title="%s" class="%s wp-ui-text-icon"><span class="screen-reader-text">%s</span>%s</span>',
				esc_attr( $hint ),
				esc_attr( $class ),
				esc_html( $hint ),
				$flag // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		} else {
			// The current user is allowed to edit the item.
			if ( 'flag' === $mode ) {
				/* translators: accessibility text, %s is a native language name */
				$hint = sprintf( __( 'Edit this item in %s', 'polylang' ), $language->name );
			} else {
				/* translators: accessibility text, %s is a native language name */
				$hint   = sprintf( __( 'Edit the translation in %s', 'polylang' ), $language->name );
				$class .= " translation_{$item_id}";
			}

			return sprintf(
				'<a href="%s" class="%s" title="%s"><span class="screen-reader-text">%s</span>%s</a>',
				esc_url( $url ),
				esc_attr( $class ),
				esc_attr( $item_name ),
				esc_html( $hint ),
				$flag // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}
	}
}
