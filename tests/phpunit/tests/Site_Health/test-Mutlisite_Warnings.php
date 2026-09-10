<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

if ( is_multisite() ) :

	class Multisite_Warnings_Test extends TestCase {
		public function test_should_add_network_activated_field_when_polylang_is_not_network_activated() {
			$debug_info = $this->site_health->info( array() );

			$this->assertArrayHasKey( 'network_activated', $debug_info['pll_warnings']['fields'], 'Debug information should contain an entry for network activation.' );
			$this->assertSame(
				'Network activated',
				$debug_info['pll_warnings']['fields']['network_activated']['label'],
				'The network_activated entry should have the expected label.'
			);
			$this->assertSame(
				'No',
				$debug_info['pll_warnings']['fields']['network_activated']['value'],
				'The network_activated entry should contain the expected value.'
			);
		}

		public function test_should_set_network_activated_to_yes_when_polylang_is_network_activated() {
			$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );
			$active_sitewide_plugins[ POLYLANG_BASENAME ] = time();
			update_site_option( 'active_sitewide_plugins', $active_sitewide_plugins );

			$debug_info = $this->site_health->info( array() );

			$this->assertArrayHasKey( 'network_activated', $debug_info['pll_warnings']['fields'], 'Debug information should contain an entry for network activation.' );
			$this->assertSame(
				'Network activated',
				$debug_info['pll_warnings']['fields']['network_activated']['label'],
				'The network_activated entry should have the expected label.'
			);
			$this->assertSame(
				'Yes',
				$debug_info['pll_warnings']['fields']['network_activated']['value'],
				'The network_activated entry should contain the expected value.'
			);
		}
	}

endif;
