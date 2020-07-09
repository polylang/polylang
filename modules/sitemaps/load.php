<?php
/**
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

if ( $polylang->model->get_languages_list() ) {
	$polylang->sitemaps = new PLL_Sitemaps( $polylang );
	$polylang->sitemaps->init();
}
