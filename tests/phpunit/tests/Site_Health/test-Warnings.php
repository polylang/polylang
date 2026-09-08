<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

class Warnings_Test extends TestCase {

	public function test_should_add_wpml_config_data_to_debug_information() {
		$debug_info = $this->get_debug_info();

		$this->assertArrayHasKey( 'pll_warnings', $debug_info, 'Debug_info should contain an entry for pll_warnings.' );
		$this->assertArrayHasKey( 'wpml', $debug_info['pll_warnings']['fields'], 'Debug_info entry should contain wpml.' );
		$this->assertSame(
			'wpml-config.xml files',
			$debug_info['pll_warnings']['fields']['wpml']['label'],
			'Pll_warnings entry should be added correctly.'
		);
		$this->assertSame(
			array( 'Polylang' => WP_CONTENT_DIR . '/polylang/wpml-config.xml' ),
			$debug_info['pll_warnings']['fields']['wpml']['value'],
			'Pll_warnings entry should contain the expected wpml-config.xml file.'
		);
	}
}
