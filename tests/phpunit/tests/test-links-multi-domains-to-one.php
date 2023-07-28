<?php

use Brain\Monkey;

/**
 * @group links
 * @group domain
 */
class Links_Multi_Domains_To_One_Test extends PLL_UnitTestCase {
	use PLL_Mocks_Trait;
	use PLL_Test_Links_Trait;

	protected $main_domain;
	protected $secondary_domain;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		if ( ! defined( 'PLL_CACHE_HOME_URL' ) || PLL_CACHE_HOME_URL ) {
			self::markTestSkipped( 'This test requires not to cache home URL.' );
		}

		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	public function set_up() {
		self::$model->options['default_lang'] = 'en';
		self::$model->options['hide_default'] = 1;
		self::$model->options['force_lang']   = 3;

		$this->main_domain      = get_option( 'siteurl' );
		$this->secondary_domain = 'https://choiceof.dev/';
		$_SERVER['HTTP_HOST']   = wp_parse_url( $this->secondary_domain, PHP_URL_HOST );

		parent::set_up();
		Monkey\setUp();
	}

	public function tear_down() {
		Monkey\tearDown();
		parent::tear_down();

		$this->reset__SERVER();
	}

	/**
	 * @ticket #1296
	 * @see https://github.com/polylang/polylang/issues/1296.
	 *
	 * @param bool $cache_languages Value of the constant `PLL_CACHE_LANGUAGES`.
	 * @param bool $cache_home_url  Value of the constant `PLL_CACHE_HOME_URL`.
	 *
	 * @testWith [true, true]
	 *           [true, false]
	 *           [false, true]
	 *           [false, false]
	 */
	public function test_home_and_search_urls( $cache_languages, $cache_home_url ) {
		$this->mock_constants(
			array(
				'PLL_CACHE_LANGUAGES' => $cache_languages,
				'PLL_CACHE_HOME_URL'  => $cache_home_url,
			)
		);

		// First let's create the languages list and the transient with the main domain.
		$this->init_links_model();
		$frontend = new PLL_Frontend( $this->links_model );
		$frontend->init();
		$languages = $frontend->model->get_languages_list(); // Create the transient with main domain.

		// Then relaunch the context to filter home and site URLs with the secondary domain.
		add_filter(
			'site_url',
			function( $url ) {
				return str_replace( $this->main_domain, $this->secondary_domain, $url );
			},
			1
		);
		add_filter(
			'home_url',
			function( $url ) {
				return str_replace( $this->main_domain, $this->secondary_domain, $url );
			},
			1
		);
		$this->init_links_model();
		$frontend = new PLL_Frontend( $this->links_model );
		$frontend->init();

		$this->assertCount( 2, $languages ); // @see `self::wpSetUpBeforeClass()`.

		foreach ( $languages as $language ) {
			$this->assertSame( $this->secondary_domain, $language->get_home_url() );
			$this->assertSame( $this->secondary_domain, $language->get_search_url() );
		}
	}
}
