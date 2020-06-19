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
	$polylang->wizard = new PLL_Wizard( $polylang );
}
