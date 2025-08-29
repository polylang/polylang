<?php
/**
 * @package Polylang
 *
 * /!\ THE CONSTANT `POLYLANG_BASENAME` MUST BE DEFINED.
 */

/**
 * Deactivation class compatible with multisite.
 *
 * @since 3.8
 */
class PLL_Deactivate extends PLL_Abstract_Deactivate {
	/**
	 * The process to run on plugin deactivation.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	protected static function process(): void {
		delete_option( 'rewrite_rules' ); // Don't use flush_rewrite_rules at network activation. See #32471.
	}
}
