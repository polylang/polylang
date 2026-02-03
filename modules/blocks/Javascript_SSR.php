<?php
/**
 * @package Polylang
 */

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
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param PLL_Language|null $current_language The current language.
	 */
	public function __construct( ?PLL_Language &$current_language = null ) {
		$this->current_language = &$current_language;
	}

	/**
	 * Initializes the JavaScript variable SSR.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'add_js_variable' ) );
	}

	/**
	 * Adds current language slug JavaScript variable to the block editor.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public function add_js_variable() {
		if ( ! $this->current_language ) {
			return;
		}

		$pll_settings_script = 'let pllEditorCurrentLanguageSlug = ' . wp_json_encode( $this->current_language->slug );

		wp_add_inline_script( 'pll_block-editor', $pll_settings_script, 'after' ); // Script handle matching the one in `admin-base.php~$scripts['block-editor']`
	}
}
