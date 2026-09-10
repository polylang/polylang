<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

use PLL_WPML_Config;
use ReflectionProperty;
use Brain\Monkey\Functions;

class Warnings_Test extends TestCase {

	public function test_should_add_wpml_config_data_to_debug_information() {
		$debug_info = $this->site_health->info( array() );

		$this->assertArrayHasKey( 'pll_warnings', $debug_info, 'Debug information should contain an entry for pll_warnings.' );
		$this->assertArrayHasKey( 'wpml', $debug_info['pll_warnings']['fields'], 'Debug information should contain an entry for wpml.' );
		$this->assertSame(
			'wpml-config.xml files',
			$debug_info['pll_warnings']['fields']['wpml']['label'],
			'Pll_warnings entry should be added correctly.'
		);
		$this->assertSame(
			array( 'Polylang' => WP_CONTENT_DIR . '/polylang/wpml-config.xml' ),
			$debug_info['pll_warnings']['fields']['wpml']['value'],
			'The wpml entry should contain the expected wpml-config.xml file.'
		);
	}

	public function test_should_add_simplexml_warning_when_it_is_missing_with_wpml_config() {
		Functions\when( 'extension_loaded' )->alias(
			function ( $arg ) {
				if ( 'simplexml' === $arg ) {
					return false;
				}
				return true;
			}
		);
		$debug_info = $this->site_health->info( array() );

		$this->assertArrayHasKey( 'simplexml', $debug_info['pll_warnings']['fields'], 'Debug information entry should contain simplexml.' );
		$this->assertSame(
			'PHP SimpleXML extension',
			$debug_info['pll_warnings']['fields']['simplexml']['label'],
			'The pll_warnings entry should be added correctly.'
		);
		$this->assertSame(
			'Not loaded. Contact your host provider.',
			$debug_info['pll_warnings']['fields']['simplexml']['value'],
			'The simplexml entry should contain the expected warning message.'
		);
	}

	public function test_should_not_add_simplexml_warning_when_it_is_present_with_wpml_config() {
		$debug_info = $this->site_health->info( array() );

		$this->assertArrayNotHasKey( 'simplexml', $debug_info['pll_warnings']['fields'], 'Debug information entry should not contain simplexml.' );
	}

	public function test_should_not_add_wpml_data_to_debug_information_when_wpml_config_file_is_absent() {
		$wpml_files_property = $this->remove_wpml_config();

		$debug_info = $this->site_health->info( array() );

		try {
			$this->assertArrayNotHasKey( 'pll_warnings', $debug_info, 'Debug information should not contain an entry for pll_warnings.' );
		} finally {
			$this->restore_wpml_config( $wpml_files_property );
		}
	}

	public function test_should_not_add_pll_warnings_when_nothing_to_report() {
		$wpml_files_property = $this->remove_wpml_config();

		self::factory()->post->create(
			array(
				'post_type' => 'post',
				'lang'      => 'en',
			)
		);

		self::factory()->term->create(
			array(
				'taxonomy' => 'category',
				'lang'     => 'en',
			)
		);

		$debug_info = $this->site_health->info( array() );
		try {
				$this->assertArrayNotHasKey( 'pll_warnings', $debug_info, 'Debug information should not contain an entry for pll_warnings.' );
		} finally {
			$this->restore_wpml_config( $wpml_files_property );
		}
	}

	public function test_should_not_add_network_activated_field_when_not_multisite() {
		$debug_info = $this->site_health->info( array() );

		$this->assertArrayNotHasKey( 'network_activated', $debug_info['pll_warnings']['fields'] ?? array(), 'Debug information should not contain an entry for multisite.' );
	}

	/**
	 * Removes the wpml-config.xml file used by the test fixtures and resets
	 * `PLL_WPML_Config`'s internal file cache so it rescans the disk.
	 *
	 * @return ReflectionProperty Reflection on `PLL_WPML_Config::$files`, to be passed to `restore_wpml_config()`.
	 */
	private function remove_wpml_config() {
		unlink( WP_CONTENT_DIR . '/polylang/wpml-config.xml' );
		rmdir( WP_CONTENT_DIR . '/polylang' );

		// Reset the cached file list so PLL_WPML_Config::get_files() rescans the disk.
		$files_reflection = new ReflectionProperty( PLL_WPML_Config::class, 'files' );
		$files_reflection->setAccessible( true );
		$files_reflection->setValue( PLL_WPML_Config::instance(), null );
		return $files_reflection;
	}

	/**
	 * Restores the wpml-config.xml file previously removed by `remove_wpml_config()`
	 * and resets the cached file list so the next call to `PLL_WPML_Config::get_files()`
	 * sees the restored file instead of the stale empty result.
	 *
	 * @param ReflectionProperty $wpml_files_property Reflection on `PLL_WPML_Config::$files`, as returned by `remove_wpml_config()`.
	 * @return void
	 */
	private function restore_wpml_config( $wpml_files_property ) {
		@mkdir( WP_CONTENT_DIR . '/polylang' );
		copy(
			PLL_TEST_DATA_DIR . 'wpml-config.xml',
			WP_CONTENT_DIR . '/polylang/wpml-config.xml'
		);

		// Reset the cache again so the next test sees the restored file, not the stale empty result.
		$wpml_files_property->setValue( PLL_WPML_Config::instance(), null );
	}
}
