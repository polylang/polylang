<?php

/**
 * Frontend context.
 *
 * @since 3.6
 */
class PLL_Context_Frontend extends PLL_Context_Base {

	/**
	 * Returns the context class name.
	 *
	 * @since 3.6
	 *
	 * @return string
	 */
	public function get_name(): string {
		return PLL_Frontend::class;
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

		$this->do_pll_actions( 'parse_request' );
		$this->do_pll_actions( 'wp' );
		$this->do_pll_actions( 'template_redirect' );
	}
}
