<?php

class Settings_Browser_Test extends PLL_UnitTestCase {

	public function set_up() {
		parent::set_up();

		$links_model = self::$model->get_links_model();
		$this->pll_env = new PLL_Settings( $links_model );
	}

	public function test_active_true() {
		self::$model->options['browser'] = 1;
		$module = new PLL_Settings_Browser( $this->pll_env );
		$this->assertTrue( $module->is_active() );
	}

	public function test_active_false() {
		self::$model->options['browser'] = 0;
		$module = new PLL_Settings_Browser( $this->pll_env );
		$this->assertFalse( $module->is_active() );
	}
}
