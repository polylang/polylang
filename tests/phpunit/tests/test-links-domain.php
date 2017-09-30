<?php

class Links_Domain_Test extends PLL_Domain_UnitTestCase {

	function setUp() {
		parent::setUp();

		global $wp_rewrite;

		$this->hosts = array(
			'en' => 'http://example.org',
			'fr' => 'http://example.fr',
			'de' => 'http://example.de',
		);

		self::$polylang->options['hide_default'] = 1;
		self::$polylang->options['force_lang'] = 3;
		self::$polylang->options['domains'] = $this->hosts;

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $this->structure );
		self::$polylang->links_model = self::$polylang->model->get_links_model();
	}

	function test_wrong_get_language_from_url() {
		$_SERVER['HTTP_HOST'] = 'de.example.fr';
		$this->assertEmpty( self::$polylang->links_model->get_language_from_url() );

		$_SERVER['HTTP_HOST'] = 'example.com';
		$this->assertEmpty( self::$polylang->links_model->get_language_from_url() );
	}

	function test_login_url() {
		$_SERVER['HTTP_HOST'] = parse_url( $this->hosts['en'], PHP_URL_HOST );
		$this->assertEquals( $this->hosts['en'] . '/wp-login.php', wp_login_url() );

		$_SERVER['HTTP_HOST'] = parse_url( $this->hosts['fr'], PHP_URL_HOST );
		$this->assertEquals( $this->hosts['fr'] . '/wp-login.php', wp_login_url() );
	}

	// Bug fixed in version 2.1.2
	function test_second_level_domain() {
		self::$polylang->options['domains']['fr'] = 'http://example.org.fr';
		self::$polylang->links_model = self::$polylang->model->get_links_model();

		$url = 'http://example.org.fr';

		$this->assertEquals( 'http://example.org.fr', self::$polylang->links_model->add_language_to_link( $url, self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEquals( 'http://example.org', self::$polylang->links_model->remove_language_from_link( $url, self::$polylang->model->get_language( 'fr' ) ) );

		$url = 'http://example.org.fr/test/';

		$this->assertEquals( 'http://example.org.fr/test/', self::$polylang->links_model->add_language_to_link( $url, self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEquals( 'http://example.org/test/', self::$polylang->links_model->remove_language_from_link( $url, self::$polylang->model->get_language( 'fr' ) ) );
	}
}
