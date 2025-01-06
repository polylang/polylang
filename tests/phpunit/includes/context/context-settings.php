<?php
use WP_Syntex\Polylang\Options\Options;

/**
 * Settings context.
 *
 * @since 3.6
 */
class PLL_Context_Settings extends PLL_Context_Admin {

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
	 * @param Options $options Polylang options.
	 * @return PLL_Model
	 */
	protected function get_model( Options $options ): PLL_Model {
		return new PLL_Admin_Model( $options );
	}
}
