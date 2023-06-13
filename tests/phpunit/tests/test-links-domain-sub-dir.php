<?php

class Links_Domain_Sub_Dir_Test extends PLL_Domain_UnitTestCase {
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

		$this->init_links_model();
	}

	public function test_flags_urls_sub_dir() {
		// Fake WP install in subdir.
		update_option( 'siteurl', 'http://example.org/sub' );
		update_option( 'home', 'http://example.org' );

		// Filter `plugins_url` because `WP_CONTENT_URL` is already defined before we change the option `siteurl`.
		add_filter(
			'plugins_url',
			function( $url ) {
				$url = str_replace( POLYLANG_DIR . '/', '/polylang/', $url );

				return str_replace( 'wp-content', 'sub/wp-content', $url );
			},
			-1
		);

		$this->init_links_model();

		self::$model->clean_languages_cache();
		$en = self::$model->get_language( 'en' );

		$this->assertSame( $this->hosts['en'] . '/sub/wp-content/plugins/polylang/flags/us.png', $en->get_display_flag_url() );
		$this->assertSame( $this->hosts['en'] . '/', apply_filters( 'pll_language_url', $en->get_home_url(), $en ) );
		$this->assertSame( $this->hosts['en'] . '/', apply_filters( 'pll_language_url', $en->get_search_url(), $en ) );

		$fr = self::$model->get_language( 'fr' );

		$this->assertSame( $this->hosts['fr'] . '/sub/wp-content/plugins/polylang/flags/fr.png', $fr->get_display_flag_url() );
		$this->assertSame( $this->hosts['fr'] . '/', apply_filters( 'pll_language_url', $fr->get_home_url(), $fr ) );
		$this->assertSame( $this->hosts['fr'] . '/', apply_filters( 'pll_language_url', $fr->get_search_url(), $fr ) );

		$de = self::$model->get_language( 'de' );

		$this->assertSame( $this->hosts['de'] . '/sub/wp-content/plugins/polylang/flags/de.png', $de->get_display_flag_url() );
		$this->assertSame( $this->hosts['de'] . '/', apply_filters( 'pll_language_url', $de->get_home_url(), $de ) );
		$this->assertSame( $this->hosts['de'] . '/', apply_filters( 'pll_language_url', $de->get_search_url(), $de ) );
	}
}
