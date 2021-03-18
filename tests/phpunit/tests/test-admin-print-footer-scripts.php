<?php


class Admin_Print_Footer_Scripts_Test extends PLL_UnitTestCase {
	public function test_print_footer_script() {
		$links_model = new stdClass();
		$admin       = new PLL_Admin( $links_model );

		ob_start();
		$admin->admin_print_footer_scripts();
		$admin_footer_script = ob_get_clean();
	}
}
