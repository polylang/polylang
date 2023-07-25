<?php

$_root_dir = dirname( dirname( dirname( __DIR__ ) ) );
$_tests_dir = ! empty( getenv( 'WP_TESTS_DIR' ) ) ? getenv( 'WP_TESTS_DIR' ) : $_root_dir . '/tmp/wordpress-tests-lib';
require_once $_tests_dir . '/includes/functions.php';

// load the plugin however *no* Polylang instance is created as no languages exist in DB
tests_add_filter(
	'muplugins_loaded',
	function() use ( $_root_dir ) {
		require_once $_root_dir . '/polylang.php';
	}
);

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_root_dir . '/vendor/yoast/phpunit-polyfills/' );
require_once $_tests_dir . '/includes/bootstrap.php';

if ( ! defined( 'PLL_CACHE_HOME_URL' ) ) {
	define( 'PLL_CACHE_HOME_URL', false );
}

if ( ! defined( 'DIR_TESTROOT' ) ) {
	define( 'DIR_TESTROOT', $_tests_dir );
}

if ( ! defined( 'PLL_TEST_DATA_DIR' ) ) {
	define( 'PLL_TEST_DATA_DIR', dirname( __DIR__ ) . '/data/' );
}

require_once __DIR__ . '/polyfills.php';

printf(
	'Testing Polylang%1$s %2$s with WordPress %3$s...' . PHP_EOL,
	defined( 'POLYLANG_PRO' ) && POLYLANG_PRO ? ' Pro' : '',
	POLYLANG_VERSION,
	$GLOBALS['wp_version']
);
