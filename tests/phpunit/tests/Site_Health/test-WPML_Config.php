<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

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
		$this->create_wpml_config();

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

	public function test_should_not_add_simplexml_warning_when_it_is_present_with_wpml_config() {
		$this->create_wpml_config();

		$debug_info = $this->site_health->info( array() );

		$this->assertArrayNotHasKey( 'simplexml', $debug_info['pll_warnings']['fields'], 'Debug information entry should not contain simplexml.' );
	}

	public function test_should_not_add_wpml_data_to_debug_information_when_wpml_config_file_is_absent() {
		$debug_info = $this->site_health->info( array() );

		$this->assertArrayNotHasKey(
			'wpml',
			$debug_info['pll_warnings']['fields'] ?? array(),
			'Debug information should not contain a wpml entry when no wpml-config.xml file is found.'
		);
	}

	public function test_should_not_report_any_warning_field_when_nothing_to_report() {
		$debug_info = $this->site_health->info( array() );

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
	}

	public function test_should_not_add_network_activated_field_when_not_multisite() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'network_activated is always present on multisite.' );
		}

		$debug_info = $this->site_health->info( array() );

		$this->assertArrayNotHasKey( 'network_activated', $debug_info['pll_warnings']['fields'] ?? array(), 'Debug information should not contain an entry for multisite.' );
	}

	public function test_should_return_good_status_when_wpml_config_exists() {
		// SimpleXML is installed in the test environment, so the PHP extensions test should pass.
		$this->create_wpml_config();

		$site_health = new WP_Site_Health();
		$result = $site_health->get_test_php_extensions();

		$this->assertSame(
			'Required and recommended modules are installed',
			$result['label'],
			'The label should indicate that all required and recommended modules are installed.'
		);
		$this->assertSame(
			'good',
			$result['status'],
			'The status should indicate that all required and recommended modules are installed.'
		);
	}

	public function test_should_not_add_simplexml_to_modules_when_wpml_config_does_not_exist() {
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
			5 // Before Polylang filter
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
		$tata = $site_health->get_test_php_extensions();
		echo "\n===== tata =====\n";
		var_dump( $tata );

		$this->assertSame(
			$modules_before,
			$modules_after,
			'The modules list should be left untouched when no wpml-config.xml file is found.'
		);
	}

	public function test_should_add_simplexml_without_altering_other_modules() {
		$this->create_wpml_config();

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
			5 // Before Polylang filter
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
