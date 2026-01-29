<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Capabilities\Create;

use PLL_Language;
use WP_Syntex\Polylang\Capabilities\Capabilities;

/**
 * Class to manage the language context for posts creation or update.
 *
 * @since 3.8
 */
class Post extends Abstract_Object {
	/**
	 * Returns the language to set for a post creation or update.
	 *
	 * @since 3.8
	 *
	 * @param int $id The post ID for which to set the language. Default `0`.
	 * @return PLL_Language The language context.
	 */
	public function get_language( int $id = 0 ): PLL_Language {
		/** @var PLL_Language $default_language The default language is always defined. */
		$default_language = $this->model->get_default_language();
		$user             = Capabilities::get_user();

		if ( ! empty( $_GET['new_lang'] ) && $lang = $this->model->get_language( sanitize_key( $_GET['new_lang'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			// Defined only on admin.
			return $lang;
		}
		if ( ! isset( $this->pref_lang ) && ! empty( $_REQUEST['lang'] ) && $lang = $this->model->get_language( sanitize_key( $_REQUEST['lang'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			// Testing $this->pref_lang makes this test pass only on frontend.
			return $lang;
		}
		if ( $this->request && $lang = $this->request->get_language() ) {
			// REST request.
			return $lang;
		}
		if ( ( $parent_id = wp_get_post_parent_id( $id ) ) && $parent_lang = $this->model->post->get_language( $parent_id ) ) {
			// Use parent if exists.
			return $parent_lang;
		}
		if ( isset( $this->pref_lang ) && $user->can_translate( $this->pref_lang ) ) {
			// Always defined on admin, never defined on frontend.
			return $this->pref_lang;
		}
		if ( ! empty( $this->curlang ) ) {
			// Only on frontend due to the previous test always true on admin.
			return $this->curlang;
		}
		if ( $user->is_translator() ) {
			// Use default language if user can translate into it...
			if ( $user->can_translate( $default_language ) ) {
				return $default_language;
			}

			// ... or its preferred one.
			$preferred_language = $this->model->get_language( $user->get_preferred_language_slug() );
			if ( $preferred_language ) {
				return $preferred_language;
			}
		}

		// In all other cases use default language because we must have a language to set.
		return $default_language;
	}
}
