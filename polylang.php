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
 * Version:           3.4.4
 * Requires at least: 5.8
 * Requires PHP:      5.6
 * Author:            WP SYNTEX
 * Author URI:        https://polylang.pro
 * Text Domain:       polylang
 * Domain Path:       /languages
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * Copyright 2011-2019 Frédéric Demarle
 * Copyright 2019-2023 WP SYNTEX
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
	exit; // Don't access directly.
};

if ( defined( 'POLYLANG_VERSION' ) ) {
	// The user is attempting to activate a second plugin instance, typically Polylang and Polylang Pro.
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-includes/pluggable.php';
	if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) ); // Deactivate this plugin.
		// WP does not allow us to send a custom meaningful message, so just tell the plugin has been deactivated.
		wp_safe_redirect( add_query_arg( 'deactivate', 'true', remove_query_arg( 'activate' ) ) );
		exit;
	}
} else {
	// Go on loading the plugin
	define( 'POLYLANG_VERSION', '3.4.4' );
	define( 'PLL_MIN_WP_VERSION', '5.8' );
	define( 'PLL_MIN_PHP_VERSION', '5.6' );

	define( 'POLYLANG_FILE', __FILE__ );
	define( 'POLYLANG_DIR', __DIR__ );

	// Whether we are using Polylang or Polylang Pro, get the filename of the plugin in use.
	if ( ! defined( 'POLYLANG_ROOT_FILE' ) ) {
		define( 'POLYLANG_ROOT_FILE', __FILE__ );
	}

	if ( ! defined( 'POLYLANG_BASENAME' ) ) {
		define( 'POLYLANG_BASENAME', plugin_basename( __FILE__ ) ); // Plugin name as known by WP.
		require __DIR__ . '/vendor/autoload.php';
	}

	define( 'POLYLANG', ucwords( str_replace( '-', ' ', dirname( POLYLANG_BASENAME ) ) ) );

	if ( empty( $_GET['deactivate-polylang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		new Polylang();
	}
}
