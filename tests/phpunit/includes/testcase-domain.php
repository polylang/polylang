<?php

class PLL_Domain_UnitTestCase extends PLL_UnitTestCase {
	use PLL_Links_Trait;

	protected $hosts;
	protected $is_subfolder_install = false;

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

		$this->filter_plugins_url();

		self::$model->options['default_lang'] = 'en';
		self::$model->options['hide_default'] = 1;
		self::$model->options['force_lang']   = 2;
		self::$model->options['domains']      = $this->hosts;
	}

	public function tear_down() {
		parent::tear_down();

		$this->reset__SERVER();
	}

	protected function _test_flags_urls( $curlang ) {
		// Needed by {@see pll_requested_url()}.
		$_SERVER['HTTP_HOST'] = wp_parse_url( $this->hosts[ $curlang->slug ], PHP_URL_HOST );

		$frontend          = new PLL_Frontend( $this->links_model );
		$frontend->curlang = $curlang;
		$frontend->init();
		$languages = $frontend->model->get_languages_list();

		$this->assertCount( 3, $languages ); // @see `self::wpSetUpBeforeClass()`.

		foreach ( $languages as $flag_language ) {
			$code = 'en' === $flag_language->slug ? 'us' : $flag_language->slug;
			$dir  = $this->is_subfolder_install ? '/sub' : '';
			$this->assertSame(
				$this->hosts[ $curlang->slug ] . "{$dir}/wp-content/plugins/polylang/flags/{$code}.png",
				$flag_language->get_display_flag_url(),
				"{$flag_language->name} flag URL with current language set to {$curlang->name} is wrong."
			);
		}
	}

	/**
	 * Enables a WordPress installation in directory.
	 *
	 * @return void
	 */
	protected function maybe_set_directory() {
		if ( ! $this->is_subfolder_install ) {
			return;
		}

		// Fake WP install in subdir.
		update_option( 'siteurl', 'http://example.org/sub' );
		update_option( 'home', 'http://example.org' );
	}

	/**
	 * @ticket #1296
	 * @see https://github.com/polylang/polylang/issues/1296.
	 *
	 * @param bool $is_subfolder_install Whether or not the test should be run in a subfolder install.
	 *
	 * @testWith [true]
	 *           [false]
	 */
	public function test_flags_urls_curlang_default( $is_subfolder_install ) {
		$this->is_subfolder_install = $is_subfolder_install;
		$this->maybe_set_directory();

		$en = self::$model->get_language( 'en' );

		$this->_test_flags_urls( $en );
	}

	/**
	 * @ticket #1296
	 * @see https://github.com/polylang/polylang/issues/1296.
	 *
	 * @param bool $is_subfolder_install Whether or not the test should be run in a subfolder install.
	 *
	 * @testWith [true]
	 *           [false]
	 */
	public function test_flags_urls_curlang_secondary( $is_subfolder_install ) {
		$this->is_subfolder_install = $is_subfolder_install;
		$this->maybe_set_directory();

		$fr = self::$model->get_language( 'fr' );

		$this->_test_flags_urls( $fr );
	}

	/**
	 * @ticket #1296
	 * @see https://github.com/polylang/polylang/issues/1296.
	 *
	 * @param bool $is_subfolder_install Whether or not the test should be run in a subfolder install.
	 *
	 * @testWith [true]
	 *           [false]
	 */
	public function test_home_and_search_urls( $is_subfolder_install ) {
		$this->is_subfolder_install = $is_subfolder_install;
		$this->maybe_set_directory();

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
}
