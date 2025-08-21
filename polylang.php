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
 * Version:           3.8-dev
 * Requires at least: 6.2
 * Requires PHP:      7.2
 * Author:            WP SYNTEX
 * Author URI:        https://polylang.pro
 * Text Domain:       polylang
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * Copyright 2011-2019 Frédéric Demarle
 * Copyright 2019-2025 WP SYNTEX
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

defined( 'ABSPATH' ) || exit;

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
	return;
}

// Stopping here if we are going to deactivate the plugin (avoids breaking rewrite rules).
if ( ! empty( $_GET['deactivate-polylang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
	return;
}

require_once __DIR__ . '/install/install-base.php';
require_once __DIR__ . '/install/install.php';
$install = new PLL_Install(
	__FILE__, // Plugin file.
	'3.8-dev', // Plugin version.
	'6.2', // WP version.
	'7.2' // PHP version.
);

if ( ! $install->is_deactivation() && $install->can_activate() ) {
	require_once __DIR__ . '/include/functions.php';

	pll_set_constant( 'POLYLANG', $install->plugin_name );
	pll_set_constant( 'POLYLANG_VERSION', $install->plugin_version );
	pll_set_constant( 'PLL_MIN_WP_VERSION', $install->min_wp_version );
	pll_set_constant( 'PLL_MIN_PHP_VERSION', $install->min_php_version );

	pll_set_constant( 'POLYLANG_FILE', __FILE__ );
	pll_set_constant( 'POLYLANG_DIR', __DIR__ );

	// Whether we are using Polylang or Polylang Pro, get the filename of the plugin in use.
	pll_maybe_set_constant( 'POLYLANG_ROOT_FILE', __FILE__ );

	if ( ! pll_has_constant( 'POLYLANG_BASENAME' ) ) {
		pll_set_constant( 'POLYLANG_BASENAME', $install->plugin_basename ); // Plugin name as known by WP.
		require __DIR__ . '/vendor/autoload.php';
	}

	$install::add_hooks();

	new Polylang();
}

unset( $install );
