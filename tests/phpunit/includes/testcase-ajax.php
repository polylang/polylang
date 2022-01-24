<?php

/**
 * A test case class for Polylang ajax tests
 */
class PLL_Ajax_UnitTestCase extends WP_Ajax_UnitTestCase {
	use PLL_UnitTestCase_Trait;

	public function set_up() {
		parent::set_up();

		remove_action( 'admin_init', '_maybe_update_core' );
		remove_action( 'admin_init', '_maybe_update_plugins' );
		remove_action( 'admin_init', '_maybe_update_themes' );
	}
}
