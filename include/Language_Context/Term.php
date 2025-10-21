<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Language_Context;

use PLL_Language;
use WP_Syntex\Polylang\Capabilities\User;

/**
 * Class to manage the language context for terms creation or update.
 *
 * @since 3.8
 */
class Term extends Abstract_Object {
	/**
	 * Returns the language to set for a post creation.
	 *
	 * @since 3.8
	 *
	 * @param User   $user     The user object.
	 * @param int    $id       The term ID for which to set the language. Default `0`.
	 * @param string $taxonomy The taxonomy for which to set the language. Default `''`.
	 * @return PLL_Language The language context.
	 */
	public function get_language( User $user, int $id = 0, string $taxonomy = '' ): PLL_Language {
		/** @var PLL_Language $default_language The default language is always defined. */
		$default_language = $this->model->get_default_language();
		$language         = null;
		if ( ! empty( $_GET['new_lang'] ) && $lang = $this->model->get_language( sanitize_key( $_GET['new_lang'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			// Defined only on admin.
			$language = $lang;
		} elseif ( ! empty( $_POST['term_lang_choice'] ) && is_string( $_POST['term_lang_choice'] ) && $lang = $this->model->get_language( sanitize_key( $_POST['term_lang_choice'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$language = $lang;
		} elseif ( ! empty( $_POST['inline_lang_choice'] ) && is_string( $_POST['inline_lang_choice'] ) && $lang = $this->model->get_language( sanitize_key( $_POST['inline_lang_choice'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$language = $lang;
		} elseif ( ! isset( $this->pref_lang ) && ! empty( $_REQUEST['lang'] ) && $lang = $this->model->get_language( sanitize_key( $_REQUEST['lang'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			// Testing $this->pref_lang makes this test pass only on frontend.
			$language = $lang;
		} elseif ( $this->request && $lang = $this->request->get_language() ) {
			// REST request.
			$language = $lang;
		} elseif ( ( $term = get_term( $id, $taxonomy ) ) && ! empty( $term->parent ) && $parent_lang = $this->model->term->get_language( $term->parent ) ) {
			// Sets language from term parent if exists thanks to Scott Kingsley Clark.
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
