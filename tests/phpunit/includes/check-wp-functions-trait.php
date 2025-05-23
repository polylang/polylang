<?php

trait PLL_Check_WP_Functions_Trait {
	protected function compare_wp_version( $version ) {
		// Keep only the main part of the WP version (removing alpha, beta or rc).
		$parts = explode( '-', $GLOBALS['wp_version'] );
		$wp_version = $parts[0];

		if ( version_compare( $wp_version, $version, '<' ) ) {
			$this->markTestSkipped( "This test requires WordPress version {$version} or higher" );
		}
	}

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
		$this->compare_wp_version( $version );
		$this->assertSame( $md5, $this->md5( ...$args ), sprintf( 'The function %s() has been modified', implode( '::', $args ) ) );
	}

	/**
	 * Check if a Polylang function that is duplicated from a WordPress has been modified.
	 *
	 * @param string $md5 Expected method md5.
	 * @param string ...$args Function name or class and method name.
	 */
	protected function check_internal_method( $md5, ...$args ) {
		$this->assertSame( $md5, $this->md5( ...$args ), sprintf( 'The function %s() emulates a WordPress function, are you sure that it needs to be modified?', implode( '::', $args ) ) );
	}

	/**
	 * Checks if a file has been modified.
	 *
	 * @param string $md5      Expected method md5.
	 * @param string $version  Minimum WordPress function to pass the test.
	 * @param string $filepath Filepath of the file to check.
	 */
	protected function check_file( $md5, $version, $filepath ) {
		$this->compare_wp_version( $version );
		$this->assertFileIsReadable( $filepath, sprintf( 'The file %s is not readable', $filepath ) );
		$this->assertSame( $md5, md5( file_get_contents( $filepath ) ), sprintf( 'The file %s has been modified', $filepath ) );
	}
}
