<?php
/**
 * @package Polylang
 *
 * /!\ THE CONSTANTS `POLYLANG_BASENAME` AND `POLYLANG_VERSION` MUST BE DEFINED.
 */

/**
 * A generic (de)activation class compatible with multisite.
 *
 * @since 3.8
 */
abstract class PLL_Abstract_Activable {
	/**
	 * (De)Activation for all blogs.
	 *
	 * @since 1.2
	 * @since 3.8 Moved from the class `PLL_Install_Base`.
	 *            Visibility changed from `protected`.
	 *            Made it `static`.
	 *            Removed first parameter `$what`.
	 *
	 * @param bool $networkwide Whether the plugin is (de)activated for all sites in the network or just the current site.
	 * @return void
	 */
	public static function do_for_all_blogs( $networkwide ): void {
		if ( is_multisite() && $networkwide ) {
			// Network.
			foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $blog_id ) {
				switch_to_blog( $blog_id );
				static::process();
			}
			restore_current_blog();
		} else {
			// Single blog.
			static::process();
		}
	}

	/**
	 * Returns the plugin's basename.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public static function get_plugin_basename(): string {
		return pll_get_constant( 'POLYLANG_BASENAME', '' );
	}

	/**
	 * Returns the plugin's version.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public static function get_plugin_version(): string {
		return pll_get_constant( 'POLYLANG_VERSION', '' );
	}

	/**
	 * The process to run on plugin (de)activation.
	 *
	 * @since 0.5
	 * @since 3.8 Moved from the class `PLL_Install_Base`.
	 *            Renamed from `_activate()`/`_deactivate()`.
	 *            Made it `static` and `abstract`.
	 *
	 * @return void
	 */
	abstract protected static function process(): void;
}
