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
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'maybe_enqueue_frontend_styles' ) );
	}

	/**
	 * Maybe enqueues CSS in frontend.
	 *
	 * @since 3.9
	 *
	 * @return void
	 */
	public static function maybe_enqueue_frontend_styles(): void {
		if ( self::has_classic_widget() ) {
			self::enqueue_frontend_styles();
		}
	}

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

	/**
	 * Tells if the site has an active Polylang classic widget.
	 * Note that this doesn't tell if the said widget will be actually shown, because we can't know if the sidebar it's
	 * in will be shown in the template.
	 *
	 * @since 3.9
	 *
	 * @return bool
	 */
	private static function has_classic_widget(): bool {
		global $wp_widget_factory;

		if ( ! get_theme_support( 'widgets' ) ) {
			return false;
		}

		if ( empty( $wp_widget_factory->get_widget_key( 'polylang' ) ) ) {
			// The widget has been unregistered.
			return false;
		}

		foreach ( wp_get_sidebars_widgets() as $sidebar => $widgets ) {
			if ( 'wp_inactive_widgets' === $sidebar || empty( $widgets ) ) {
				continue;
			}

			foreach ( $widgets as $widget ) {
				if ( is_string( $widget ) && preg_match( '/^polylang-\d+$/', $widget ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
