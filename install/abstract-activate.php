<?php
/**
 * @package Polylang
 *
 * /!\ THE CONSTANT `POLYLANG_BASENAME` MUST BE DEFINED.
 */

/**
 * A generic activation class compatible with multisite.
 *
 * @since 3.8
 */
abstract class PLL_Abstract_Activate extends PLL_Abstract_Activable {
	/**
	 * Adds the required hooks.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public static function add_hooks(): void {
		// Plugin activation.
		register_activation_hook( pll_get_constant( 'POLYLANG_BASENAME', '' ), array( static::class, 'do_for_all_blogs' ) );

		// Site creation on multisite.
		add_action( 'wp_initialize_site', array( static::class, 'new_site' ), 50 ); // After WP (prio 10).
	}

	/**
	 * Site creation on multisite (to set default options).
	 *
	 * @since 3.8
	 *
	 * @param WP_Site $new_site New site object.
	 * @return void
	 */
	public static function new_site( $new_site ): void {
		switch_to_blog( $new_site->id );
		static::process();
		restore_current_blog();
	}
}
