<?php

use Brain\Monkey;

abstract class PLL_Domain_UnitTestCase extends PLL_UnitTestCase {
	use PLL_Mocks_Trait;
	use PLL_Test_Links_Trait;

	protected $hosts;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE' );
	}

	public function set_up() {
		parent::set_up();
		Monkey\setUp();

		$this->filter_plugins_url();

		self::$model->options['default_lang'] = 'en';
	}

	public function tear_down() {
		Monkey\tearDown();
		parent::tear_down();

		$this->reset__SERVER();
	}

	protected function _test_flags_urls( $curlang, $is_subfolder_install, $cache_languages, $cache_home_url ) {
		$this->mock_constants(
			array(
				'PLL_CACHE_LANGUAGES' => $cache_languages,
				'PLL_CACHE_HOME_URL'  => $cache_home_url,
			)
		);

		// Needed by {@see pll_requested_url()}.
		$_SERVER['HTTP_HOST'] = wp_parse_url( $this->hosts[ $curlang->slug ], PHP_URL_HOST );

		$frontend          = new PLL_Frontend( $this->links_model );
		$frontend->curlang = $curlang;
		$frontend->init();
		$languages = $frontend->model->get_languages_list();

		$this->assertCount( 3, $languages ); // @see `self::wpSetUpBeforeClass()`.

		foreach ( $languages as $flag_language ) {
			$code = 'en' === $flag_language->slug ? 'us' : $flag_language->slug;
			$dir  = $is_subfolder_install ? "/{$this->subfolder_name}" : '';
			$this->assertSame(
				$this->hosts[ $curlang->slug ] . "{$dir}/wp-content/plugins/polylang/flags/{$code}.png",
				$flag_language->get_display_flag_url(),
				"{$flag_language->name} flag URL with current language set to {$curlang->name} is wrong."
			);
		}
	}

	/**
	 * @ticket #1296
	 * @see https://github.com/polylang/polylang/issues/1296.
	 *
	 * @dataProvider url_context_provider
	 *
	 * @param bool $is_subfolder_install Whether or not the test should be run in a subfolder install.
	 * @param bool $cache_languages      Value of the constant `PLL_CACHE_LANGUAGES`.
	 * @param bool $cache_home_url       Value of the constant `PLL_CACHE_HOME_URL`.
	 */
	public function test_flags_urls_curlang_default( $is_subfolder_install, $cache_languages, $cache_home_url ) {
		$this->maybe_set_subfolder_install( $is_subfolder_install );

		$en = self::$model->get_language( 'en' );

		$this->_test_flags_urls( $en, $is_subfolder_install, $cache_languages, $cache_home_url );
	}

	/**
	 * @ticket #1296
	 * @see https://github.com/polylang/polylang/issues/1296.
	 *
	 * @dataProvider url_context_provider
	 *
	 * @param bool $is_subfolder_install Whether or not the test should be run in a subfolder install.
	 * @param bool $cache_languages      Value of the constant `PLL_CACHE_LANGUAGES`.
	 * @param bool $cache_home_url       Value of the constant `PLL_CACHE_HOME_URL`.
	 */
	public function test_flags_urls_curlang_secondary( $is_subfolder_install, $cache_languages, $cache_home_url ) {
		$this->maybe_set_subfolder_install( $is_subfolder_install );

		$fr = self::$model->get_language( 'fr' );

		$this->_test_flags_urls( $fr, $is_subfolder_install, $cache_languages, $cache_home_url );
	}

	/**
	 * @ticket #1296
	 * @see https://github.com/polylang/polylang/issues/1296.
	 *
	 * @dataProvider url_context_provider
	 *
	 * @param bool $is_subfolder_install Whether or not the test should be run in a subfolder install.
	 * @param bool $cache_languages      Value of the constant `PLL_CACHE_LANGUAGES`.
	 * @param bool $cache_home_url       Value of the constant `PLL_CACHE_HOME_URL`.
	 */
	public function test_home_and_search_urls( $is_subfolder_install, $cache_languages, $cache_home_url ) {
		$this->maybe_set_subfolder_install( $is_subfolder_install );
		$this->mock_constants(
			array(
				'PLL_CACHE_LANGUAGES' => $cache_languages,
				'PLL_CACHE_HOME_URL'  => $cache_home_url,
			)
		);

		self::$model->clean_languages_cache();
		$languages = self::$model->get_languages_list();

		$this->assertCount( 3, $languages ); // @see `self::wpSetUpBeforeClass()`.

		foreach ( $languages as $language ) {
			$this->assertSame( $this->hosts[ $language->slug ] . '/', $language->get_home_url() );
			$this->assertSame( $this->hosts[ $language->slug ] . '/', $language->get_search_url() );
		}
	}

	public function test_add_language_to_link() {
		$url = $this->hosts['en'] . '/test/';

		$this->assertEquals( $this->hosts['en'] . '/test/', $this->links_model->add_language_to_link( $url, self::$model->get_language( 'en' ) ) );
		$this->assertEquals( $this->hosts['fr'] . '/test/', $this->links_model->add_language_to_link( $url, self::$model->get_language( 'fr' ) ) );
	}

	public function test_double_add_language_to_link() {
		$this->assertEquals( $this->hosts['fr'] . '/test/', $this->links_model->add_language_to_link( $this->hosts['fr'] . '/test/', self::$model->get_language( 'fr' ) ) );
	}

	public function test_remove_language_from_link() {
		$this->assertEquals( $this->hosts['en'] . '/test/', $this->links_model->remove_language_from_link( $this->hosts['en'] . '/test/' ) );
		$this->assertEquals( $this->hosts['en'] . '/test/', $this->links_model->remove_language_from_link( $this->hosts['fr'] . '/test/' ) );
	}

	public function test_switch_language_in_link() {
		$this->assertEquals( $this->hosts['en'] . '/test/', $this->links_model->switch_language_in_link( $this->hosts['fr'] . '/test/', self::$model->get_language( 'en' ) ) );
		$this->assertEquals( $this->hosts['de'] . '/test/', $this->links_model->switch_language_in_link( $this->hosts['fr'] . '/test/', self::$model->get_language( 'de' ) ) );
		$this->assertEquals( $this->hosts['fr'] . '/test/', $this->links_model->switch_language_in_link( $this->hosts['en'] . '/test/', self::$model->get_language( 'fr' ) ) );
	}

	public function test_add_paged_to_link() {
		$this->assertEquals( $this->hosts['en'] . '/test/page/2/', $this->links_model->add_paged_to_link( $this->hosts['en'] . '/test/', 2 ) );
		$this->assertEquals( $this->hosts['fr'] . '/test/page/2/', $this->links_model->add_paged_to_link( $this->hosts['fr'] . '/test/', 2 ) );
	}

	public function test_remove_paged_from_link() {
		$this->assertEquals( $this->hosts['en'] . '/test/', $this->links_model->remove_paged_from_link( $this->hosts['en'] . '/test/page/2/' ) );
		$this->assertEquals( $this->hosts['fr'] . '/test/', $this->links_model->remove_paged_from_link( $this->hosts['fr'] . '/test/page/2/' ) );
	}

	public function test_get_language_from_url() {
		// hack $_SERVER
		$server = $_SERVER;
		$_SERVER['REQUEST_URI'] = '/test/';
		$_SERVER['HTTP_HOST'] = wp_parse_url( $this->hosts['fr'], PHP_URL_HOST );
		$this->assertEquals( 'fr', $this->links_model->get_language_from_url() );

		// clean up
		$_SERVER = $server;
	}

	public function test_home_url() {
		$this->assertEquals( $this->hosts['en'] . '/', $this->links_model->home_url( self::$model->get_language( 'en' ) ) );
		$this->assertEquals( $this->hosts['fr'] . '/', $this->links_model->home_url( self::$model->get_language( 'fr' ) ) );
	}

	public function test_allowed_redirect_hosts() {
		$hosts = str_replace( 'http://', '', array_values( $this->hosts ) );
		$this->assertSameSets( $hosts, $this->links_model->allowed_redirect_hosts( array() ) );
		$this->assertEquals( $this->hosts['fr'], wp_validate_redirect( $this->hosts['fr'] ) );
	}

	public function test_upload_dir() {
		// Hack $_SERVER.
		$server = $_SERVER;
		$_SERVER['REQUEST_URI'] = '/test/';
		$_SERVER['HTTP_HOST'] = wp_parse_url( $this->hosts['fr'], PHP_URL_HOST );
		$uploads = wp_get_upload_dir(); // Since WP 4.5.

		$this->assertStringContainsString( $this->hosts['fr'], $uploads['url'] );
		$this->assertStringContainsString( $this->hosts['fr'], $uploads['baseurl'] );

		$_SERVER['HTTP_HOST'] = wp_parse_url( $this->hosts['en'], PHP_URL_HOST );
		$uploads = wp_get_upload_dir(); // Since WP 4.5.

		$this->assertStringContainsString( $this->hosts['en'], $uploads['url'] );
		$this->assertStringContainsString( $this->hosts['en'], $uploads['baseurl'] );

		// Clean up.
		$_SERVER = $server;
	}

	public function url_context_provider() {
		return array(
			'is subfolder + all caches'      => array(
				'is_subfolder_install' => true,
				'cache_languages'      => true,
				'cache_home_url'       => true,
			),
			'is subfolder + cache languages' => array(
				'is_subfolder_install' => true,
				'cache_languages'      => true,
				'cache_home_url'       => false,
			),
			'is subfolder + cache home url'  => array(
				'is_subfolder_install' => true,
				'cache_languages'      => false,
				'cache_home_url'       => true,
			),
			'is subfolder + no cache'        => array(
				'is_subfolder_install' => true,
				'cache_languages'      => false,
				'cache_home_url'       => false,
			),
			'is subfolder + cache not set'   => array(
				'is_subfolder_install' => true,
				'cache_languages'      => null,
				'cache_home_url'       => null,
			),
			'no subfolder + all caches'      => array(
				'is_subfolder_install' => false,
				'cache_languages'      => true,
				'cache_home_url'       => true,
			),
			'no subfolder + cache languages' => array(
				'is_subfolder_install' => false,
				'cache_languages'      => true,
				'cache_home_url'       => false,
			),
			'no subfolder + cache home url'  => array(
				'is_subfolder_install' => false,
				'cache_languages'      => false,
				'cache_home_url'       => true,
			),
			'no subfolder + no cache'        => array(
				'is_subfolder_install' => false,
				'cache_languages'      => false,
				'cache_home_url'       => false,
			),
			'no subfolder + cache not set'   => array(
				'is_subfolder_install' => false,
				'cache_languages'      => null,
				'cache_home_url'       => null,
			),
		);
	}
}
