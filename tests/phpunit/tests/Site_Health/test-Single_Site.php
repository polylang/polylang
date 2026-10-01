<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

use WP_Debug_Data;

if ( ! is_multisite() ) :
	class Single_Site_Test extends TestCase {

		public function test_should_not_add_network_activated_field_when_not_multisite() {
			$debug_info = WP_Debug_Data::debug_data();

			$this->assertArrayNotHasKey( 'network_activated', $debug_info['pll_warnings']['fields'] ?? array(), 'Debug information should not contain an entry for multisite.' );
		}
	}

endif;
