<?php
/**
 * @package Polylang
 */

/**
 * Define wordpress.com VIP equivalent of uncached functions
 * WordPress backward compatibility functions
 * and miscellaneous utility functions
 */

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

if ( ! function_exists( 'sanitize_locale_name' ) ) {
	/**
	 * Strips out all characters not allowed in a locale code.
	 * Backward compatibility with WP < 6.2.1.
	 *
	 * @since 3.5
	 *
	 * @param string $locale_name The locale name to be sanitized.
	 * @return string The sanitized value.
	 */
	function sanitize_locale_name( $locale_name ) {
		// Limit to A-Z, a-z, 0-9, '_', '-'.
		$sanitized = (string) preg_replace( '/[^A-Za-z0-9_-]/', '', $locale_name );

		/**
		 * Filters a sanitized locale name string.
		 * Backward compatibility with WP < 6.2.1.
		 *
		 * @since 3.5
		 *
		 * @param string $sanitized   The sanitized locale name.
		 * @param string $locale_name The locale name before sanitization.
		 */
		return apply_filters( 'sanitize_locale_name', $sanitized, $locale_name );
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
		return set_url_scheme( sanitize_url( wp_unslash( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) ) );
	}

	/** @var string */
	$home_url = get_option( 'home' );

	/*
	 * In WP CLI context, few developers define superglobals in wp-config.php
	 * as proposed in https://make.wordpress.org/cli/handbook/common-issues/#php-notice-undefined-index-on-_server-superglobal
	 * So let's return the unfiltered home url to avoid a bunch of notices.
	 */
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return $home_url;
	}

	/*
	 * When using system CRON instead of WP_CRON, the superglobals are likely undefined.
	 */
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return $home_url;
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
	return class_exists( 'WP_Syntex\Polylang_Pro\Editors\Screens\Abstract_Screen' ) && apply_filters( 'pll_use_block_editor_plugin', ! defined( 'PLL_USE_BLOCK_EDITOR_PLUGIN' ) || PLL_USE_BLOCK_EDITOR_PLUGIN );
}

/**
 * Determines whether a plugin is active.
 *
 * We define our own function because `is_plugin_active()` is available only in the backend.
 *
 * @since 3.5
 *
 * @param string $plugin_name Plugin basename.
 * @return bool True if activated, false otherwise.
 */
function pll_is_plugin_active( string $plugin_name ) {
	$sitewide_plugins     = get_site_option( 'active_sitewide_plugins' );
	$sitewide_plugins     = ! empty( $sitewide_plugins ) && is_array( $sitewide_plugins ) ? array_keys( $sitewide_plugins ) : array();
	$current_site_plugins = (array) get_option( 'active_plugins', array() );
	$plugins              = array_merge( $sitewide_plugins, $current_site_plugins );

	return in_array( $plugin_name, $plugins );
}

/**
 * Prepares and registers notices.
 *
 * Wraps `add_settings_error()` to make its use more consistent.
 *
 * @since 3.6
 *
 * @param WP_Error $error Error object.
 * @return void
 */
function pll_add_notice( WP_Error $error ) {
	if ( ! $error->has_errors() ) {
		return;
	}

	foreach ( $error->get_error_codes() as $error_code ) {
		// Extract the "error" type.
		$data = $error->get_error_data( $error_code );
		$type = empty( $data ) || ! is_string( $data ) ? 'error' : $data;

		$message = wp_kses(
			implode( '<br>', $error->get_error_messages( $error_code ) ),
			array(
				'a'    => array( 'href' => true ),
				'br'   => array(),
				'code' => array(),
				'em'   => array(),
			)
		);

		add_settings_error( 'polylang', $error_code, $message, $type );
	}
}
