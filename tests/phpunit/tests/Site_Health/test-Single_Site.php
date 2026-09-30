<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

if ( ! is_multisite() ) :
	class Single_Site_Test extends TestCase {

		public function test_should_not_add_network_activated_field_when_not_multisite() {
			$debug_info = $this->site_health->info( array() );

			$this->assertArrayNotHasKey( 'network_activated', $debug_info['pll_warnings']['fields'] ?? array(), 'Debug information should not contain an entry for multisite.' );
		}
	}

endif;
