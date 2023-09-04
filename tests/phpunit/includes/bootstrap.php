<?php

$_root_dir = dirname( dirname( dirname( __DIR__ ) ) );
$_tests_dir = ! empty( getenv( 'WP_TESTS_DIR' ) ) ? getenv( 'WP_TESTS_DIR' ) : $_root_dir . '/tmp/wordpress-tests-lib';
require_once $_tests_dir . '/includes/functions.php';

// Temporary fix: prevent Patchwork to print `;\Patchwork\CodeManipulation\Stream::reinstateWrapper();` in CLI.
$db_file_path = $_root_dir . '/tmp/wordpress/wp-content/db.php';

if ( is_readable( $db_file_path ) && is_writable( $db_file_path ) ) {
	$db_file_content = file_get_contents( $db_file_path );

	if ( is_string( $db_file_content ) && trim( $db_file_content ) === '' ) {
		$fp = @fopen( $db_file_path, 'wb' );

		if ( $fp ) {
			fwrite( $fp, '<?php' );
			fclose( $fp );
		}
	}
}

// load the plugin however *no* Polylang instance is created as no languages exist in DB
tests_add_filter(
	'muplugins_loaded',
	function() use ( $_root_dir ) {
		require_once $_root_dir . '/polylang.php';
	}
);

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_root_dir . '/vendor/yoast/phpunit-polyfills/' );
require_once $_root_dir . '/vendor/antecedent/patchwork/Patchwork.php';
require_once $_tests_dir . '/includes/bootstrap.php';

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
