<?php
/**
 * @package Polylang
 *
 * /!\ THE CONSTANTS `POLYLANG_BASENAME` AND `POLYLANG_VERSION` MUST BE DEFINED.
 */

use WP_Syntex\Polylang\Options\Options;
use WP_Syntex\Polylang\Options\Registry as Options_Registry;

/**
 * Activation class compatible with multisite.
 *
 * @since 3.8
 */
class PLL_Activate extends PLL_Abstract_Activate {
	/**
	 * Adds the required hooks.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public static function add_hooks(): void {
		register_activation_hook( pll_get_constant( 'POLYLANG_BASENAME', '' ), array( PLL_Wizard::class, 'start_wizard' ) );

		parent::add_hooks();
	}

	/**
	 * The process to run on plugin activation.
	 *
	 * @since 0.5
	 * @since 3.8 Moved from the class `PLL_Install`.
	 *            Renamed from `_activate()`.
	 *            Made it `static`.
	 *
	 * @return void
	 */
	protected static function process(): void {
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
		// Rewrite rules are created at next page load.
		delete_option( 'rewrite_rules' );
	}
}
