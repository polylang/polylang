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
		add_action( 'admin_enqueue_scripts', array( self::class, 'maybe_enqueue_admin_styles' ) );
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
	 * Maybe enqueues CSS in admin.
	 *
	 * @since 3.9
	 *
	 * @return void
	 */
	public static function maybe_enqueue_admin_styles(): void {
		$screen = get_current_screen();

		if ( empty( $screen ) ) {
			return;
		}

		if ( 'widgets' === $screen->base || 'customize' === $screen->base ) {
			wp_enqueue_style( 'polylang_admin' );
			return;
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
		global $wp_widget_factory, $wp_registered_sidebars;

		if ( ! $wp_widget_factory instanceof WP_Widget_Factory || ! is_array( $wp_registered_sidebars ) ) {
			// If this happens, the site owner has bigger problems.
			return false;
		}

		$pll_widget_id = 'polylang';

		if ( empty( $wp_widget_factory->get_widget_key( $pll_widget_id ) ) ) {
			// The widget has been unregistered.
			return false;
		}

		// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		// Array( 'sidebar-1' => Array( 'search-2', 'polylang-3', 'polylang-2' ), 'sidebar-2' => Array( 'polylang-5' ) ).
		$active_widgets_by_sidebars = array_intersect_key( wp_get_sidebars_widgets(), $wp_registered_sidebars );

		if ( empty( $active_widgets_by_sidebars ) ) {
			// No widgets in the registered sidebars.
			return false;
		}

		// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		// Array( 'search-2', 'polylang-3', 'polylang-2', 'polylang-5' ).
		$active_widgets = array_merge( ...array_values( $active_widgets_by_sidebars ) );

		foreach ( $active_widgets as $widget ) {
			if ( is_string( $widget ) && preg_match( "/^{$pll_widget_id}-\d+$/", $widget ) ) {
				return true;
			}
		}

		return false;
	}
}
