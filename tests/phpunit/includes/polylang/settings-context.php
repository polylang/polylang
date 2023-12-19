<?php

class PLL_Settings_Context extends PLL_Admin_Context {

	/**
	 * Returns the context class name.
	 *
	 * @since 3.6
	 *
	 * @return string
	 */
	public function get_name(): string {
		return PLL_Settings::class;
	}

	/**
	 * Returns the model according to the context.
	 * PLL_Admin_Model for Settings.
	 *
	 * @since 3.6
	 *
	 * @param array $options Polylang options.
	 * @return PLL_Model
	 */
	protected function get_model( array $options ): PLL_Model {
		return new PLL_Admin_Model( $options );
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
