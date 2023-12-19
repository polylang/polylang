<?php

class PLL_Admin_Context extends PLL_Context {

	/**
	 * Returns the context class name.
	 *
	 * @since 3.6
	 *
	 * @return string
	 */
	public function get_name(): string {
		return PLL_Admin::class;
	}

	/**
	 * Executes Polylang actions on filters that need to be run according to context.
	 * Also refreshes WordPress’ rewrite rules.
	 *
	 * @since 3.6
	 *
	 * @return void
	 */
	protected function do_wordpress_actions() {
		$this->do_pll_actions( 'setup_theme' );
		$this->do_pll_actions( 'after_setup_theme' );
		$this->do_pll_actions( 'init' );
		$this->do_pll_actions( 'widgets_init' );
		$this->do_pll_actions( 'wp_loaded' );

		flush_rewrite_rules();

		$this->do_pll_actions( 'admin_init' );
		$this->do_pll_actions( 'current_screen' );
	}
}
