<?php
/**
 * @package Polylang
 */

/**
 * Class to handle site language switch.
 */
class PLL_Switch_Language {

	/**
	 * Singleton instance.
	 *
	 * @var PLL_Switch_Language|null
	 */
	protected static $instance;

	/**
	 * The original language.
	 *
	 * @var PLL_Language|null
	 */
	private $original_language;

	/**
	 * Access to the single instance of the class.
	 *
	 * @since 3.7
	 *
	 * @return PLL_Switch_Language
	 */
	public static function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Switch the site to the given language.
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
		$this->original_language = null === $this->original_language ? $current_language : $this->original_language;

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
	 * Gets the original language.
	 *
	 * @since 3.7
	 *
	 * @return PLL_Language|null The language if there is one, false otherwise.
	 */
	public function get_original_language() {
		return $this->original_language;
	}

	/**
	 * Restores the original language.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	public function restore_current_language() {
		PLL()->curlang = $this->original_language;
	}
}
