<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

use WP_Site_Health;

class Warnings_Test extends TestCase {
	public function tear_down() {
		if ( ! file_exists( WP_CONTENT_DIR . '/polylang/wpml-config.xml' ) ) {
			self::restore_wpml_config();
		}

		parent::tear_down();
	}

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

	public function test_should_not_add_simplexml_warning_when_it_is_present_with_wpml_config() {
		$debug_info = $this->site_health->info( array() );

		$this->assertArrayNotHasKey( 'simplexml', $debug_info['pll_warnings']['fields'], 'Debug information entry should not contain simplexml.' );
	}

	public function test_should_not_add_wpml_data_to_debug_information_when_wpml_config_file_is_absent() {
		self::remove_wpml_config();

		$debug_info = $this->site_health->info( array() );

		$this->assertArrayNotHasKey(
			'wpml',
			$debug_info['pll_warnings']['fields'] ?? array(),
			'Debug information should not contain a wpml entry when no wpml-config.xml file is found.'
		);
	}

	public function test_should_not_report_any_warning_field_when_nothing_to_report() {
		self::remove_wpml_config();

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

	public function test_should_add_simplexml_to_modules_when_wpml_config_exists() {
		/** @var array|null $captured_modules */
		$captured_modules = null;

		add_filter(
			'site_status_test_php_modules',
			function ( $modules ) use ( &$captured_modules ) {
				$captured_modules = $modules;
				return $modules;
			},
			20 // After Polylang filter
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

	public function test_should_not_add_simplexml_to_modules_when_wpml_config_does_not_exist() {
		self::remove_wpml_config();
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
			$modules_before,
			$modules_after,
			'The modules list should be left untouched when no wpml-config.xml file is found.'
		);
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
}
