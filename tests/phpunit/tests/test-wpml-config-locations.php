<?php

class WPML_Config_Locations_Test extends PLL_UnitTestCase {

	protected static $dirs = array(
		'themes/best-theme',
		'themes/best-child',
		'plugins/best-plugin',
	);

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		// Copy themes and plugins from tests/phpunit/data to wp-content.
		foreach ( self::$dirs as $path ) {
			$source_dir = PLL_TEST_DATA_DIR . $path;
			$dest_dir   = WP_CONTENT_DIR . "/{$path}";

			@mkdir( $dest_dir );

			foreach ( glob( $source_dir . '/*.*' ) as $file ) {
				copy( $file, $dest_dir . '/' . basename( $file ) );
			}
		}
	}

	public static function wpTearDownAfterClass() {
		parent::wpTearDownAfterClass();

		// Remove previously copied themes and plugins from wp-content.
		foreach ( self::$dirs as $path ) {
			$dest_dir = WP_CONTENT_DIR . "/{$path}";

			foreach ( glob( $dest_dir . '/*.*' ) as $file ) {
				unlink( $file );
			}

			rmdir( $dest_dir );
		}
	}

	public function test_in_polylang() {
		@mkdir( WP_CONTENT_DIR . '/polylang' );
		copy( PLL_TEST_DATA_DIR . 'wpml-config.xml', WP_CONTENT_DIR . '/polylang/wpml-config.xml' );

		$files    = ( new PLL_WPML_Config() )->get_files();
		$expected = array( 'Polylang' => WP_CONTENT_DIR . '/polylang/wpml-config.xml' );

		unlink( WP_CONTENT_DIR . '/polylang/wpml-config.xml' );
		rmdir( WP_CONTENT_DIR . '/polylang' );

		$this->assertSameSetsWithIndex( $expected, $files );
	}

	public function test_in_theme() {
		switch_theme( 'best-theme' );

		$files    = ( new PLL_WPML_Config() )->get_files();
		$expected = array( 'themes/best-theme' => get_theme_root() . '/best-theme/wpml-config.xml' );

		switch_theme( 'default' );

		$this->assertSameSetsWithIndex( $expected, $files );
	}

	public function test_in_child_theme() {
		switch_theme( 'best-child' );

		$files    = ( new PLL_WPML_Config() )->get_files();
		$expected = array(
			'themes/best-theme' => get_theme_root() . '/best-theme/wpml-config.xml',
			'themes/best-child' => get_theme_root() . '/best-child/wpml-config.xml',
		);

		switch_theme( 'default' );

		$this->assertSameSetsWithIndex( $expected, $files );
	}

	public function test_in_active_plugin() {
		activate_plugin( 'best-plugin/best-plugin.php' );
		$files    = ( new PLL_WPML_Config() )->get_files();
		$expected = array( 'plugins/best-plugin' => WP_PLUGIN_DIR . '/best-plugin/wpml-config.xml' );

		$this->assertSameSetsWithIndex( $expected, $files );
	}

	public function test_in_mu_plugin() {
		@mkdir( WPMU_PLUGIN_DIR );
		$filename_1 = WPMU_PLUGIN_DIR . '/wpml-config.xml';
		copy( PLL_TEST_DATA_DIR . 'wpml-config.xml', $filename_1 );

		@mkdir( WPMU_PLUGIN_DIR . '/must-use' );
		$filename_2 = WPMU_PLUGIN_DIR . '/must-use/wpml-config.xml';
		copy( PLL_TEST_DATA_DIR . 'wpml-config.xml', $filename_2 );

		@symlink( PLL_TEST_DATA_DIR . 'plugins/best-plugin', WPMU_PLUGIN_DIR . '/best-plugin' );

		$files    = array_map( 'wp_normalize_path', ( new PLL_WPML_Config() )->get_files() );
		$expected = array_map(
			'wp_normalize_path',
			array(
				'mu-plugins'             => $filename_1,
				'mu-plugins/must-use'    => $filename_2,
				'mu-plugins/best-plugin' => WPMU_PLUGIN_DIR . '/best-plugin/wpml-config.xml',
			)
		);

		if ( stripos( PHP_OS, 'WIN' ) === 0 ) {
			/*
			 * Uses rmdir() to remove symbolic link on Windows. See https://www.php.net/manual/fr/function.unlink.php
			 * Because symbolic link could not be created before if we don't have permissions, it needs to protect rmdir() call to prevent any error.
			 * And thus to be sure WPMU_PLUGIN_DIR will be removed at the end of this test.
			 */
			@rmdir( WPMU_PLUGIN_DIR . '/best-plugin' );
		} else {
			unlink( WPMU_PLUGIN_DIR . '/best-plugin' );
		}

		unlink( $filename_2 );
		rmdir( WPMU_PLUGIN_DIR . '/must-use' );

		unlink( $filename_1 );
		rmdir( WPMU_PLUGIN_DIR );

		$this->assertSameSetsWithIndex( $expected, $files );
	}
}
