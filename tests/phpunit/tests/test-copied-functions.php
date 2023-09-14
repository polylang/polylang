<?php

class Copied_Functions_Test extends PHPUnit_Framework_TestCase {
	use PLL_Check_WP_Functions_Trait;

	public function test_calendar_widget() {
		$this->check_method( 'f0e00bf7ecb32405972b53fe9bc32acd', '6.4', 'WP_Widget_Calendar', 'widget' );
	}

	public function test_get_calendar() {
		$this->check_method( '01b136c86d6837211ada6651fba90e28', '6.3', 'get_calendar' );
	}

	public function test_wp_admin_bar() {
		$this->check_method( 'cc6308276c4e0553f75da06592f881cb', '6.2', 'wp_admin_bar_search_menu' );
	}

	public function test_sanitize_locale_name() {
		$this->check_method( 'c095fac87bb4632618334ab540b9e87d', '6.2.1', 'sanitize_locale_name' );
	}
}
