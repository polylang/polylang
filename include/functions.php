<?php

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
		return get_page_by_title( $page_title, $output, $post_type );
	}
}

if ( ! function_exists( 'wpcom_vip_get_category_by_slug' ) ) {
	/**
	 * Retrieve category object by category slug.
	 *
	 * @since 2.0
	 *
	 * @param string $slug The category slug.
	 * @return object Category data object
	 */
	function wpcom_vip_get_category_by_slug( $slug ) {
		return get_category_by_slug( $slug );
	}
}

if ( ! function_exists( 'wpcom_vip_get_term_by' ) ) {
	/**
	 * Get all Term data from database by Term field and data.
	 *
	 * @since 2.0
	 *
	 * @param string     $field    Either 'slug', 'name', 'id' (term_id), or 'term_taxonomy_id'
	 * @param string|int $value    Search for this term value
	 * @param string     $taxonomy Taxonomy name. Optional, if `$field` is 'term_taxonomy_id'.
	 * @param string     $output   Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N. Default OBJECT.
	 * @param string     $filter   Optional, default is raw or no WordPress defined filter will applied.
	 * @return WP_Term|array|false WP_Term instance (or array) on success. Will return false if `$taxonomy` does not exist or `$term` was not found.
	 */
	function wpcom_vip_get_term_by( $field, $value, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {
		return get_term_by( $field, $value, $taxonomy, $output, $filter );
	}
}

if ( ! function_exists( 'wpcom_vip_get_term_link' ) ) {
	/**
	 * Generate a permalink for a taxonomy term archive.
	 *
	 * @since 2.0
	 *
	 * @param object|int|string $term     The term object, ID, or slug whose link will be retrieved.
	 * @param string            $taxonomy Optional. Taxonomy. Default empty.
	 * @return string|WP_Error HTML link to taxonomy term archive on success, WP_Error if term does not exist.
	 */
	function wpcom_vip_get_term_link( $term, $taxonomy = '' ) {
		return get_term_link( $term, $taxonomy );
	}
}

if ( ! function_exists( 'wp_doing_ajax' ) ) {
	/**
	 * Determines whether the current request is a WordPress Ajax request.
	 * Backward compatibility function for WP < 4.7
	 *
	 * @since 2.2
	 *
	 * @return bool True if it's a WordPress Ajax request, false otherwise.
	 */
	function wp_doing_ajax() {
		/** This filter is documented in wp-includes/load.php */
		return apply_filters( 'wp_doing_ajax', defined( 'DOING_AJAX' ) && DOING_AJAX );
	}
}

if ( ! function_exists( 'wp_doing_cron' ) ) {
	/**
	 * Determines whether the current request is a WordPress cron request.
	 * Backward compatibility function for WP < 4.8
	 *
	 * @since 2.6
	 *
	 * @return bool True if it's a WordPress cron request, false otherwise.
	 */
	function wp_doing_cron() {
		/** This filter is documented in wp-includes/load.php */
		return apply_filters( 'wp_doing_cron', defined( 'DOING_CRON' ) && DOING_CRON );
	}
}

if ( ! function_exists( 'wp_using_themes' ) ) {
	/**
	 * Determines whether the current request should use themes.
	 * Backward compatibility function for WP < 5.1
	 *
	 * @since 2.6
	 *
	 * @return bool True if themes should be used, false otherwise.
	 */
	function wp_using_themes() {
		/** This filter is documented in wp-includes/load.php */
		return apply_filters( 'wp_using_themes', defined( 'WP_USE_THEMES' ) && WP_USE_THEMES );
	}
}

/**
 * Determines whether we should load the cache compatibility
 *
 * @since 2.3.8
 *
 * return bool True if the cache compatibility must be loaded
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
 * return bool True to use the block editor plugin.
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
