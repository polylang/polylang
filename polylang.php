<?php

/**
 * Polylang
 *
 * @author      Frédéric Demarle
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Polylang
 * Plugin URI: https://polylang.pro
 * Description: Adds multilingual capability to WordPress.
 * Version: 2.5
 * Author: Frédéric Demarle
 * Author URI: https://polylang.pro
 * License: GPL-2.0+
 * Text Domain: polylang
 * Domain Path: /languages
 */

/**
 * Copyright 2011-2018 Frédéric Demarle
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * ( at your option ) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // don't access directly
};

if ( defined( 'POLYLANG_BASENAME' ) ) {
	// The user is attempting to activate a second plugin instance, typically Polylang and Polylang Pro
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( defined( 'POLYLANG_PRO' ) ) {
		// Polylang Pro is already activated
		if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
			require_once ABSPATH . 'wp-includes/pluggable.php';
			deactivate_plugins( plugin_basename( __FILE__ ) ); // Deactivate this plugin
			// WP does not allow us to send a custom meaningful message, so just tell the plugin has been deactivated
			wp_safe_redirect( add_query_arg( 'deactivate', 'true', remove_query_arg( 'activate' ) ) );
			exit;
		}
	} else {
		// Polylang was activated, deactivate it to keep only what we expect to be Polylang Pro
		deactivate_plugins( POLYLANG_BASENAME );
	}
} else {
	// Go on loading the plugin
	define( 'POLYLANG_VERSION', '2.5' );
	define( 'PLL_MIN_WP_VERSION', '4.7' );

	define( 'POLYLANG_FILE', __FILE__ ); // this file
	define( 'POLYLANG_BASENAME', plugin_basename( POLYLANG_FILE ) ); // plugin name as known by WP
	define( 'POLYLANG_DIR', dirname( POLYLANG_FILE ) ); // our directory
	define( 'POLYLANG', ucwords( str_replace( '-', ' ', dirname( POLYLANG_BASENAME ) ) ) );

	define( 'PLL_ADMIN_INC', POLYLANG_DIR . '/admin' );
	define( 'PLL_FRONT_INC', POLYLANG_DIR . '/frontend' );
	define( 'PLL_INC', POLYLANG_DIR . '/include' );
	define( 'PLL_INSTALL_INC', POLYLANG_DIR . '/install' );
	define( 'PLL_MODULES_INC', POLYLANG_DIR . '/modules' );
	define( 'PLL_SETTINGS_INC', POLYLANG_DIR . '/settings' );

	require_once PLL_INC . '/class-polylang.php';

	if ( file_exists( PLL_INC . '/class-polylang-pro.php' ) ) {
		define( 'POLYLANG_PRO', true );
		require_once PLL_INC . '/class-polylang-pro.php';
	}
}
