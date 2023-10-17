<?php

class WPML_Config_Locations_Test extends PLL_UnitTestCase {

	protected static $dirs = array(
		'themes' => array(
			'best-theme',
			'best-child',
		),
		'plugins' => array(
			'best-plugin',
		),
	);

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		// Copy themes and plugins from tests/phpunit/data to wp-content.
		foreach ( self::$dirs as $type => $subdirs ) {
			foreach ( $subdirs as $name ) {
				$source_dir = dirname( __DIR__ ) . "/data/{$type}/{$name}";
				$dest_dir   = WP_CONTENT_DIR . "/{$type}/{$name}";

				@mkdir( $dest_dir );

				foreach ( glob( $source_dir . '/*.*' ) as $file ) {
					copy( $file, $dest_dir . '/' . basename( $file ) );
				}
			}
		}
	}

	public static function wpTearDownAfterClass() {
		parent::wpTearDownAfterClass();

		// Remove previously copied themes and plugins from wp-content.
		foreach ( self::$dirs as $type => $subdirs ) {
			foreach ( $subdirs as $name ) {
				$dest_dir = WP_CONTENT_DIR . "/{$type}/{$name}";

				foreach ( glob( $dest_dir . '/*.*' ) as $file ) {
					unlink( $file );
				}

				rmdir( $dest_dir );
			}
		}
	}

	public function test_in_polylang() {
		@mkdir( WP_CONTENT_DIR . '/polylang' );
		copy( dirname( __DIR__ ) . '/data/wpml-config.xml', WP_CONTENT_DIR . '/polylang/wpml-config.xml' );

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

		$this->assertSameSetsWithIndex( $expected, $files );
	}

	public function test_in_child_theme() {
		switch_theme( 'best-child' );
		$files    = ( new PLL_WPML_Config() )->get_files();
		$expected = array(
			'themes/best-theme' => get_theme_root() . '/best-theme/wpml-config.xml',
			'themes/best-child' => get_theme_root() . '/best-child/wpml-config.xml',
		);

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
		$filename = WPMU_PLUGIN_DIR . '/wpml-config.xml';
		copy( dirname( __DIR__ ) . '/data/wpml-config.xml', $filename );

		$files    = ( new PLL_WPML_Config() )->get_files();
		$expected = array( 'mu-plugins' => $filename );

		unlink( $filename );
		rmdir( WPMU_PLUGIN_DIR );

		$this->assertSameSetsWithIndex( $expected, $files );
	}

	public function test_in_mu_plugin_sub_dir() {
		@mkdir( WPMU_PLUGIN_DIR );
		@mkdir( WPMU_PLUGIN_DIR . '/must-use' );
		$filename = WPMU_PLUGIN_DIR . '/must-use/wpml-config.xml';
		copy( dirname( __DIR__ ) . '/data/wpml-config.xml', $filename );

		$files    = ( new PLL_WPML_Config() )->get_files();
		$expected = array( 'mu-plugins/must-use' => $filename );

		unlink( $filename );
		rmdir( WPMU_PLUGIN_DIR . '/must-use' );
		rmdir( WPMU_PLUGIN_DIR );

		$this->assertSameSetsWithIndex( $expected, $files );
	}
}
