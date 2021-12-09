<?php

/**
 * A test case class for Polylang standard tests
 */
class PLL_UnitTestCase extends WP_UnitTestCase {
	use PLL_UnitTestCase_Trait;

	public function set_up() {
		parent::set_up();

		add_filter( 'wp_using_themes', '__return_true' ); // To pass the test in PLL_Choose_Lang::init() by default.
		add_filter( 'wp_doing_ajax', '__return_false' );
	}
}
