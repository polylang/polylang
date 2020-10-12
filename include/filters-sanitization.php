<?php
/**
 * @package Polylang
 */

/**
 * Setup specific filters useful for sanitization.
 *
 * Extract from PLL_Admin_Filters to be able to use in a REST API context.
 *
 * @since 2.9
 */
class PLL_Filters_Sanitization {
	/**
	 * Language used for the sanitization depending on the context.
	 *
	 * @var string $locale
	 */
	public $locale;

	/**
	 * Constructor: setups filters and actions.
	 *
	 * @since 2.9
	 *
	 * @param string $locale Locale code of the language.
	 */
	public function __construct( $locale ) {
		$this->locale = $locale;

		// We need specific filters for some languages like German and Danish
		$specific_locales = array( 'da_DK', 'de_DE', 'de_DE_formal', 'de_CH', 'de_CH_informal', 'ca', 'sr_RS', 'bs_BA' );
		if ( in_array( $locale, $specific_locales ) ) {
			add_filter( 'sanitize_title', array( $this, 'sanitize_title' ), 10, 3 );
			add_filter( 'sanitize_user', array( $this, 'sanitize_user' ), 10, 3 );
		}
	}

	/**
	 * Retrieve the locale code of the language.
	 *
	 * @since 2.0
	 *
	 * @return string
	 */
	public function get_locale() {
		return $this->locale;
	}

	/**
	 * Maybe fix the result of sanitize_title() in case the languages include German or Danish
	 * Without this filter, if language of the title being sanitized is different from the language
	 * used for the admin interface and if one this language is German or Danish, some specific
	 * characters such as ä, ö, ü, ß are incorrectly sanitized.
	 *
	 * All the process is done by the remove_accents() WordPress function based on the locale value
	 *
	 * @link https://github.com/WordPress/WordPress/blob/5.5.1/wp-includes/formatting.php#L1920-L1944
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
	 * All the process is done by the remove_accents() WordPress function based on the locale value
	 *
	 * @link https://github.com/WordPress/WordPress/blob/5.5-branch/wp-includes/formatting.php#L1920-L1944
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
