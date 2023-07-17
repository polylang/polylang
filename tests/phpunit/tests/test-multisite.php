<?php

class Multisite_Test extends PLL_UnitTestCase {

	public function set_up() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test requires WordPress multisite.' );
		}

		parent::set_up();
	}

	public function test_new_site() {
		$site_id = self::factory()->blog->create();
		switch_to_blog( $site_id );
		$options = get_option( 'polylang' );
		restore_current_blog();
		$this->assertNotFalse( $options );
	}
}
