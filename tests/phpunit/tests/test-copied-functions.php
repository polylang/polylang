<?php

class Copied_Functions_Test extends PHPUnit_Framework_TestCase {
	use PLL_Check_WP_Functions_Trait;

	public function test_calendar_widget() {
		$this->check_method( 'f0e00bf7ecb32405972b53fe9bc32acd', '6.4', 'WP_Widget_Calendar', 'widget' );
	}

	public function test_get_calendar() {
		$this->check_method( '1091cc9154c78828de17f4398696db8d', '7.3', 'get_calendar' );
	}

	public function test_wp_admin_bar() {
		$this->check_method( 'c8fb57ff3a4a4efe69255d62bcbc9dd0', '7.2', 'wp_admin_bar_search_menu' );
	}

	/**
	 * Monitors PLL_Term_Slug::maybe_get_parent_suffix()
	 */
	public function test_wp_unique_term_slug() {
		$this->check_internal_method( '747593613aa10fa414d46ce80227d520', PLL_Term_Slug::class, 'maybe_get_parent_suffix' );
		$this->check_method( '02a156bbc3b2661986b1a3b037d351e2', '7.3', 'wp_unique_term_slug' );
	}
}
