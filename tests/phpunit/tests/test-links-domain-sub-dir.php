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

		$this->init_links_model();

		// Add a custom flag to tweak the URL otherwise we get the full path to the file in the test environment.
		add_filter(
			'pll_custom_flag',
			function( $flag, $code ) {
				$base_url = 'http://example.org';

				if ( 'us' !== $code ) {
					$base_url = "http://example.{$code}";
				}

				$custom_flag['url'] = "{$base_url}/sub/wp-content/plugins/polylang/flags/{$code}.png";

				return $custom_flag;
			},
			10,
			2
		);

		self::$model->clean_languages_cache();
		$en = self::$model->get_language( 'en' );

		$this->assertSame( $this->hosts['en'] . '/sub/wp-content/plugins/polylang/flags/us.png', $en->get_display_flag_url() );
		$this->assertSame( $this->hosts['en'] . '/', apply_filters( 'pll_language_url', $en->get_home_url() ) );
		$this->assertSame( $this->hosts['en'] . '/', apply_filters( 'pll_language_url', $en->get_search_url() ) );

		$fr = self::$model->get_language( 'fr' );

		$this->assertSame( $this->hosts['fr'] . '/sub/wp-content/plugins/polylang/flags/fr.png', $fr->get_display_flag_url() );
		$this->assertSame( $this->hosts['fr'] . '/', apply_filters( 'pll_language_url', $fr->get_home_url() ) );
		$this->assertSame( $this->hosts['fr'] . '/', apply_filters( 'pll_language_url', $fr->get_search_url() ) );

		$de = self::$model->get_language( 'de' );

		$this->assertSame( $this->hosts['de'] . '/sub/wp-content/plugins/polylang/flags/de.png', $de->get_display_flag_url() );
		$this->assertSame( $this->hosts['de'] . '/', apply_filters( 'pll_language_url', $de->get_home_url() ) );
		$this->assertSame( $this->hosts['de'] . '/', apply_filters( 'pll_language_url', $de->get_search_url() ) );
	}
}
