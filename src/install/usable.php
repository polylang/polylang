<?php
/**
 * @package Polylang
 *
 * /!\ THE CODE IN THIS FILE MUST BE COMPATIBLE WITH PHP 5.6.
 *
 * /!\ THE CONSTANTS `POLYLANG`, `PLL_MIN_PHP_VERSION`, AND `PLL_MIN_WP_VERSION` MUST BE DEFINED.
 */

/**
 * Class tat can tell if Polylang can be activated.
 *
 * @since 3.8
 */
class PLL_Usable {
	/**
	 * Checks min PHP and WP version, displays a notice if a requirement is not met.
	 *
	 * @since 2.6.7
	 * @since 3.8 Moved from the class `PLL_Install`.
	 *            Made it `static`.
	 *
	 * @return bool
	 */
	public static function can_activate() {
		global $wp_version;

		if ( version_compare( pll_get_constant( 'PHP_VERSION', '' ), static::get_min_php_version(), '<' ) ) {
			add_action( 'admin_notices', array( static::class, 'php_version_notice' ) );
			return false;
		}

		if ( version_compare( $wp_version, static::get_min_wp_version(), '<' ) ) {
			add_action( 'admin_notices', array( static::class, 'wp_version_notice' ) );
			return false;
		}

		return true;
	}

	/**
	 * Displays a notice if PHP min version is not met.
	 *
	 * @since 2.6.7
	 * @since 3.8 Moved from the class `PLL_Install`.
	 *            Made it `static`.
	 *
	 * @return void
	 */
	public static function php_version_notice() {
		load_plugin_textdomain( 'polylang' ); // Plugin i18n.

		printf(
			'<div class="error"><p>%s</p></div>',
			sprintf(
				/* translators: 1: Plugin name 2: Current PHP version 3: Required PHP version */
				esc_html__( '%1$s has deactivated itself because you are using an old version of PHP. You are using using PHP %2$s. %1$s requires PHP %3$s.', 'polylang' ),
				esc_html( static::get_plugin_name() ),
				esc_html( pll_get_constant( 'PHP_VERSION', '' ) ),
				esc_html( static::get_min_php_version() )
			)
		);
	}

	/**
	 * Displays a notice if WP min version is not met.
	 *
	 * @since 2.6.7
	 * @since 3.8 Moved from the class `PLL_Install`.
	 *            Made it `static`.
	 *
	 * @return void
	 */
	public static function wp_version_notice() {
		global $wp_version;

		load_plugin_textdomain( 'polylang' ); // Plugin i18n.

		printf(
			'<div class="error"><p>%s</p></div>',
			sprintf(
				/* translators: 1: Plugin name 2: Current WordPress version 3: Required WordPress version */
				esc_html__( '%1$s has deactivated itself because you are using an old version of WordPress. You are using using WordPress %2$s. %1$s requires at least WordPress %3$s.', 'polylang' ),
				esc_html( static::get_plugin_name() ),
				esc_html( $wp_version ),
				esc_html( static::get_min_wp_version() )
			)
		);
	}

	/**
	 * Returns the minimal php version required to run the plugin.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public static function get_min_php_version() {
		return pll_get_constant( 'PLL_MIN_PHP_VERSION', '' );
	}

	/**
	 * Returns the minimal WP version required to run the plugin.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public static function get_min_wp_version() {
		return pll_get_constant( 'PLL_MIN_WP_VERSION', '' );
	}

	/**
	 * Returns the plugin's name.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public static function get_plugin_name() {
		return pll_get_constant( 'POLYLANG', '' );
	}
}
