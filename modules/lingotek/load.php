<?php
/**
 * Loads the Lingotek ad.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
};

if ( ! defined( 'POLYLANG_PRO' ) && ( ! defined( 'PLL_LINGOTEK_AD' ) || PLL_LINGOTEK_AD ) && $polylang instanceof PLL_Admin_Base ) {
	add_action( 'wp_loaded', array( new PLL_Lingotek(), 'init' ) );
}
