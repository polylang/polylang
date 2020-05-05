<?php
/**
 * Polylang
 *
 * @package           Polylang
 * @author            WP SYNTEX
 * @license           GPL-3.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Polylang
 * Plugin URI:        https://polylang.pro
 * Description:       Adds multilingual capability to WordPress
 * Version:           2.8-dev
 * Requires at least: 4.9
 * Requires PHP:      5.6
 * Author:            WP SYNTEX
 * Author uri:        https://polylang.pro
 * Text Domain:       polylang
 * Domain Path:       /languages
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * Copyright 2011-2019 Frédéric Demarle
 * Copyright 2019-2020 WP SYNTEX
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // don't access directly
};

if ( defined( 'POLYLANG_VERSION' ) ) {
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
	define( 'POLYLANG_VERSION', '2.7.3' );
	define( 'PLL_MIN_WP_VERSION', '4.9' );
	define( 'PLL_MIN_PHP_VERSION', '5.6' );

	define( 'POLYLANG_FILE', __FILE__ ); // This file.
	define( 'POLYLANG_DIR', __DIR__ ); // Our directory.

	if ( ! defined( 'POLYLANG_BASENAME' ) ) {
		define( 'POLYLANG_BASENAME', plugin_basename( __FILE__ ) ); // Plugin name as known by WP.
	}

	define( 'POLYLANG', ucwords( str_replace( '-', ' ', dirname( POLYLANG_BASENAME ) ) ) );

	define( 'PLL_INC', POLYLANG_DIR . '/include' );
	define( 'PLL_SETTINGS_INC', POLYLANG_DIR . '/settings' );
	define( 'PLL_PREFIX', 'pll_' );

	require __DIR__ . '/vendor/autoload.php';
	require_once PLL_INC . '/class-polylang.php';
	new Polylang();
}
