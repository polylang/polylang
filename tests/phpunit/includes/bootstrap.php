<?php
use function WP_Syntex\Polylang_Phpunit\getCliColors;

$_root_dir = dirname( __DIR__, 3 );
$_tests_dir = ! empty( getenv( 'WP_TESTS_DIR' ) ) ? getenv( 'WP_TESTS_DIR' ) : $_root_dir . '/tmp/wordpress-tests-lib';
require_once $_tests_dir . '/includes/functions.php';

// load the plugin however *no* Polylang instance is created as no languages exist in DB
tests_add_filter(
	'muplugins_loaded',
	function () use ( $_root_dir, $_tests_dir ) {
		// Kind of doing the job of `wp_register_plugin_realpath()` since the plugin is not in the plugins folder (not even as a symlink).
		$real_file_path   = realpath( $_root_dir . '/polylang.php' );
		$symlink_dir_path = wp_normalize_path( dirname( $_tests_dir ) . '/wordpress/wp-content/plugins/polylang' );

		$GLOBALS['wp_plugin_paths'][ $symlink_dir_path ] = wp_normalize_path( dirname( $real_file_path ) );

		// Tell WP where to find the themes.
		register_theme_directory( "{$_root_dir}/tmp/themes" );
		delete_site_transient( 'theme_roots' );

		require_once $real_file_path;
	}
);

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_root_dir . '/vendor/yoast/phpunit-polyfills/' );
require_once $_root_dir . '/vendor/antecedent/patchwork/Patchwork.php';
require_once $_tests_dir . '/includes/bootstrap.php';
require_once $_root_dir . '/vendor/wpsyntex/wp-phpunit/UnitTests/functions.php';

if ( ! defined( 'DIR_TESTROOT' ) ) {
	define( 'DIR_TESTROOT', $_tests_dir );
}

if ( ! defined( 'PLL_TEST_EXT_THEMES_DIR' ) ) {
	define( 'PLL_TEST_EXT_THEMES_DIR', "{$_root_dir}/tmp/themes/" );
}

if ( ! defined( 'PLL_TEST_EXT_PLUGINS_DIR' ) ) {
	define( 'PLL_TEST_EXT_PLUGINS_DIR', "{$_root_dir}/tmp/plugins/" );
}

if ( ! defined( 'PLL_TEST_WP_DIR' ) ) {
	define( 'PLL_TEST_WP_DIR', dirname( DIR_TESTROOT ) . '/wordpress/' );
}

if ( ! defined( 'PLL_TEST_THEMES_DIR' ) ) {
	define( 'PLL_TEST_THEMES_DIR', PLL_TEST_WP_DIR . 'wp-content/themes/' );
}

if ( ! defined( 'PLL_TEST_PLUGINS_DIR' ) ) {
	define( 'PLL_TEST_PLUGINS_DIR', PLL_TEST_WP_DIR . 'wp-content/plugins/' );
}

if ( ! defined( 'PLL_TEST_DATA_DIR' ) ) {
	define( 'PLL_TEST_DATA_DIR', dirname( __DIR__ ) . '/data/' );
}

$colors = getCliColors();

printf(
	"{$colors['info']}Testing Polylang%1\$s %2\$s with WordPress %3\$s...{$colors['no_color']}\n",
	defined( 'POLYLANG_PRO' ) && POLYLANG_PRO ? ' Pro' : '',
	POLYLANG_VERSION,
	$GLOBALS['wp_version']
);
