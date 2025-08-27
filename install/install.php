<?php
/**
 * @package Polylang
 *
 * /!\ THE CODE IN THIS FILE MUST BE COMPATIBLE WITH PHP 5.6.
 *
 * /!\ THE CONSTANTS `POLYLANG`, `POLYLANG_BASENAME`, `POLYLANG_VERSION`,
 *     `PLL_MIN_PHP_VERSION`, AND `PLL_MIN_WP_VERSION` MUST BE DEFINED.
 */

use WP_Syntex\Polylang\Options\Options;
use WP_Syntex\Polylang\Options\Registry as Options_Registry;

/**
 * Polylang activation/de-activation class.
 *
 * @since 1.7
 * @since 3.8 Reworked.
 */
class PLL_Install extends PLL_Install_Base {
	/**
	 * Checks min PHP and WP version, displays a notice if a requirement is not met.
	 *
	 * @since 2.6.7
	 * @since 3.8 Static method.
	 *
	 * @return bool
	 */
	public static function can_activate() {
		global $wp_version;

		if ( version_compare( pll_get_constant( 'PHP_VERSION', '' ), pll_get_constant( 'PLL_MIN_PHP_VERSION', '' ), '<' ) ) {
			add_action( 'admin_notices', array( static::class, 'php_version_notice' ) );
			return false;
		}

		if ( version_compare( $wp_version, pll_get_constant( 'PLL_MIN_WP_VERSION', '' ), '<' ) ) {
			add_action( 'admin_notices', array( static::class, 'wp_version_notice' ) );
			return false;
		}

		return true;
	}

	/**
	 * Displays a notice if PHP min version is not met.
	 *
	 * @since 2.6.7
	 * @since 3.8 Static method.
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
				esc_html( pll_get_constant( 'POLYLANG', '' ) ),
				esc_html( pll_get_constant( 'PHP_VERSION', '' ) ),
				esc_html( pll_get_constant( 'PLL_MIN_PHP_VERSION', '' ) )
			)
		);
	}

	/**
	 * Displays a notice if WP min version is not met.
	 *
	 * @since 2.6.7
	 * @since 3.8 Static method.
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
				esc_html( pll_get_constant( 'POLYLANG', '' ) ),
				esc_html( $wp_version ),
				esc_html( pll_get_constant( 'PLL_MIN_WP_VERSION', '' ) )
			)
		);
	}

	/**
	 * Adds the required hooks.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public static function add_hooks() {
		// register an action when plugin is activating.
		register_activation_hook( pll_get_constant( 'POLYLANG_BASENAME', '' ), array( 'PLL_Wizard', 'start_wizard' ) );

		parent::add_hooks();
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
		add_action( 'pll_init_options_for_blog', array( Options_Registry::class, 'register' ) );
		$options = new Options();

		if ( ! empty( $options['version'] ) ) {
			// Check if we will be able to upgrade.
			if ( version_compare( $options['version'], pll_get_constant( 'POLYLANG_VERSION', '' ), '<' ) ) {
				( new PLL_Upgrade( $options ) )->can_activate();
			}
		} else {
			$options['version'] = pll_get_constant( 'POLYLANG_VERSION', '' );
		}

		$options->save(); // Force save here to prevent any conflicts with another instance of `Options`.

		if ( false === get_option( 'pll_language_from_content_available' ) ) {
			update_option(
				'pll_language_from_content_available',
				0 === $options['force_lang'] ? 'yes' : 'no'
			);
		}

		// Avoid 1 query on every pages if no wpml strings is registered.
		if ( ! get_option( 'polylang_wpml_strings' ) ) {
			update_option( 'polylang_wpml_strings', array() );
		}

		// Don't use flush_rewrite_rules at network activation. See #32471.
		// Thanks to RavanH for the trick. See https://polylang.wordpress.com/2015/06/10/polylang-1-7-6-and-multisite/.
		// Rewrite rules are created at next page load :)
		delete_option( 'rewrite_rules' );
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
		delete_option( 'rewrite_rules' ); // Don't use flush_rewrite_rules at network activation. See #32471.
	}
}
