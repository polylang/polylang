<?php
/**
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

/*
 * Subdomains and multiple domains work out of the box.
 * We need to load our compatibility when one unique domain is used.
 */
if ( $polylang->options['force_lang'] < 2 && $polylang->model->get_languages_list() ) {
	$polylang->sitemaps = new PLL_Sitemaps( $polylang );
	$polylang->sitemaps->init();
}
