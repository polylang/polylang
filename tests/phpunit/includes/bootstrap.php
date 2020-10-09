<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

// load the plugin however *no* Polylang instance is created as no languages exist in DB
function _manually_load_plugin() {
	require_once dirname( __FILE__ ) . '/../../../polylang.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require_once $_tests_dir . '/includes/bootstrap.php';

if ( ! defined( 'DIR_TESTROOT' ) ) {
	define( 'DIR_TESTROOT', $_tests_dir );
}

if ( ! defined( 'PLL_TEST_DATA_DIR' ) ) {
	define( 'PLL_TEST_DATA_DIR', dirname( __FILE__ ) . '/../data/' );
}
require_once __DIR__ . '/testcase-trait.php';
require_once __DIR__ . '/testcase.php';
require_once __DIR__ . '/testcase-ajax.php';
require_once __DIR__ . '/testcase-canonical.php';
require_once __DIR__ . '/testcase-domain.php';
require_once __DIR__ . '/wp-screen-mock.php';
require_once __DIR__ . '/check-wp-functions-trait.php';

printf(
	'Testing Polylang%1$s %2$s with WordPress %3$s...' . PHP_EOL,
	defined( 'POLYLANG_PRO' ) && POLYLANG_PRO ? ' Pro' : '',
	POLYLANG_VERSION,
	$GLOBALS['wp_version']
);
