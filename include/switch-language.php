<?php
/**
 * @package Polylang
 */

/**
 * Class to handle site language switch.
 */
class PLL_Switch_Language {

	/**
	 * The original language.
	 *
	 * @var PLL_Language|null
	 */
	public static $original_language;

	/**
	 * Switches the site to the given language.
	 *
	 * @since 3.7
	 *
	 * @param PLL_Language|string $language The language we want to switch to.
	 * @return void
	 */
	public function switch_language( $language ) {
		$language = PLL()->model->get_language( $language );
		if ( ! $language instanceof PLL_Language ) {
			return;
		}

		if ( PLL()->curlang === $language ) {
			return;
		}

		if ( ! in_array( $language, PLL()->model->languages->get_list() ) ) {
			return;
		}

		// Stores the original language.
		$current_language        = empty( PLL()->curlang ) ? null : PLL()->curlang;
		self::$original_language = null === self::$original_language ? $current_language : self::$original_language;

		PLL()->curlang = $language;

		PLL()->load_strings_translations( $language->slug );

		/**
		 * Fires when the language is switched.
		 *
		 * @since 3.7
		 *
		 * @param PLL_Language $language The new language.
		 */
		do_action( 'pll_change_language', $language );
	}

	/**
	 * Restores the original language.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	public function restore_original_language() {
		PLL()->curlang = self::$original_language;
	}

	/**
	 * Loads user defined strings translations
	 *
	 * @since 1.2
	 * @since 2.1.3 $locale parameter added.
	 * @since 3.7   Moved from `PLL_Base`.
	 *
	 * @param string $locale Language locale or slug. Defaults to current locale.
	 * @return void
	 */
	public static function load_strings_translations( $locale = '' ) {
		if ( empty( $locale ) ) {
			$locale = ( is_admin() && ! Polylang::is_ajax_on_front() ) ? get_user_locale() : get_locale();
		}

		$language = self::$model->get_language( $locale );

		if ( ! empty( $language ) ) {
			$mo = new PLL_MO();
			$mo->import_from_db( $language );
			$GLOBALS['l10n']['pll_string'] = &$mo;
		} else {
			unset( $GLOBALS['l10n']['pll_string'] );
		}
	}
}
