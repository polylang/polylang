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

		public function test_multisite_does_not_add_warnings_when_no_posts_are_missing_a_language() {
			self::factory()->post->create(
				array(
					'post_type' => 'post',
					'lang'      => 'en',
				)
			);

			$debug_info = $this->site_health->info( array() );

			$this->assertArrayHasKey( 'pll_warnings', $debug_info, 'Debug information should contain an entry for pll_warnings.' );
			$this->assertArrayNotHasKey(
				'post-no-lang',
				$debug_info['pll_warnings']['fields'],
				'Should not report posts without language when all posts have a language.'
			);
		}

		public function test_multisite_does_not_add_warnings_when_no_terms_are_missing_a_language() {
			self::factory()->term->create(
				array(
					'taxonomy' => 'category',
					'lang'     => 'en',
				)
			);

			$debug_info = $this->site_health->info( array() );

			$this->assertArrayHasKey( 'pll_warnings', $debug_info, 'Debug information should contain an entry for pll_warnings.' );
			$this->assertArrayNotHasKey(
				'term-no-lang',
				$debug_info['pll_warnings']['fields'],
				'Should not report term without language when all terms have a language.'
			);
		}
	}

endif;
