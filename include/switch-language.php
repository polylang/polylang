<?php
/**
 * @package Polylang
 */

/**
 * Class to handle site language switch.
 */
class PLL_Switch_Language {

	/**
	 * @var PLL_Model
	 */
	private static $model;

	/**
	 * The previous language.
	 *
	 * @var PLL_Language|null
	 */
	public static $previous_language;

	/**
	 * The original language.
	 *
	 * @var PLL_Language|null
	 */
	private static $original_language;

	/**
	 * The current language.
	 *
	 * @var PLL_Language|null
	 */
	private static $current_language;

	/**
	 * Setups filters.
	 *
	 * @since 3.7
	 *
	 * @param PLL_Model $model Instance of `PLL_Model`.
	 * @return void
	 */
	public static function init( PLL_Model $model ): void {
		self::$model = $model;

		add_action( 'pll_language_defined', array( static::class, 'set_current_language' ) );
	}

	/**
	 * Sets the current language.
	 *
	 * @since 3.7
	 *
	 * @param string $slug Current language slug.
	 * @return void
	 */
	public static function set_current_language( $slug ): void {
		$language = self::$model->languages->get( $slug );
		self::$current_language = ! empty( $language ) ? $language : null;
	}

	/**
	 * Switches to the given language.
	 * Hooked to `pll_post_synchronized` at first.
	 *
	 * @since 3.7
	 *
	 * @param int    $post_id ID of the source post.
	 * @param int    $tr_id   ID of the target post.
	 * @param string $lang    Language of the target post.
	 * @return void
	 */
	public static function on_post_synchronized( $post_id, $tr_id, $lang ): void {
		self::switch_language( $lang );
	}

	/**
	 * Switches the language back.
	 * Hooked to `pll_post_synchronized` at last.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	public static function after_post_synchronized(): void {
		self::$previous_language = self::$current_language;

		self::restore_original_language();
	}

	/**
	 * Switches the site to the given language.
	 *
	 * @since 3.7
	 *
	 * @param PLL_Language|string|null $language The language we want to switch to.
	 * @return void
	 */
	public static function switch_language( $language = null ): void {
		if ( null === $language ) {
			self::$current_language = null;
			return;
		}

		$language = self::$model->languages->get( $language );
		if ( ! $language instanceof PLL_Language ) {
			return;
		}

		if ( self::$current_language === $language ) {
			return;
		}

		if ( ! in_array( $language, self::$model->languages->get_list(), true ) ) {
			return;
		}

		// Stores the original language.
		self::$original_language = null === self::$original_language ? self::$current_language : self::$original_language;

		self::$current_language = $language;

		self::load_strings_translations( $language->slug );

		/**
		 * Fires when the language is switched.
		 *
		 * @since 3.7
		 *
		 * @param PLL_Language $language The new language.
		 */
		do_action( 'pll_switch_language', $language );
	}

	/**
	 * Restores the original language.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	public static function restore_original_language(): void {
		self::switch_language( self::$original_language );
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
	public static function load_strings_translations( $locale = '' ): void {
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
