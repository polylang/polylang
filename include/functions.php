<?php
/**
 * @package Polylang
 */

/**
 * Define wordpress.com VIP equivalent of uncached functions
 * WordPress backward compatibility functions
 * and miscellaneous utility functions
 */

if ( ! function_exists( 'wpcom_vip_get_page_by_title' ) ) {
	/**
	 * Retrieve a page given its title.
	 *
	 * @since 2.0
	 *
	 * @param string       $page_title Page title
	 * @param string       $output     Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N. Default OBJECT
	 * @param string|array $post_type  Optional. Post type or array of post types. Default 'page'.
	 * @return WP_Post|array|null WP_Post (or array) on success, or null on failure.
	 */
	function wpcom_vip_get_page_by_title( $page_title, $output = OBJECT, $post_type = 'page' ) {
		return get_page_by_title( $page_title, $output, $post_type ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_page_by_title_get_page_by_title
	}
}

if ( ! function_exists( 'wpcom_vip_get_page_by_path' ) ) {
	/**
	 * Retrieves a page given its path.
	 *
	 * @since 2.8.3
	 *
	 * @param string       $page_path Page path.
	 * @param string       $output    Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which correspond to
	 *                                a WP_Post object, an associative array, or a numeric array, respectively. Default OBJECT.
	 * @param string|array $post_type Optional. Post type or array of post types. Default 'page'.
	 * @return WP_Post|array|null WP_Post (or array) on success, or null on failure.
	 */
	function wpcom_vip_get_page_by_path( $page_path, $output = OBJECT, $post_type = 'page' ) {
		return get_page_by_path( $page_path, $output, $post_type ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_page_by_path_get_page_by_path
	}
}

/**
 * Determines whether we should load the cache compatibility
 *
 * @since 2.3.8
 *
 * @return bool True if the cache compatibility must be loaded
 */
function pll_is_cache_active() {
	/**
	 * Filters whether we should load the cache compatibility
	 *
	 * @since 2.3.8
	 *
	 * @bool $is_cache True if a known cache plugin is active
	 *                 incl. WP Fastest Cache which doesn't use WP_CACHE
	 */
	return apply_filters( 'pll_is_cache_active', ( defined( 'WP_CACHE' ) && WP_CACHE ) || defined( 'WPFC_MAIN_PATH' ) );
}

/**
 * Get the the current requested url
 *
 * @since 2.6
 *
 * @return string Requested url
 */
function pll_get_requested_url() {
	if ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
		return set_url_scheme( esc_url_raw( wp_unslash( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) ) );
	}

	/*
	 * In WP CLI context, few developers define superglobals in wp-config.php
	 * as proposed in https://make.wordpress.org/cli/handbook/common-issues/#php-notice-undefined-index-on-_server-superglobal
	 * So let's return the unfiltered home url to avoid a bunch of notices.
	 */
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return get_option( 'home' );
	}

	if ( WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions
		trigger_error( '$_SERVER[\'HTTP_HOST\'] or $_SERVER[\'REQUEST_URI\'] are required but not set.' );
	}

	return '';
}

/**
 * Determines whether we should load the block editor plugin or the legacy languages metabox.
 *
 * @since 2.6.0
 *
 * @return bool True to use the block editor plugin.
 */
function pll_use_block_editor_plugin() {
	/**
	 * Filters whether we should load the block editor plugin or the legacy languages metabox.
	 *
	 * @since 2.6.0
	 *
	 * @param bool $use_plugin True when loading the block editor plugin.
	 */
	return class_exists( 'PLL_Block_Editor_Plugin' ) && apply_filters( 'pll_use_block_editor_plugin', ! defined( 'PLL_USE_BLOCK_EDITOR_PLUGIN' ) || PLL_USE_BLOCK_EDITOR_PLUGIN );
}
