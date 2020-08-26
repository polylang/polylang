<?php
/**
 * Loads the site health.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

if ( $polylang instanceof PLL_Admin && $polylang->model->get_languages_list() ) {
	$polylang->site_health = new PLL_Admin_Site_Health( $polylang );
}
