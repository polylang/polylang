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

/**
 * Tells if a constant is defined.
 *
 * @since 3.5
 *
 * @param string $constant_name Name of the constant.
 * @return bool True if the constant is defined, false otherwise.
 *
 * @phpstan-param non-falsy-string $constant_name
 */
function pll_has_constant( string $constant_name ): bool {
	return defined( $constant_name ); // phpcs:ignore WordPressVIPMinimum.Constants.ConstantString.NotCheckingConstantName
}

/**
 * Returns the value of a constant if it is defined.
 *
 * @since 3.5
 *
 * @param string $constant_name Name of the constant.
 * @param mixed  $default       Optional. Value to return if the constant is not defined. Defaults to `null`.
 * @return mixed The value of the constant.
 *
 * @phpstan-param non-falsy-string $constant_name
 * @phpstan-param int|float|string|bool|array|null $default
 */
function pll_get_constant( string $constant_name, $default = null ) {
	if ( ! pll_has_constant( $constant_name ) ) {
		return $default;
	}

	return constant( $constant_name );
}

/**
 * Defines a constant if it is not already defined.
 *
 * @since 3.5
 *
 * @param string $constant_name Name of the constant.
 * @param mixed  $value         Value to set.
 * @return bool True on success, false on failure or already defined.
 *
 * @phpstan-param non-falsy-string $constant_name
 * @phpstan-param int|float|string|bool|array|null $value
 */
function pll_set_constant( string $constant_name, $value ): bool {
	if ( pll_has_constant( $constant_name ) ) {
		return false;
	}

	return define( $constant_name, $value ); // phpcs:ignore WordPressVIPMinimum.Constants.ConstantString.NotCheckingConstantName
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
