<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Capabilities\Create;

use PLL_Language;
use WP_Syntex\Polylang\Capabilities\User;

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
	 * @param User $user The user object.
	 * @param int  $id   The post ID for which to set the language. Default `0`.
	 * @return PLL_Language The language context.
	 */
	public function get_language( User $user, int $id = 0 ): PLL_Language {
		/** @var PLL_Language $default_language The default language is always defined. */
		$default_language = $this->model->get_default_language();
		$language         = null;
		if ( ! empty( $_GET['new_lang'] ) && $lang = $this->model->get_language( sanitize_key( $_GET['new_lang'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			// Defined only on admin.
			$language = $lang;
		} elseif ( ! isset( $this->pref_lang ) && ! empty( $_REQUEST['lang'] ) && $lang = $this->model->get_language( sanitize_key( $_REQUEST['lang'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			// Testing $this->pref_lang makes this test pass only on frontend.
			$language = $lang;
		} elseif ( $this->request && $lang = $this->request->get_language() ) {
			// REST request.
			$language = $lang;
		} elseif ( ( $parent_id = wp_get_post_parent_id( $id ) ) && $parent_lang = $this->model->post->get_language( $parent_id ) ) {
			// Use parent if exists.
			$language = $parent_lang;
		} elseif ( isset( $this->pref_lang ) && $user->can_translate( $this->pref_lang ) ) {
			// Always defined on admin, never defined on frontend.
			$language = $this->pref_lang;
		} elseif ( ! empty( $this->curlang ) ) {
			// Only on frontend due to the previous test always true on admin.
			$language = $this->curlang;
		} elseif ( $user->is_translator() ) {
			// Use default language if user can translate into it or its preferred one.
			$language = $user->can_translate( $default_language ) ? $default_language : $user->get_preferred_language( $this->model );
		}

		// In all other cases use default language because we must have a language to set.
		return $language ?? $default_language;
	}
}
