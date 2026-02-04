<?php
/**
 * @package Polylang
 */

/**
 * Manages the compatibility with Twenty_Seventeen.
 *
 * @since 2.8
 */
class PLL_Twenty_Seventeen {
	/**
	 * Translates the front page panels and the header video.
	 *
	 * @since 2.0.10
	 */
	public function init() {
		if ( 'twentyseventeen' === get_template() && did_action( 'pll_init' ) ) {
			if ( function_exists( 'twentyseventeen_panel_count' ) && PLL() instanceof PLL_Frontend ) {
				$num_sections = twentyseventeen_panel_count();
				for ( $i = 1; $i < ( 1 + $num_sections ); $i++ ) {
					add_filter( 'theme_mod_panel_' . $i, 'pll_get_post' );
				}
			}

			$theme_slug = get_option( 'stylesheet' ); // In case we are using a child theme.
			new PLL_Translate_Option( "theme_mods_$theme_slug", array( 'external_header_video' => 1 ), array( 'context' => 'Twenty Seventeen' ) );
		}
	}
}
