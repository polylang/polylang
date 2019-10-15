<?php

/**
 * Manages filters and actions related to media on admin side
 * Capability to edit / create media is checked before loading this class
 *
 * @since 1.2
 */
class PLL_Admin_Filters_Media extends PLL_Admin_Filters_Post_Base {
	public $posts;

	/**
	 * Constructor: setups filters and actions
	 *
	 * @since 1.2
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		$this->posts = &$polylang->posts;

		// Adds the language field and translations tables in the 'Edit Media' panel
		add_filter( 'attachment_fields_to_edit', array( $this, 'attachment_fields_to_edit' ), 10, 2 );

		// Adds actions related to languages when creating, saving or deleting media
		add_filter( 'attachment_fields_to_save', array( $this, 'save_media' ), 10, 2 );

		// Creates a media translation
		if ( isset( $_GET['action'], $_GET['new_lang'], $_GET['from_media'] ) && 'translate_media' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			add_action( 'admin_init', array( $this, 'translate_media' ) );
		}
	}

	/**
	 * Adds the language field and translations tables in the 'Edit Media' panel
	 * Needs WP 3.5+
	 *
	 * @since 0.9
	 *
	 * @param array  $fields list of form fields
	 * @param object $post
	 * @return array modified list of form fields
	 */
	public function attachment_fields_to_edit( $fields, $post ) {
		if ( 'post.php' == $GLOBALS['pagenow'] ) {
			return $fields; // Don't add anything on edit media panel for WP 3.5+ since we have the metabox
		}

		$post_id = $post->ID;
		$lang = $this->model->post->get_language( $post_id );

		$dropdown = new PLL_Walker_Dropdown();
		$fields['language'] = array(
			'label' => __( 'Language', 'polylang' ),
			'input' => 'html',
			'html'  => $dropdown->walk(
				$this->model->get_languages_list(),
				-1,
				array(
					'name'     => sprintf( 'attachments[%d][language]', $post_id ),
					'class'    => 'media_lang_choice',
					'selected' => $lang ? $lang->slug : '',
				)
			),
		);

		return $fields;
	}

	/**
	 * Creates a media translation
	 *
	 * @since 0.9
	 */
	public function translate_media() {
		if ( isset( $_GET['from_media'], $_GET['new_lang'] ) ) {
			// Security check
			check_admin_referer( 'translate_media' );
			$post_id = (int) $_GET['from_media'];

			// Bails if the translations already exists
			// See https://wordpress.org/support/topic/edit-translation-in-media-attachments?#post-7322303
			// Or if the source media does not exist
			if ( $this->model->post->get_translation( $post_id, sanitize_key( $_GET['new_lang'] ) ) || ! get_post( $post_id ) ) {
				wp_safe_redirect( wp_get_referer() );
				exit;
			}

			$tr_id = $this->posts->create_media_translation( $post_id, sanitize_key( $_GET['new_lang'] ) );
			wp_safe_redirect( admin_url( sprintf( 'post.php?post=%d&action=edit', $tr_id ) ) ); // WP 3.5+
			exit;
		}
	}

	/**
	 * Called when a media is saved
	 * Saves language and translations
	 *
	 * @since 0.9
	 *
	 * @param array $post
	 * @param array $attachment
	 * @return array unmodified $post
	 */
	public function save_media( $post, $attachment ) {
		// Language is filled in attachment by the function applying the filter 'attachment_fields_to_save'
		// All security checks have been done by functions applying this filter
		if ( ! empty( $attachment['language'] ) ) {
			$this->model->post->set_language( $post['ID'], $attachment['language'] );
		}

		if ( isset( $_POST['media_tr_lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->save_translations( $post['ID'], array_map( 'absint', $_POST['media_tr_lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		return $post;
	}
}
