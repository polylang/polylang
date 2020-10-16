<?php

class PLL_Canonical_UnitTestCase extends WP_Canonical_UnitTestCase {
	use PLL_UnitTestCase_Trait;

	public function setUp() {
		parent::setUp();

		add_filter( 'wp_using_themes', '__return_true' ); // To pass the test in PLL_Choose_Lang::init() by default.
		add_filter( 'wp_doing_ajax', '__return_false' );
	}

	/**
	 * Parses the canonical url if redirect, by either Polylang and/or WordPress.
	 *
	 * The {@see PLL_Frontend_Filters_Links::check_canonical_url()} method is hooked on {@see https://github.com/WordPress/wordpress-develop/blob/505fe2f0b87bba956d399f657f85a7073c978289/src/wp-includes/template-loader.php#L13 template_redirect}, which is not triggered during automated tests.
	 *
	 * @param string $test_url
	 *
	 * @return string Either the canonical url, if redirected, or the inputted $test_url.
	 */
	public function get_canonical( $test_url ) {
		$pll_redirected_url = self::$polylang->filters_links->check_canonical_url( home_url( $test_url ), false );
		$wp_redirected_url  = redirect_canonical( $pll_redirected_url, false );
		if ( ! $wp_redirected_url ) {
			return $pll_redirected_url;
		}

		return $wp_redirected_url;
	}
}
