<?php

namespace WP_Syntex\Polylang\Tests\Switcher;

class Print_Test extends TestCase {
	public function test_print(): void {
		$switcher = $this->get_switcher_and_init_frontend();
		$html_get = $switcher->get();

		ob_start();
		$switcher->print();
		$html_print = ob_get_clean();

		$this->assertSame( $html_get, $html_print );
	}
}
