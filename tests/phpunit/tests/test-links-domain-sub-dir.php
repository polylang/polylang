<?php

class Links_Domain_Sub_Dir_Test extends PLL_Domain_UnitTestCase {
	use PLL_Directory_Trait;

	public function set_up() {
		parent::set_up();

		$this->hosts = array(
			'en' => 'http://example.org',
			'fr' => 'http://example.fr',
			'de' => 'http://example.de',
		);

		self::$model->options['hide_default'] = 1;
		self::$model->options['force_lang'] = 3;
		self::$model->options['domains'] = $this->hosts;

		$this->filter_plugin_url();
		$this->init_links_model();
	}

	/**
	 * @ticket #1296
	 * @see https://github.com/polylang/polylang/issues/1296.
	 */
	public function test_flags_urls_sub_dir() {
		// Fake WP install in subdir.
		update_option( 'siteurl', 'http://example.org/sub' );
		update_option( 'home', 'http://example.org' );

		self::$model->clean_languages_cache();
		$languages = self::$model->get_languages_list();

		$this->assertCount( 3, $languages ); // @see `self::wpSetUpBeforeClass()`.

		foreach ( $languages as $language ) {
			$code = 'en' === $language->slug ? 'us' : $language->slug;
			$this->assertSame( $this->hosts[ $language->slug ] . "/sub/wp-content/plugins/polylang/flags/{$code}.png", $language->get_display_flag_url() );
			$this->assertSame( $this->hosts[ $language->slug ] . '/', apply_filters( 'pll_language_url', $language->get_home_url(), $language ) );
			$this->assertSame( $this->hosts[ $language->slug ] . '/', apply_filters( 'pll_language_url', $language->get_search_url(), $language ) );
		}
	}
}
