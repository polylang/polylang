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

		$this->assertArrayHasKey( 'simplexml', $debug_info['pll_warnings']['fields'], 'The pll_warnings entry should contain simplexml.' );
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
		unlink( WP_CONTENT_DIR . '/polylang/wpml-config.xml' );
		rmdir( WP_CONTENT_DIR . '/polylang' );

		// Reset the cached file list so PLL_WPML_Config::get_files() rescans the disk.
		$files_reflection = new ReflectionProperty( PLL_WPML_Config::class, 'files' );
		$files_reflection->setAccessible( true );
		$files_reflection->setValue( PLL_WPML_Config::instance(), null );

		$debug_info = $this->site_health->info( array() );

		try {
			$this->assertArrayNotHasKey( 'pll_warnings', $debug_info, 'Debug information should not contain an entry for pll_warnings.' );
		} finally {
			@mkdir( WP_CONTENT_DIR . '/polylang' );
			copy(
				PLL_TEST_DATA_DIR . 'wpml-config.xml',
				WP_CONTENT_DIR . '/polylang/wpml-config.xml'
			);

			// Reset the cache again so the next test sees the restored file, not the stale empty result.
			$files_reflection->setValue( PLL_WPML_Config::instance(), null );
		}
	}
}
