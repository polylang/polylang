<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher;

defined( 'ABSPATH' ) || exit;

/**
 * Class that manages CSS and JS dependencies.
 *
 * @since 3.9
 */
class Assets {
	public const FRONTEND_ASSET_HANDLE = 'pll-language-switcher';

	/**
	 * Enqueues frontend CSS.
	 *
	 * @since 3.9
	 *
	 * @return void
	 */
	public static function enqueue_frontend_styles(): void {
		pll_enqueue_style( 'language-switcher' );
	}

	/**
	 * Enqueues frontend JS.
	 * Should be called on-the-fly when needed.
	 *
	 * @since 3.9
	 *
	 * @return void
	 */
	public static function enqueue_frontend_scripts(): void {
		pll_enqueue_script( 'language-switcher', array(), array( 'in_footer' => true ) );
	}

	/**
	 * Registers frontend CSS.
	 *
	 * @since 3.9
	 *
	 * @return void
	 */
	public static function register_styles(): void {
		pll_register_style( 'language-switcher' );
	}

	/**
	 * Registers frontend JS.
	 *
	 * @since 3.9
	 *
	 * @return void
	 */
	public static function register_scripts(): void {
		pll_register_script( 'language-switcher', array(), array( 'in_footer' => true ) );
	}
}
