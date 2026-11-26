<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

class Options_Sync_Test extends TestCase {

	public function test_sync_disabled_when_empty() {
		$this->pll_admin->options->reset( 'sync' );

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field(
			$fields,
			'sync',
			'Sync',
			'0: Synchronization disabled'
		);
	}

	public function test_sync_lists_selected_keys() {
		$this->pll_admin->options['sync'] = array( 'taxonomies', 'post_meta' );

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field( $fields, 'sync', 'Sync', 'taxonomies, post_meta' );
	}
}
