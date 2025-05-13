<?php

class Copied_Functions_Test extends PHPUnit_Framework_TestCase {
	use PLL_Check_WP_Functions_Trait;

	public function test_calendar_widget() {
		$this->check_method( 'f0e00bf7ecb32405972b53fe9bc32acd', '6.4', 'WP_Widget_Calendar', 'widget' );
	}

	public function test_get_calendar() {
		$this->check_method( '99d663e95afafddfda886b1b854d611c', '6.9', 'get_calendar' );
	}

	public function test_wp_admin_bar() {
		$this->check_method( 'cc6308276c4e0553f75da06592f881cb', '6.2', 'wp_admin_bar_search_menu' );
	}

	public function test_sanitize_locale_name() {
		$this->check_method( 'c095fac87bb4632618334ab540b9e87d', '6.2.1', 'sanitize_locale_name' );
	}

	/**
	 * Monitors PLL_Term_Slug::maybe_get_parent_suffix()
	 */
	public function test_wp_unique_term_slug() {
		$this->check_method( 'c926e40169b2e1b430eb21039ae8d9d7', '6.4', 'wp_unique_term_slug' );
		$this->check_internal_method( 'b84b8505f2708c20ef72d9f01568e305', PLL_Term_Slug::class, 'maybe_get_parent_suffix' );
	}
}
