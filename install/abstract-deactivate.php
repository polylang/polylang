<?php
/**
 * @package Polylang
 *
 * /!\ THE CONSTANT `POLYLANG_BASENAME` MUST BE DEFINED.
 */

/**
 * A generic deactivation class compatible with multisite.
 *
 * @since 3.8
 */
abstract class PLL_Abstract_Deactivate extends PLL_Abstract_Activable {
	/**
	 * Adds the required hooks.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public static function add_hooks(): void {
		register_deactivation_hook( pll_get_constant( 'POLYLANG_BASENAME', '' ), array( static::class, 'do_for_all_blogs' ) );
	}

	/**
	 * Detects plugin deactivation.
	 *
	 * @since 1.7
	 * @since 3.8 Moved from the class `PLL_Install_Base`.
	 *
	 * @return bool True if the plugin is currently being deactivated.
	 */
	public static function is_deactivation(): bool {
		return isset( $_GET['action'], $_GET['plugin'] ) && 'deactivate' === $_GET['action'] && pll_get_constant( 'POLYLANG_BASENAME', '' ) === $_GET['plugin']; // phpcs:ignore WordPress.Security.NonceVerification
	}
}
