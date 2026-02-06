<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Blocks;

use PLL_Language;

/**
 * Manages the JavaScript variable SSR for the blocks.
 *
 * @since 3.8
 */
class Javascript_SSR {
	/**
	 * The current language.
	 *
	 * @var PLL_Language|null
	 */
	private $current_language;

	/**
	 * The default language.
	 *
	 * @var PLL_Language
	 */
	private $default_language;

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param PLL_Language      $default_language The default language.
	 * @param PLL_Language|null $current_language The current language.
	 */
	public function __construct( PLL_Language $default_language, ?PLL_Language &$current_language = null ) {
		$this->current_language = &$current_language;
		$this->default_language = $default_language;
	}

	/**
	 * Initializes the JavaScript variable SSR.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'render_js_variable' ) );
	}

	/**
	 * Adds current language slug JavaScript variable to the editors.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public function render_js_variable() {
		// Fallback to default language if current language is not set, usually happens in Site Editor.
		$current_language_slug = $this->current_language ? $this->current_language->slug : $this->default_language->slug;

		$pll_settings_script = 'let pllEditorCurrentLanguageSlug = ' . wp_json_encode( $current_language_slug );

		// Script handles matches the one for Polylang blocks.
		wp_add_inline_script( 'pll_blocks', $pll_settings_script, 'after' );
	}
}
