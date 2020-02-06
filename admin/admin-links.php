<?php

/**
 * Manages links related functions
 *
 * @since 1.8
 */
class PLL_Admin_Links extends PLL_Links {

	/**
	 * Returns html markup for a new translation link
	 *
	 * @since 2.6
	 *
	 * @param string $link     The new translation link.
	 * @param object $language The language of the new translation.
	 * @return string
	 */
	protected function new_translation_link( $link, $language ) {
		$str = '';

		if ( $link ) {
			/* translators: accessibility text, %s is a native language name */
			$hint = sprintf( __( 'Add a translation in %s', 'polylang' ), $language->name );

			$str = sprintf(
				'<a href="%1$s" title="%2$s" class="pll_icon_add"><span class="screen-reader-text">%3$s</span></a>',
				esc_url( $link ),
				esc_attr( $hint ),
				esc_html( $hint )
			);
		}

		return $str;
	}

	/**
	 * Returns html markup for a translation link
	 *
	 * @since 2.6
	 *
	 * @param string $link     The translation link.
	 * @param object $language The language of the translation.
	 * @return string
	 */
	public function edit_translation_link( $link, $language ) {
		return $link ? sprintf(
			'<a href="%1$s" class="pll_icon_edit"><span class="screen-reader-text">%2$s</span></a>',
			esc_url( $link ),
			/* translators: accessibility text, %s is a native language name */
			esc_html( sprintf( __( 'Edit the translation in %s', 'polylang' ), $language->name ) )
		) : '';
	}

	/**
	 * Get the link to create a new post translation
	 *
	 * @since 1.5
	 *
	 * @param int    $post_id  The source post id.
	 * @param object $language The language of the new translation.
	 * @param string $context  Optional. Defaults to 'display' which encodes '&' to '&amp;'.
	 *                         Otherwise, preserves '&'.
	 * @return string
	 */
	public function get_new_post_translation_link( $post_id, $language, $context = 'display' ) {
		$post_type = get_post_type( $post_id );
		$post_type_object = get_post_type_object( get_post_type( $post_id ) );
		if ( ! current_user_can( $post_type_object->cap->create_posts ) ) {
			return '';
		}

		// Special case for the privacy policy page which is associated to a specific capability
		if ( 'page' === $post_type_object->name && ! current_user_can( 'manage_privacy_options' ) ) {
			$privacy_page = get_option( 'wp_page_for_privacy_policy' );
			if ( $privacy_page && in_array( $post_id, $this->model->post->get_translations( $privacy_page ) ) ) {
				return '';
			}
		}

		if ( 'attachment' === $post_type ) {
			$args = array(
				'action'     => 'translate_media',
				'from_media' => $post_id,
				'new_lang'   => $language->slug,
			);

			$link = add_query_arg( $args, admin_url( 'admin.php' ) );

			// Add nonce for media as we will directly publish a new attachment from a click on this link
			if ( 'display' === $context ) {
				$link = wp_nonce_url( $link, 'translate_media' );
			} else {
				$link = add_query_arg( '_wpnonce', wp_create_nonce( 'translate_media' ), $link );
			}
		} else {
			$args = array(
				'post_type' => $post_type,
				'from_post' => $post_id,
				'new_lang'  => $language->slug,
			);

			$link = add_query_arg( $args, admin_url( 'post-new.php' ) );

			if ( 'display' === $context ) {
				$link = wp_nonce_url( $link, 'new-post-translation' );
			} else {
				$link = add_query_arg( '_wpnonce', wp_create_nonce( 'new-post-translation' ), $link );
			}
		}

		/**
		 * Filter the new post translation link
		 *
		 * @since 1.8
		 *
		 * @param string $link     the new post translation link
		 * @param object $language the language of the new translation
		 * @param int    $post_id  the source post id
		 */
		return apply_filters( 'pll_get_new_post_translation_link', $link, $language, $post_id );
	}

	/**
	 * Returns html markup for a new post translation link
	 *
	 * @since 1.8
	 *
	 * @param int    $post_id
	 * @param object $language
	 * @return string
	 */
	public function new_post_translation_link( $post_id, $language ) {
		$link = $this->get_new_post_translation_link( $post_id, $language );
		return $this->new_translation_link( $link, $language );
	}

	/**
	 * Returns html markup for a translation link
	 *
	 * @since 1.4
	 *
	 * @param int $post_id translation post id
	 * @return string
	 */
	public function edit_post_translation_link( $post_id ) {
		$link = get_edit_post_link( $post_id );
		$language = $this->model->post->get_language( $post_id );
		return $this->edit_translation_link( $link, $language );
	}

	/**
	 * Get the link to create a new term translation
	 *
	 * @since 1.5
	 *
	 * @param int    $term_id
	 * @param string $taxonomy
	 * @param string $post_type
	 * @param object $language
	 * @return string
	 */
	public function get_new_term_translation_link( $term_id, $taxonomy, $post_type, $language ) {
		$tax = get_taxonomy( $taxonomy );
		if ( ! $tax || ! current_user_can( $tax->cap->edit_terms ) ) {
			return '';
		}

		$args = array(
			'taxonomy'  => $taxonomy,
			'post_type' => $post_type,
			'from_tag'  => $term_id,
			'new_lang'  => $language->slug,
		);

		$link = add_query_arg( $args, admin_url( 'edit-tags.php' ) );

		/**
		 * Filter the new term translation link
		 *
		 * @since 1.8
		 *
		 * @param string $link      the new term translation link
		 * @param object $language  the language of the new translation
		 * @param int    $term_id   the source term id
		 * @param string $taxonomy
		 * @param string $post_type
		 */
		return apply_filters( 'pll_get_new_term_translation_link', $link, $language, $term_id, $taxonomy, $post_type );
	}

	/**
	 * Returns html markup for a new term translation
	 *
	 * @since 1.8
	 *
	 * @param int    $term_id
	 * @param string $taxonomy
	 * @param string $post_type
	 * @param object $language
	 * @return string
	 */
	public function new_term_translation_link( $term_id, $taxonomy, $post_type, $language ) {
		$link = $this->get_new_term_translation_link( $term_id, $taxonomy, $post_type, $language );
		return $this->new_translation_link( $link, $language );
	}

	/**
	 * Returns html markup for a term translation link
	 *
	 * @since 1.4
	 *
	 * @param object $term_id   translation term id
	 * @param string $taxonomy
	 * @param string $post_type
	 * @return string
	 */
	public function edit_term_translation_link( $term_id, $taxonomy, $post_type ) {
		$link = get_edit_term_link( $term_id, $taxonomy, $post_type );
		$language = $this->model->term->get_language( $term_id );
		return $this->edit_translation_link( $link, $language );
	}
}
