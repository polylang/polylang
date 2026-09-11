<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

use PLL_WPML_Config;
use ReflectionProperty;
use Brain\Monkey\Functions;
use WP_Debug_Data;
use WP_Site_Health;

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

	public function test_should_add_pll_warnings_without_overwriting_existing_entries() {
		$debug_info = array(
			'pre_existing_data' => array(
				'label'       => 'Title of this data',
				'description' => 'Description',
				'fields'      => array(
					'name' => array(
						'label' => 'Name',
						'value' => 'Field name',
					),
				),
			),
		);

		$result = $this->site_health->info( $debug_info );

		$this->assertArrayHasKey( 'pre_existing_data', $result, 'The pre-existing entry should still be present.' );
		$this->assertSame(
			$debug_info['pre_existing_data'],
			$result['pre_existing_data'],
			'The pre-existing entry should be left untouched by the Polylang filter.'
		);
		$this->assertArrayHasKey( 'pll_warnings', $result, 'The pll_warnings entry should be added.' );
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
			$this->assertArrayNotHasKey(
				'wpml',
				$debug_info['pll_warnings']['fields'] ?? array(),
				'Debug information should not contain a wpml entry when no wpml-config.xml file is found.'
			);
		} finally {
			$this->restore_wpml_config( $wpml_files_property );
		}
	}

	public function test_should_not_report_any_warning_field_when_nothing_to_report() {
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
				$this->assertArrayNotHasKey(
					'wpml',
					$debug_info['pll_warnings']['fields'] ?? array(),
					'Debug information should not contain a wpml entry when no wpml-config.xml file is found.'
				);
			$this->assertArrayNotHasKey(
				'simplexml',
				$debug_info['pll_warnings']['fields'] ?? array(),
				'Debug information should not contain a simplexml entry when no wpml-config.xml file is found.'
			);
		} finally {
			$this->restore_wpml_config( $wpml_files_property );
		}
	}

	public function test_should_not_add_network_activated_field_when_not_multisite() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'network_activated is always present on multisite.' );
		}

		$debug_info = $this->site_health->info( array() );

		$this->assertArrayNotHasKey( 'network_activated', $debug_info['pll_warnings']['fields'] ?? array(), 'Debug information should not contain an entry for multisite.' );
	}

	public function test_should_add_simplexml_to_modules_when_wpml_config_exists() {
		/** @var array|null $captured_modules */
		$captured_modules = null;

		add_filter(
			'site_status_test_php_modules',
			function ( $modules ) use ( &$captured_modules ) {
				$captured_modules = $modules;
				return $modules;
			},
			20 // After Polyland filter
		);

		$site_health = new WP_Site_Health();
		$site_health->get_test_php_extensions();

		$this->assertIsArray( $captured_modules, 'The filter callback should have captured the modules array.' );
		$this->assertArrayHasKey( 'simplexml', $captured_modules, 'The simplexml module should have been added to the list.' );
		$this->assertSame(
			array( 'extension' => 'simplexml', 'required' => true ),
			$captured_modules['simplexml'],
			'The simplexml module should be marked as required.'
		);
	}

	public function test_should_not_add_simplexml_to_modules_when_wpml_config_does_not_exists() {
		$wpml_files_property = $this->remove_wpml_config();
		/** @var array|null $modules_before */
		$modules_before = null;
		/** @var array|null $modules_after */
		$modules_after = null;

		add_filter(
			'site_status_test_php_modules',
			function ( $modules ) use ( &$modules_before ) {
				$modules_before = $modules;
				return $modules;
			},
			5 // Before Polyland filter
		);

		add_filter(
			'site_status_test_php_modules',
			function ( $modules ) use ( &$modules_after ) {
				$modules_after = $modules;
				return $modules;
			},
			20 // After Polylang's filter
		);

		$site_health = new WP_Site_Health();
		$site_health->get_test_php_extensions();

		try {
			$this->assertSame(
				$modules_before,
				$modules_after,
				'The modules list should be left untouched when no wpml-config.xml file is found.'
			);
		} finally {
			$this->restore_wpml_config( $wpml_files_property );
		}
	}

	public function test_should_add_simplexml_without_altering_other_modules() {
		/** @var array|null $modules_before */
		$modules_before = null;
		/** @var array|null $modules_after */
		$modules_after = null;

		add_filter(
			'site_status_test_php_modules',
			function ( $modules ) use ( &$modules_before ) {
				$modules_before = $modules;
				return $modules;
			},
			5 // Before Polyland filter
		);

		add_filter(
			'site_status_test_php_modules',
			function ( $modules ) use ( &$modules_after ) {
				$modules_after = $modules;
				return $modules;
			},
			20 // After Polylang's filter
		);

		$site_health = new WP_Site_Health();
		$site_health->get_test_php_extensions();


		$this->assertSame(
			array_diff_key( $modules_before, array( 'simplexml' => true ) ),
			array_diff_key( $modules_after, array( 'simplexml' => true ) ),
			'All other modules should be left untouched by the Polylang filter.'
		);
		$this->assertSame(
			array( 'extension' => 'simplexml', 'required' => true ),
			$modules_after['simplexml'],
			'The simplexml module should be marked as required.'
		);
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
