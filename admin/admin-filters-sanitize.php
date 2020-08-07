<?php
/**
 * @package Polylang
 */

/**
 * Setup specific admin filters usefull for sanitization.
 *
 * Extract from PLL_Admin_Filters to be able to use in a REST API context.
 *
 * @since 2.9
 */
class PLL_Admin_Filters_Sanitize extends PLL_Filters {

	/**
	 * Constructor: setups filters and actions
	 *
	 * @since 2.9
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		// To get the language from REST API Request
		add_filter( 'rest_pre_dispatch', array( $this, 'get_rest_query_params' ), 10, 3 );

		// We need specific filters for some languages like German and Danish
		$specific_locales = array( 'da_DK', 'de_DE', 'de_DE_formal', 'de_CH', 'de_CH_informal', 'ca', 'sr_RS', 'bs_BA' );
		if ( array_intersect( $this->model->get_languages_list( array( 'fields' => 'locale' ) ), $specific_locales ) ) {
			add_filter( 'sanitize_title', array( $this, 'sanitize_title' ), 10, 3 );
			add_filter( 'sanitize_user', array( $this, 'sanitize_user' ), 10, 3 );
		}
	}

	/**
	 * Get REST API parameters to set the current language correctly.
	 *
	 * @see WP_REST_Server::dispatch()
	 *
	 * @since 2.9
	 *
	 * @param mixed           $result  Response to replace the requested version with. Can be anything
	 *                                 a normal endpoint can return, or null to not hijack the request.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 */
	public function get_rest_query_params( $result, $server, $request ) {
		if ( current_user_can( 'edit_posts' ) && null !== $request->get_param( 'is_block_editor' ) ) {
			// When it's a post request on a new post the language is sent into the request.
			if ( ! empty( $request->get_param( 'lang' ) ) ) {
				$this->curlang = $this->model->get_language( sanitize_key( $request->get_param( 'lang' ) ) );
			} else {
				// Otherwise we need to get the language from the post itself.
				$this->curlang = $this->model->post->get_language( sanitize_key( $request->get_param( 'id' ) ) );
			}
		}
		return $result;
	}

	/**
	 * Filters the locale according to the current language instead of the language
	 * of the admin interface
	 *
	 * @since 2.0
	 *
	 * @param string $locale
	 * @return string
	 */
	public function get_locale( $locale ) {
		if ( isset( $_POST['post_lang_choice'] ) && $lang = $this->model->get_language( sanitize_key( $_POST['post_lang_choice'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$locale = $lang->locale;
		} elseif ( isset( $_POST['term_lang_choice'] ) && $lang = $this->model->get_language( sanitize_key( $_POST['term_lang_choice'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$locale = $lang->locale;
		} elseif ( isset( $_POST['inline_lang_choice'] ) && $lang = $this->model->get_language( sanitize_key( $_POST['inline_lang_choice'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$locale = $lang->locale;
		} elseif ( ! empty( $this->curlang ) ) {
			$locale = $this->curlang->locale;
		}

		return $locale;
	}

	/**
	 * Maybe fix the result of sanitize_title() in case the languages include German or Danish
	 * Without this filter, if language of the title being sanitized is different from the language
	 * used for the admin interface and if one this language is German or Danish, some specific
	 * characters such as ä, ö, ü, ß are incorrectly sanitized.
	 *
	 * @since 2.0
	 *
	 * @param string $title     Sanitized title.
	 * @param string $raw_title The title prior to sanitization.
	 * @param string $context   The context for which the title is being sanitized.
	 * @return string
	 */
	public function sanitize_title( $title, $raw_title, $context ) {
		static $once = false;

		if ( ! $once && 'save' == $context && ! empty( $title ) ) {
			$once = true;
			add_filter( 'locale', array( $this, 'get_locale' ), 20 ); // After the filter for the admin interface
			$title = sanitize_title( $raw_title, '', $context );
			remove_filter( 'locale', array( $this, 'get_locale' ), 20 );
			$once = false;
		}
		return $title;
	}

	/**
	 * Maybe fix the result of sanitize_user() in case the languages include German or Danish
	 *
	 * @since 2.0
	 *
	 * @param string $username     Sanitized username.
	 * @param string $raw_username The username prior to sanitization.
	 * @param bool   $strict       Whether to limit the sanitization to specific characters. Default false.
	 * @return string
	 */
	public function sanitize_user( $username, $raw_username, $strict ) {
		static $once = false;

		if ( ! $once ) {
			$once = true;
			add_filter( 'locale', array( $this, 'get_locale' ), 20 ); // After the filter for the admin interface
			$username = sanitize_user( $raw_username, '', $strict );
			remove_filter( 'locale', array( $this, 'get_locale' ), 20 );
			$once = false;
		}
		return $username;
	}
}
