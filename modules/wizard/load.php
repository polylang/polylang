<?php
/**
 * Loads the setup wizard.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
};

if ( $polylang instanceof PLL_Admin_Base ) {
	$polylang->add_shared( 'wizard', PLL_Wizard::class )
		->withArgument( $polylang->model )
		->withArgument( $polylang->options );

	$polylang->get( 'wizard' )->init();
}
