<?php

/**
 * A test case class for Polylang standard tests
 */
class PLL_UnitTestCase extends WP_UnitTestCase {

	use PLL_UnitTestCase_Trait;

	/**
	 * @var PLL_Links_Model|null
	 */
	protected $links_model;

	/**
	 * @var PLL_Frontend|null
	 */
	protected $frontend;

	/**
	 * @var PLL_Admin|null
	 */
	protected $pll_admin;

	/**
	 * @var PLL_Base|null
	 */
	protected $pll_env;

	public function set_up() {
		parent::set_up();

		add_filter( 'wp_using_themes', '__return_true' ); // To pass the test in PLL_Choose_Lang::init() by default.
		add_filter( 'wp_doing_ajax', '__return_false' );
	}
}
