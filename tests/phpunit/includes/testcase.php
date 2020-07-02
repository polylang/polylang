<?php

/**
 * A test case class for Polylang standard tests
 */
class PLL_UnitTestCase extends WP_UnitTestCase {
	use PLL_UnitTestCase_Trait;

	function setUp() {
		parent::setUp();

		add_filter( 'wp_using_themes', '__return_true' ); // To pass the test in PLL_Choose_Lang::init() by default.
		add_filter( 'wp_doing_ajax', '__return_false' );
	}

	/**
	 * Creates a Polylang environment to recreate an administration dashboard context.
	 *
	 * @since 2.8
	 *
	 * @param $options_overrides
	 * @return array
	 */
	protected function admin_setup($options_overrides)
	{
		$options = array_merge(PLL_Install::get_default_options(), $options_overrides);
		$model = new PLL_Admin_Model($options);
		$links_model = new PLL_Links_Default($model);
		$polylang = new PLL_Admin($links_model);
		$polylang->init();
		do_action('wp_loaded');
		return $polylang;
	}
}
