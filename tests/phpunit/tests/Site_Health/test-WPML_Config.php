<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

use WP_Debug_Data;
use WP_Site_Health;
use PLL_WPML_Config;
use ReflectionProperty;

class WPML_Config_Test extends TestCase {
	public function tear_down() {
		$this->remove_wpml_config();

		parent::tear_down();
	}

	public function test_should_add_wpml_config_data_to_debug_information() {
		$this->create_wpml_config();

		$debug_info = $this->site_health->info( array() );

		$this->assertArrayHasKey( 'pll_warnings', $debug_info, 'Debug information should contain an entry for pll_warnings.' );
		$this->assertArrayHasKey( 'wpml', $debug_info['pll_warnings']['fields'], 'Debug information should contain an entry for wpml-config.xml files.' );
		$this->assertSame(
			'wpml-config.xml files',
			$debug_info['pll_warnings']['fields']['wpml']['label'],
			'Pll_warnings entry should be added correctly.'
		);
		$this->assertSame(
			array( 'Polylang' => WP_CONTENT_DIR . '/polylang/wpml-config.xml' ),
			$debug_info['pll_warnings']['fields']['wpml']['value'],
			'The wpml-config.xml entry should contain the expected file.'
		);
	}

	public function test_should_add_pll_warnings_without_overwriting_existing_entries() {
		$this->create_wpml_config();

		$pre_existing_data = array(
			'label'       => 'Title of this data',
			'description' => 'Description',
			'fields'      => array(
				'name' => array(
					'label' => 'Name',
					'value' => 'Field name',
				),
			),
		);
		add_filter(
			'debug_information',
			function ( $debug_info ) use ( $pre_existing_data ) {
				$debug_info['pre_existing_data'] = $pre_existing_data;

				return $debug_info;
			}
		);

		$debug_info = WP_Debug_Data::debug_data();

		$this->assertSame(
			$pre_existing_data,
			$debug_info['pre_existing_data'],
			'The pre-existing entry should be left untouched by the Polylang filter.'
		);
		$this->assertArrayHasKey( 'pll_warnings', $debug_info, 'The pll_warnings entry should be added.' );
	}

	public function test_should_not_add_wpml_data_to_debug_information_when_wpml_config_file_is_absent() {
		$debug_info = $this->site_health->info( array() );

		$this->assertArrayNotHasKey(
			'wpml',
			$debug_info['pll_warnings']['fields'] ?? array(),
			'Debug information should not contain a wpml entry when no wpml-config.xml file is found.'
		);
	}

	/**
	 * Resets the cached wpml-config.xml file list.
	 *
	 * @return void
	 */
	private function reset_wpml_files_cache() {
		$files_reflection = new ReflectionProperty( PLL_WPML_Config::class, 'files' );

		// `setAccessible()` is required before PHP 8.1 to access non-public properties,
		// but is deprecated in recent PHP versions where properties are accessible by default.
		version_compare( PHP_VERSION, '8.1', '<' ) && $files_reflection->setAccessible( true );

		$files_reflection->setValue( PLL_WPML_Config::instance(), null );
	}

	/**
	 * Removes the wpml-config.xml file used by the test fixtures and resets
	 * `PLL_WPML_Config`'s internal file cache so it rescans the disk.
	 *
	 * @return void
	 */
	private function remove_wpml_config() {
		if ( file_exists( WP_CONTENT_DIR . '/polylang/wpml-config.xml' ) ) {
			unlink( WP_CONTENT_DIR . '/polylang/wpml-config.xml' );
		}
		if ( is_dir( WP_CONTENT_DIR . '/polylang' ) ) {
			rmdir( WP_CONTENT_DIR . '/polylang' );
		}

		$this->reset_wpml_files_cache();
	}

	/**
	 * Creates the wpml-config.xml file and resets the cached file list so the next call to
	 * `PLL_WPML_Config::get_files()` sees the created file instead of the stale cached result.
	 *
	 * @return void
	 */
	private function create_wpml_config() {
		@mkdir( WP_CONTENT_DIR . '/polylang' );
		copy(
			PLL_TEST_DATA_DIR . 'wpml-config.xml',
			WP_CONTENT_DIR . '/polylang/wpml-config.xml'
		);

		$this->reset_wpml_files_cache();
	}
}
