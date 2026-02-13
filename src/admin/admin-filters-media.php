<?php
/**
 * @package Polylang
 */

use WP_Syntex\Polylang\Capabilities\Capabilities;

/**
 * Manages filters and actions related to media on admin side
 * Capability to edit / create media is checked before loading this class
 *
 * @since 1.2
 */
class PLL_Admin_Filters_Media extends PLL_Admin_Filters_Post_Base {
	/**
	 * @var PLL_CRUD_Posts|null
	 */
	public $posts;

	/**
	 * Constructor: setups filters and actions
	 *
	 * @since 1.2
	 *
	 * @param object $polylang The Polylang object.
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		$this->posts = &$polylang->posts;

		// Adds the language field and translations tables in the 'Edit Media' panel.
		add_filter( 'attachment_fields_to_edit', array( $this, 'attachment_fields_to_edit' ), 10, 2 );

		// Adds actions related to languages when creating, saving or deleting media.
		add_filter( 'attachment_fields_to_save', array( $this, 'save_media' ), 10, 2 );

		// Maybe creates a media translation.
		add_action( 'admin_init', array( $this, 'translate_media' ) );
	}

	/**
	 * Adds the language field and translations tables in the 'Edit Media' panel.
	 * Needs WP 3.5+
	 *
	 * @since 0.9
	 *
	 * @param array   $fields List of form fields.
	 * @param WP_Post $post   The attachment being edited.
	 * @return array Modified list of form fields.
	 */
	public function attachment_fields_to_edit( $fields, $post ) {
		if ( 'post.php' === $GLOBALS['pagenow'] ) {
			return $fields; // Don't add anything on edit media panel since we have the metabox.
		}

		$post_id   = $post->ID;
		$lang      = $this->model->post->get_language( $post_id );
		$user      = Capabilities::get_user();
		$languages = $this->model->languages->filter( 'translator' )->get_list();

		if ( empty( $lang ) ) {
			// The media has no language: prepend an empty option to prevent displaying a wrong information.
			array_unshift( $languages, (object) array( 'slug' => '', 'name' => '' ) );
		} elseif ( ! $user->can_translate( $lang ) ) {
			// The user cannot translate this language, so it isn't in `$languages` (the dropdown will be disabled).
			if ( ! in_array( $lang, $languages, true ) ) {
				array_unshift( $languages, $lang );
			}
		}

		// Disable the dropdown if:
		// - the media has no language and the user is a translator,
		// - or the media has a language but the user is not allowed to translate it.
		$disabled = empty( $lang ) ? $user->is_translator() : ! $user->can_translate( $lang );

		$dropdown = new PLL_Walker_Dropdown();
		$fields['language'] = array(
			'label' => __( 'Language', 'polylang' ),
			'input' => 'html',
			'html'  => $dropdown->walk(
				$languages,
				-1,
				array(
					'name'     => sprintf( 'attachments[%d][language]', $post_id ),
					'class'    => 'media_lang_choice',
					'selected' => $lang ? $lang->slug : '',
					'disabled' => $disabled,
				)
			),
		);

		return $fields;
	}

	/**
	 * Maybe creates a media translation.
	 *
	 * @since 0.9
	 *
	 * @return void
	 */
	public function translate_media() {
		$data = $this->links->get_data_from_new_media_translation_request();
		if ( empty( $data ) ) {
			return;
		}

		$post_id = $data['from_post']->ID;

		// Bails if the translations already exists.
		if ( $this->model->post->get_translation( $post_id, $data['new_lang'] ) ) {
			wp_safe_redirect( wp_get_referer() );
			exit;
		}

		$tr_id = $this->model->post->create_media_translation( $post_id, $data['new_lang'] );
		wp_safe_redirect( admin_url( sprintf( 'post.php?post=%d&action=edit', $tr_id ) ) ); // WP 3.5+.
		exit;
	}

	/**
	 * Called when a media is saved
	 * Saves language and translations
	 *
	 * @since 0.9
	 *
	 * @param array $post       An array of post data.
	 * @param array $attachment An array of attachment metadata.
	 * @return array Unmodified $post
	 */
	public function save_media( $post, $attachment ) {
		// Language is filled in attachment by the function applying the filter 'attachment_fields_to_save'
		// All security checks have been done by functions applying this filter
		if ( empty( $attachment['language'] ) || ! current_user_can( 'edit_post', $post['ID'] ) ) {
			return $post;
		}

		$language = $this->model->get_language( $attachment['language'] );

		if ( empty( $language ) ) {
			return $post;
		}

		Capabilities::get_user()->can_translate_or_die( $language );

		$this->model->post->set_language( $post['ID'], $language );

		return $post;
	}
}
