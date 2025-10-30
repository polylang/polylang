<?php
/**
 * @package Polylang
 */

use WP_Syntex\Polylang\Capabilities\User;

/**
 * Manages links related functions.
 *
 * @since 1.8
 */
class PLL_Admin_Links extends PLL_Links {
	/**
	 * Current user.
	 *
	 * @var User
	 */
	protected $user;

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param PLL_Base $polylang The Polylang object.
	 */
	public function __construct( PLL_Base &$polylang ) {
		parent::__construct( $polylang );
		$this->user = new User();
	}

	/**
	 * Returns the html markup for a new post translation link.
	 *
	 * @since 1.8
	 *
	 * @param int          $post_id  The source post ID.
	 * @param PLL_Language $language The language of the new translation.
	 * @return string
	 */
	public function new_post_translation_link( int $post_id, PLL_Language $language ): string {
		$link = $this->get_new_post_translation_link( $post_id, $language );
		return $this->new_translation_link( $link, $language );
	}

	/**
	 * Returns the URL to create a new post translation.
	 * Returns an empty string if the current user is not allowed to create posts in the given language.
	 *
	 * @since 1.5
	 *
	 * @param int          $post_id  The source post id.
	 * @param PLL_Language $language The language of the new translation.
	 * @param string       $context  Optional. Defaults to 'display' which encodes '&' to '&amp;'.
	 *                               Otherwise, preserves '&'.
	 * @return string
	 */
	public function get_new_post_translation_link( int $post_id, PLL_Language $language, string $context = 'display' ): string {
		if ( ! $this->user->can_translate( $language ) ) {
			return '';
		}

		$post_type = get_post_type( $post_id );

		if ( empty( $post_type ) ) {
			return '';
		}

		$post_type_object = get_post_type_object( $post_type );

		if ( empty( $post_type_object ) || ! $this->user->has_cap( $post_type_object->cap->create_posts ) ) {
			return '';
		}

		// Special case for the privacy policy page which is associated to a specific capability
		if ( 'page' === $post_type_object->name && ! $this->user->has_cap( 'manage_privacy_options' ) ) {
			$privacy_page = get_option( 'wp_page_for_privacy_policy' );
			$privacy_page = is_numeric( $privacy_page ) ? (int) $privacy_page : 0;

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
		 * Filters the new post translation link.
		 *
		 * @since 1.8
		 *
		 * @param string       $link     The new post translation link.
		 * @param PLL_Language $language The language of the new translation.
		 * @param int          $post_id  The source post id.
		 */
		return apply_filters( 'pll_get_new_post_translation_link', $link, $language, $post_id );
	}

	/**
	 * Returns the html markup for a new translation link.
	 *
	 * @since 2.6
	 *
	 * @param string       $link     The new translation link.
	 * @param PLL_Language $language The language of the new translation.
	 * @return string
	 */
	protected function new_translation_link( string $link, PLL_Language $language ): string {
		if ( empty( $link ) ) {
			return sprintf(
				'<span title="%s" class="pll_icon_add wp-ui-text-icon"></span>',
				/* translators: accessibility text, %s is a native language name */
				esc_attr( sprintf( __( 'You are not allowed to add a translation in %s', 'polylang' ), $language->name ) )
			);
		}

		/* translators: accessibility text, %s is a native language name */
		$hint = sprintf( __( 'Add a translation in %s', 'polylang' ), $language->name );
		return sprintf(
			'<a href="%1$s" title="%2$s" class="pll_icon_add"><span class="screen-reader-text">%3$s</span></a>',
			esc_url( $link ),
			esc_attr( $hint ),
			esc_html( $hint )
		);
	}

	/**
	 * Returns the html markup for a post translation link.
	 *
	 * @since 1.4
	 *
	 * @param int $post_id The translation post id.
	 * @return string
	 */
	public function edit_post_translation_link( int $post_id ): string {
		$language = $this->model->post->get_language( $post_id );

		if ( empty( $language ) ) {
			// Should not happen.
			return '';
		}

		$link = $this->get_edit_post_translation_link( $post_id, $language );
		return $this->edit_translation_link( $link, $language );
	}

	/**
	 * Returns the URL to edit a post.
	 * Returns an empty string if the current user is not allowed to edit the post.
	 *
	 * @since 3.8
	 *
	 * @param int          $post_id  The source post ID.
	 * @param PLL_Language $language The language of the post.
	 * @return string
	 */
	public function get_edit_post_translation_link( int $post_id, PLL_Language $language ): string {
		if ( ! $this->user->can_translate( $language ) ) {
			return '';
		}

		return (string) get_edit_post_link( $post_id );
	}

	/**
	 * Returns the html markup for a translation link.
	 *
	 * @since 2.6
	 * @since 3.8 Visibility changed from `public` to `protected`.
	 *
	 * @param string       $link     The translation link.
	 * @param PLL_Language $language The language of the translation.
	 * @return string
	 */
	protected function edit_translation_link( string $link, PLL_Language $language ): string {
		if ( empty( $link ) ) {
			return sprintf(
				'<span title="%s" class="pll_icon_edit wp-ui-text-icon"></span>',
				/* translators: accessibility text, %s is a native language name */
				esc_attr( sprintf( __( 'You are not allowed to edit a translation in %s', 'polylang' ), $language->name ) )
			);
		}

		/* translators: accessibility text, %s is a native language name */
		$hint = sprintf( __( 'Edit the translation in %s', 'polylang' ), $language->name );
		return sprintf(
			'<a href="%1$s" title="%2$s" class="pll_icon_edit"><span class="screen-reader-text">%3$s</span></a>',
			esc_url( $link ),
			esc_attr( $hint ),
			esc_html( $hint )
		);
	}

	/**
	 * Returns the html markup for a new term translation.
	 *
	 * @since 1.8
	 *
	 * @param int          $term_id   Source term ID.
	 * @param string       $taxonomy  Taxonomy name.
	 * @param string       $post_type Post type name.
	 * @param PLL_Language $language  The language of the new translation.
	 * @return string
	 */
	public function new_term_translation_link( int $term_id, string $taxonomy, string $post_type, PLL_Language $language ): string {
		$link = $this->get_new_term_translation_link( $term_id, $taxonomy, $post_type, $language );
		return $this->new_translation_link( $link, $language );
	}

	/**
	 * Returns the URL to create a new term translation.
	 * Returns an empty string if the current user is not allowed to create terms in the given language.
	 *
	 * @since 1.5
	 *
	 * @param int          $term_id   Source term id.
	 * @param string       $taxonomy  Taxonomy name.
	 * @param string       $post_type Post type name.
	 * @param PLL_Language $language  The language of the new translation.
	 * @return string
	 */
	public function get_new_term_translation_link( int $term_id, string $taxonomy, string $post_type, PLL_Language $language ): string {
		if ( ! $this->user->can_translate( $language ) ) {
			return '';
		}

		$tax = get_taxonomy( $taxonomy );
		if ( ! $tax || ! $this->user->has_cap( $tax->cap->edit_terms ) ) {
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
		 * Filters the new term translation link.
		 *
		 * @since 1.8
		 *
		 * @param string       $link      The new term translation link.
		 * @param PLL_Language $language  The language of the new translation.
		 * @param int          $term_id   The source term id.
		 * @param string       $taxonomy  Taxonomy name.
		 * @param string       $post_type Post type name.
		 */
		return apply_filters( 'pll_get_new_term_translation_link', $link, $language, $term_id, $taxonomy, $post_type );
	}

	/**
	 * Returns the html markup for a term translation link.
	 *
	 * @since 1.4
	 *
	 * @param int    $term_id   Translation term id.
	 * @param string $taxonomy  Taxonomy name.
	 * @param string $post_type Post type name.
	 * @return string
	 */
	public function edit_term_translation_link( int $term_id, string $taxonomy, string $post_type ): string {
		$language = $this->model->term->get_language( $term_id );

		if ( empty( $language ) ) {
			// Should not happen.
			return '';
		}

		$link = $this->get_edit_term_translation_link( $term_id, $taxonomy, $post_type, $language );
		return $this->edit_translation_link( $link, $language );
	}

	/**
	 * Returns the URL to edit a term.
	 * Returns an empty string if the current user is not allowed to edit the term.
	 *
	 * @since 3.8
	 *
	 * @param int          $term_id   Translation term ID.
	 * @param string       $taxonomy  Taxonomy name.
	 * @param string       $post_type Post type name.
	 * @param PLL_Language $language  The language of the term.
	 * @return string
	 */
	public function get_edit_term_translation_link( int $term_id, string $taxonomy, string $post_type, PLL_Language $language ): string {
		if ( ! $this->user->can_translate( $language ) ) {
			return '';
		}

		return (string) get_edit_term_link( $term_id, $taxonomy, $post_type );
	}

	/**
	 * Returns the html markup for a post edit link.
	 *
	 * @since 3.8
	 *
	 * @param WP_Post      $post     The post.
	 * @param PLL_Language $language The language of the post.
	 * @param string       $mode     Optional. How the link should be displayed: with a pen icon or a language's flag.
	 *                               Possible values are `icon` and `flag`. Default is `icon`.
	 * @return string
	 *
	 * @phpstan-param 'icon'|'flag' $mode
	 */
	public function get_edit_post_item_link_tag( WP_Post $post, PLL_Language $language, string $mode = 'icon' ): string {
		$url = $this->get_edit_post_translation_link( $post->ID, $language );
		return $this->get_edit_item_link( $url, $post->ID, $post->post_title, $language, $mode );
	}

	/**
	 * Returns the html markup for a term edit link.
	 *
	 * @since 3.8
	 *
	 * @param WP_Term      $term      The term.
	 * @param string       $taxonomy  Taxonomy name.
	 * @param string       $post_type Post type name.
	 * @param PLL_Language $language  The language of the term.
	 * @param string       $mode      Optional. How the link should be displayed: with a pen icon or a language's flag.
	 *                                Possible values are `icon` and `flag`. Default is `icon`.
	 * @return string
	 *
	 * @phpstan-param 'icon'|'flag' $mode
	 */
	public function get_edit_term_item_link_tag( WP_Term $term, string $taxonomy, string $post_type, PLL_Language $language, string $mode = 'icon' ): string {
		$url = $this->get_edit_term_translation_link( $term->term_id, $taxonomy, $post_type, $language );
		return $this->get_edit_item_link( $url, $term->term_id, $term->name, $language, $mode );
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
	public function get_edit_item_link( string $url, int $item_id, string $item_name, PLL_Language $language, string $mode = 'icon' ): string {
		if ( 'flag' === $mode ) {
			$flag  = $language->flag ?: sprintf( '<abbr>%s</abbr>', esc_html( $language->slug ) );
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
				'<span title="%s" class="%s wp-ui-text-icon">%s</span>',
				esc_attr( $hint ),
				esc_attr( $class ),
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

	/**
	 * Returns some data (`from_post` and `new_lang`) from the current request.
	 *
	 * @since 3.7
	 * @since 3.8 Removed parameter.
	 *
	 * @return array {
	 *     @type WP_Post      $from_post The source post.
	 *     @type PLL_Language $new_lang  The target language.
	 * }
	 *
	 * @phpstan-return array{}|array{from_post: WP_Post, new_lang: PLL_Language}|never
	 */
	public function get_data_from_new_post_translation_request(): array {
		if ( isset( $_GET['from_media'] ) ) {
			return $this->get_data_from_new_media_translation_request();
		}

		if ( ! isset( $GLOBALS['pagenow'], $GLOBALS['post_type'], $_GET['_wpnonce'], $_GET['from_post'], $_GET['new_lang'], $_GET['post_type'] ) ) {
			return array();
		}

		if ( 'post-new.php' !== $GLOBALS['pagenow'] || ! $this->model->is_translated_post_type( $GLOBALS['post_type'] ) ) {
			return array();
		}

		// Capability check already done in post-new.php.
		check_admin_referer( 'new-post-translation' );
		return $this->get_objects_from_new_post_translation_request( (int) $_GET['from_post'], sanitize_key( $_GET['new_lang'] ) );
	}

	/**
	 * Returns some data (`from_post` and `new_lang`) from the current request.
	 *
	 * @since 3.7
	 *
	 * @return array {
	 *     @type WP_Post      $from_post The source media.
	 *     @type PLL_Language $new_lang  The target language.
	 * }
	 *
	 * @phpstan-return array{}|array{from_post: WP_Post, new_lang: PLL_Language}|never
	 */
	public function get_data_from_new_media_translation_request(): array {
		if ( ! $this->options['media_support'] ) {
			return array();
		}

		if ( ! isset( $_GET['action'], $_GET['_wpnonce'], $_GET['from_media'], $_GET['new_lang'] ) || 'translate_media' !== $_GET['action'] ) {
			return array();
		}

		check_admin_referer( 'translate_media' );
		return $this->get_objects_from_new_post_translation_request( (int) $_GET['from_media'], sanitize_key( $_GET['new_lang'] ) );
	}

	/**
	 * Returns the objects given the post ID and language slug provided in the new post translation request.
	 *
	 * @since 3.7
	 *
	 * @param int    $post_id   The original Post ID provided.
	 * @param string $lang_slug The new translation language provided
	 * @return array {
	 *     @type WP_Post      $from_post The source post.
	 *     @type PLL_Language $new_lang  The target language.
	 * }
	 *
	 * @phpstan-return array{}|array{from_post: WP_Post, new_lang: PLL_Language}|never
	 */
	private function get_objects_from_new_post_translation_request( int $post_id, string $lang_slug ): array {
		if ( $post_id <= 0 || empty( $lang_slug ) ) {
			return array();
		}

		$post = get_post( $post_id );
		$lang = $this->model->get_language( $lang_slug );

		if ( empty( $post ) || empty( $lang ) ) {
			return array();
		}

		return array(
			'from_post' => $post,
			'new_lang'  => $lang,
		);
	}
}
