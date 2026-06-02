<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher;

use WP_Widget_Factory;

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
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_style( self::FRONTEND_ASSET_HANDLE, plugins_url( "/css/build/frontend-switcher{$suffix}.css", POLYLANG_FILE ), array(), POLYLANG_VERSION );
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
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( self::FRONTEND_ASSET_HANDLE, plugins_url( "/js/build/frontend-switcher{$suffix}.js", POLYLANG_FILE ), array(), POLYLANG_VERSION, true );
	}
}
