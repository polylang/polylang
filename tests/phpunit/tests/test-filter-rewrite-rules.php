<?php

class Filter_Rewrite_Rules_Test extends PLL_UnitTestCase {
	/**
	 * @var PLL_Admin
	 */
	protected $pll_env;

	protected $structure = '/%postname%/';

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	public function set_up() {
		parent::set_up();


		// Set Polylang env up.
		$options                      = PLL_Install::get_default_options();
		$options['hide_default']      = 1;
		$options['default_lang']      = 'en';
		$options['post_types']        = array(
			'trcpt' => 'trcpt',
		);
		$model                        = new PLL_Admin_Model( $options );
		$links_model                  = new PLL_Links_Default( $model );
		$this->pll_env                = new PLL_Admin( $links_model );
		$this->pll_env->model         = $model;
		$this->pll_env->filters_links = new PLL_Links_Directory( $this->pll_env );

		/**
		 * Creates a translated custom post type with archives to pass some conditions
		 * in PLL_Links_Directory::rewrite_rules() that could lead to errors.
		 */
		register_post_type( 'trcpt', array( 'public' => true, 'has_archive' => true ) );
	}

	public function test_bad_rewrite_rules_filtering() {
		global $wp_rewrite;

		// Let's do bad things to rewrite rules.
		add_filter(
			'rewrite_rules_array',
			function( $rules ) {
				$rules['dumb'] = array(
					'do not' => 'do this!',
				);
				$rules[1]      = 'even dumber';

				return $rules;
			},
			1 // Before Polylang.
		);

		// Switch to pretty permalinks.
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $this->structure );
		$this->pll_env->model->post->register_taxonomy(); // Need this for 'lang' query var.
		create_initial_taxonomies();
		$links_directory = new PLL_Links_Directory( $this->pll_env->model );
		$links_directory->init();

		// Let's flush rules and filter it!
		try {
			$wp_rewrite->flush_rules();
		} catch ( \Throwable $th ) {
			$message = $th->getMessage();
			$this->fail( 'An error occurs while filtering bad rewrite rules with the following message:' . PHP_EOL . "$message." );
		}

		/**
		 * expectNotToPerformAssertions() being available only since PHPUnit 7.2,
		 * We have to use this trick...
		 */
		$this->assertTrue( true );
	}
}
