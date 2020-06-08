<?php

class Copied_Functions_Test extends PHPUnit_Framework_TestCase {

	protected function md5( ...$args ) {
		if ( empty( $args[1] ) ) {
			// We got a function.
			$reflection = new ReflectionFunction( $args[0] );
		} else {
			$reflection = new ReflectionMethod( $args[0], $args[1] );
		}
		$filename = $reflection->getFileName();
		$start_line = $reflection->getStartLine() - 1; // It's actually - 1, otherwise you wont get the function() block.
		$end_line = $reflection->getEndLine();
		$length = $end_line - $start_line;

		$source = file( $filename );
		$body = implode( '', array_slice( $source, $start_line, $length ) );
		return md5( $body );
	}

	/**
	 * Checks if a WordPress function has been modified.
	 *
	 * @param string $md5     Expected method md5.
	 * @param string $version Minimum WordPress function to pass the test.
	 * @param string ...$args Function name or class and method name.
	 */
	protected function check_method( $md5, $version, ...$args ) {
		if ( version_compare( $GLOBALS['wp_version'], $version, '<' ) ) {
			$this->markTestSkipped( "This test requires WordPress version {$version} or higher" );
		}
		$this->assertEquals( $md5, $this->md5( ...$args ), sprintf( 'The function %s() has been modified', implode( '::', $args ) ) );
	}

	public function test_calendar_widget() {
		$this->check_method( 'f75a2c70d28b1d2c4e3e8fd86a8bb7d3', '5.4', 'WP_Widget_Calendar', 'widget' );
	}

	public function test_get_calendar() {
		$this->check_method( '4cb06a3a390e2feaa9d32761d1f3fd00', '5.4', 'get_calendar' );
	}

	public function test_wp_admin_bar() {
		$this->check_method( '0104b0cde635904909a91ab3dafd5129', '5.4', 'wp_admin_bar_search_menu' );
	}
}
