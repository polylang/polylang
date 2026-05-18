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
	 * Adds hooks.
	 *
	 * @since 3.9
	 *
	 * @return self
	 */
	public function init(): self {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
		return $this;
	}

	/**
	 * Registers frontend CSS and JS.
	 *
	 * @since 3.9
	 *
	 * @return void
	 */
	public function register_frontend_assets(): void {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_style( self::FRONTEND_ASSET_HANDLE, plugins_url( "/css/build/frontend-switcher{$suffix}.css", POLYLANG_FILE ), array(), POLYLANG_VERSION );

		wp_register_script( self::FRONTEND_ASSET_HANDLE, plugins_url( "/js/build/frontend-switcher{$suffix}.js", POLYLANG_FILE ), array(), POLYLANG_VERSION, true );

		$i18n = array( 'openDropdown' => __( 'Open languages submenu', 'polylang' ), 'closeDropdown' => __( 'Close languages submenu', 'polylang' ) );
		wp_localize_script( self::FRONTEND_ASSET_HANDLE, 'pllSwitcherI18n', $i18n );
	}

	/**
	 * Enqueues frontend CSS.
	 *
	 * @since 3.9
	 *
	 * @return void
	 */
	public static function enqueue_frontend_styles(): void {
		wp_enqueue_style( self::FRONTEND_ASSET_HANDLE );
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
		wp_enqueue_script( self::FRONTEND_ASSET_HANDLE );
	}
}
