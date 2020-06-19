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

			if ( PLL() instanceof PLL_Frontend ) {
				add_filter( 'theme_mod_external_header_video', 'pll__' );
			} else {
				pll_register_string( __( 'Header video', 'polylang' ), get_theme_mod( 'external_header_video' ), 'Twenty Seventeen', false );
			}
		}
	}
}
