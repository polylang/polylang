<?php

/**
 * Rest context.
 *
 * @since 3.6
 */
class PLL_Context_Rest extends PLL_Context_Base {

	/**
	 * Gets the context class name.
	 *
	 * @since 3.6
	 *
	 * @return string
	 */
	public function get_name(): string {
		return PLL_REST_Request::class;
	}

	/**
	 * Executes Polylang actions on filters that need to be run according to context.
	 * Also refresh WordPress’ rewrite rules.
	 *
	 * @since 3.6
	 *
	 * @return void
	 */
	protected function do_wordpress_actions() {
		global $wp_rest_server;

		$this->do_pll_actions( 'setup_theme' );
		$this->do_pll_actions( 'after_setup_theme' );
		$this->do_pll_actions( 'init' );
		$this->do_pll_actions( 'widgets_init' );

		$wp_rest_server = new Spy_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );

		$this->do_pll_actions( 'wp_loaded' );

		flush_rewrite_rules();

		$this->do_pll_actions( 'parse_request' );
	}
}
