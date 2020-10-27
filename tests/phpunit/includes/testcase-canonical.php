<?php

class PLL_Canonical_UnitTestCase extends WP_Canonical_UnitTestCase {
	use PLL_UnitTestCase_Trait;

	protected $options;

	public static function wpTearDownAfterClass() {
		$options = PLL_Install::get_default_options();
		self::$polylang->model = new PLL_Admin_Model( $options ); // Needed for {@see PLL_UnitTest_Trait::delete_all_languages()}.
		parent::wpTearDownAfterClass();
	}

	public function setUp() {
		parent::setUp();

		add_filter( 'wp_using_themes', '__return_true' ); // To pass the test in PLL_Choose_Lang::init() by default.
		add_filter( 'wp_doing_ajax', '__return_false' );

		$this->options = PLL_Install::get_default_options();
	}

	/**
	 * Override WP_UnitTestCase_Base::set_permalink_structure() to allow polylang to do its logic.
	 *
	 * @param string $structure
	 */
	public function set_permalink_structure( $structure = '' ) {
		global $wp_rewrite;

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );

		// $wp_rewrite->flush_rules() is called in self::assertCanonical()
	}


	/**
	 * Set up the Polylang environment before testing canonical redirects.
	 *
	 * @param string $test_url
	 * @param string $expected
	 * @param int    $ticket
	 * @param array  $expected_doing_it_wrong
	 */
	public function assertCanonical( $test_url, $expected, $ticket = 0, $expected_doing_it_wrong = array() ) {
		global $wp_rewrite;

		if ( did_action( 'pll_language_defined' ) ) {
			$this->fail( 'Canonical tests MUST have only one call to PLL_UnitTestCase_Canonical::assertCanonical() per test.' );
		}

		// Needed by {@see pll_requested_url()}.
		$_SERVER['REQUEST_URI'] = $test_url;

		$model = new PLL_Model( $this->options );

		// register post types and taxonomies
		$model->post->register_taxonomy(); // needs this for 'lang' query var
		create_initial_taxonomies();

		// reset the links model according to the permalink structure
		$links_model    = $model->get_links_model();
		self::$polylang = new PLL_Frontend( $links_model );
		self::$polylang->init();
		do_action_ref_array( 'pll_init', array( &self::$polylang ) );

		// flush rules
		$wp_rewrite->flush_rules();

		return parent::assertCanonical( $test_url, $expected, $ticket, $expected_doing_it_wrong );
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
