<?php
/**
 * @package Polylang
 */

/**
 * Class to load the Share Slugs module.
 *
 * @since 3.3
 */
class PLL_Share_Slug {
	/**
	 * Init.
	 *
	 * @since 3.3
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'pll_settings_modules', array( $this, 'add_setting' ) );
	}

	/**
	 * Adds the Share Slugs module to the list of setting modules.
	 *
	 * @since 3.3
	 *
	 * @param  array<string> $modules The list of module classes.
	 * @return array<string>
	 */
	public function add_setting( $modules ) {
		$modules[] = PLL_Settings_Preview_Share_Slug::class;
		return $modules;
	}
}
