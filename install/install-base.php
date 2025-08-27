<?php
/**
 * @package Polylang
 *
 * /!\ THE CODE IN THIS FILE MUST BE COMPATIBLE WITH PHP 5.6.
 *
 * /!\ THE CONSTANT `POLYLANG_BASENAME` MUST BE DEFINED.
 */

/**
 * A generic activation/de-activation class compatible with multisite.
 *
 * @since 1.7
 * @since 3.8 Abstract class, reworked.
 */
abstract class PLL_Install_Base {
	/**
	 * Allows to detect plugin deactivation.
	 *
	 * @since 1.7
	 * @since 3.8 Static method.
	 *
	 * @return bool True if the plugin is currently being deactivated.
	 */
	public static function is_deactivation() {
		return isset( $_GET['action'], $_GET['plugin'] ) && 'deactivate' === $_GET['action'] && pll_get_constant( 'POLYLANG_BASENAME', '' ) === $_GET['plugin']; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Adds the required hooks.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public static function add_hooks() {
		// Manages plugin activation and deactivation
		register_activation_hook( pll_get_constant( 'POLYLANG_BASENAME', '' ), array( static::class, 'activate' ) );
		register_deactivation_hook( pll_get_constant( 'POLYLANG_BASENAME', '' ), array( static::class, 'deactivate' ) );

		// Site creation on multisite.
		add_action( 'wp_initialize_site', array( static::class, 'new_site' ), 50 ); // After WP (prio 10).
	}

	/**
	 * Plugin activation for multisite.
	 *
	 * @since 1.7
	 * @since 3.8 Static method.
	 *
	 * @param bool $networkwide Whether the plugin is activated for all sites in the network or just the current site.
	 * @return void
	 */
	public static function activate( $networkwide ) {
		static::do_for_all_blogs( 'activate', (bool) $networkwide );
	}

	/**
	 * Plugin activation.
	 *
	 * @since 0.5
	 * @since 3.8 Static method.
	 *
	 * @return void
	 */
	protected static function _activate() {
		// Can be overridden in child class.
	}

	/**
	 * Plugin deactivation for multisite.
	 *
	 * @since 0.1
	 * @since 3.8 Static method.
	 *
	 * @param bool $networkwide Whether the plugin is deactivated for all sites in the network or just the current site.
	 * @return void
	 */
	public static function deactivate( $networkwide ) {
		static::do_for_all_blogs( 'deactivate', (bool) $networkwide );
	}

	/**
	 * Plugin deactivation.
	 *
	 * @since 0.5
	 * @since 3.8 Static method.
	 *
	 * @return void
	 */
	protected static function _deactivate() {
		// Can be overridden in child class.
	}

	/**
	 * Site creation on multisite (to set default options).
	 *
	 * @since 2.6.8
	 * @since 3.8 Static method.
	 *
	 * @param WP_Site $new_site New site object.
	 * @return void
	 */
	public static function new_site( $new_site ) {
		switch_to_blog( $new_site->id );
		static::_activate();
		restore_current_blog();
	}

	/**
	 * Activation or deactivation for all blogs.
	 *
	 * @since 1.2
	 * @since 3.8 Static method.
	 *
	 * @param string $what        Either 'activate' or 'deactivate'.
	 * @param bool   $networkwide Whether the plugin is (de)activated for all sites in the network or just the current site.
	 * @return void
	 */
	protected static function do_for_all_blogs( $what, $networkwide ) {
		if ( is_multisite() && $networkwide ) {
			// Network.
			foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $blog_id ) {
				switch_to_blog( $blog_id );
				'activate' === $what ? static::_activate() : static::_deactivate();
			}
			restore_current_blog();
		} else {
			// Single blog.
			'activate' === $what ? static::_activate() : static::_deactivate();
		}
	}
}
