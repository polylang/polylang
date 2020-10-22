<?php

class Copied_Functions_Test extends PHPUnit_Framework_TestCase {
	use PLL_Check_WP_Functions_Trait;

	public function test_calendar_widget() {
		$this->check_method( 'f75a2c70d28b1d2c4e3e8fd86a8bb7d3', '5.4', 'WP_Widget_Calendar', 'widget' );
	}

	public function test_get_calendar() {
		$this->check_method( '218e132e305bb498f975d340a4503e15', '5.6', 'get_calendar' );
	}

	public function test_wp_admin_bar() {
		$this->check_method( '0104b0cde635904909a91ab3dafd5129', '5.4', 'wp_admin_bar_search_menu' );
	}
}
