<?php

class Links_Directory_Test extends PLL_UnitTestCase {
	protected $structure = '/%postname%/';
	protected $host = 'http://example.org';

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE_formal' );
	}

	function setUp() {
		parent::setUp();

		global $wp_rewrite;

		self::$polylang->options['hide_default'] = 1;
		self::$polylang->options['rewrite'] = 1;

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $this->structure );
		self::$polylang->links_model = self::$polylang->model->get_links_model();
		self::$polylang->links_model->init();
	}

	function test_add_language_to_link() {
		$url = $this->host . '/test/';

		$this->assertEquals( $this->host . '/test/', self::$polylang->links_model->add_language_to_link( $url, self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( $this->host . '/fr/test/', self::$polylang->links_model->add_language_to_link( $url, self::$polylang->model->get_language( 'fr' ) ) );

		self::$polylang->options['rewrite'] = 0;
		$this->assertEquals( $this->host . '/test/', self::$polylang->links_model->add_language_to_link( $url, self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( $this->host . '/language/fr/test/', self::$polylang->links_model->add_language_to_link( $url, self::$polylang->model->get_language( 'fr' ) ) );
	}

	function test_double_add_language_to_link() {
		$this->assertEquals( $this->host . '/fr/test/', self::$polylang->links_model->add_language_to_link( $this->host . '/fr/test/', self::$polylang->model->get_language( 'fr' ) ) );

		self::$polylang->options['rewrite'] = 0;
		$this->assertEquals( $this->host . '/language/fr/test/', self::$polylang->links_model->add_language_to_link( $this->host . '/language/fr/test/', self::$polylang->model->get_language( 'fr' ) ) );
	}

	function test_remove_language_from_link() {
		$this->assertEquals( $this->host . '/en/test/', self::$polylang->links_model->remove_language_from_link( $this->host . '/en/test/' ) );
		$this->assertEquals( $this->host . '/test/', self::$polylang->links_model->remove_language_from_link( $this->host . '/fr/test/' ) );

		self::$polylang->options['rewrite'] = 0;
		$this->assertEquals( $this->host . '/language/en/test/', self::$polylang->links_model->remove_language_from_link( $this->host . '/language/en/test/' ) );
		$this->assertEquals( $this->host . '/test/', self::$polylang->links_model->remove_language_from_link( $this->host . '/language/fr/test/' ) );
	}

	function test_switch_language_in_link() {
		$this->assertEquals( $this->host . '/test/', self::$polylang->links_model->switch_language_in_link( $this->host . '/fr/test/' , self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( $this->host . '/de/test/', self::$polylang->links_model->switch_language_in_link( $this->host . '/fr/test/' , self::$polylang->model->get_language( 'de' ) ) );
		$this->assertEquals( $this->host . '/fr/test/', self::$polylang->links_model->switch_language_in_link( $this->host . '/test/' , self::$polylang->model->get_language( 'fr' ) ) );
	}

	function test_add_paged_to_link() {
		$this->assertEquals( $this->host . '/test/page/2/', self::$polylang->links_model->add_paged_to_link( $this->host . '/test/', 2 ) );
		$this->assertEquals( $this->host . '/fr/test/page/2/', self::$polylang->links_model->add_paged_to_link( $this->host . '/fr/test/', 2 ) );
	}

	function test_remove_paged_from_link() {
		$this->assertEquals( $this->host . '/test/', self::$polylang->links_model->remove_paged_from_link( $this->host . '/test/page/2/' ) );
		$this->assertEquals( $this->host . '/fr/test/', self::$polylang->links_model->remove_paged_from_link( $this->host . '/fr/test/page/2/' ) );
	}

	function test_get_language_from_url() {
		$server = $_SERVER;

		$this->assertEquals( 'fr', self::$polylang->links_model->get_language_from_url( home_url( '/fr' ) ) );

		$_SERVER['REQUEST_URI'] = '/test/';
		$this->assertEmpty( self::$polylang->links_model->get_language_from_url() );

		$_SERVER['REQUEST_URI'] = '/fr/test/';
		$this->assertEquals( 'fr', self::$polylang->links_model->get_language_from_url() );

		self::$polylang->options['rewrite'] = 0;
		$_SERVER['REQUEST_URI'] = '/language/fr/test/';
		$this->assertEquals( 'fr', self::$polylang->links_model->get_language_from_url() );

		$_SERVER = $server;
	}

	function test_home_url() {
		$this->assertEquals( $this->host . '/', self::$polylang->links_model->home_url( self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( $this->host . '/fr/', self::$polylang->links_model->home_url( self::$polylang->model->get_language( 'fr' ) ) );

		self::$polylang->options['rewrite'] = 0;
		$this->assertEquals( $this->host . '/', self::$polylang->links_model->home_url( self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( $this->host . '/language/fr/', self::$polylang->links_model->home_url( self::$polylang->model->get_language( 'fr' ) ) );
	}

	// Issue fixed in version 2.1.2
	function test_get_language_from_url_with_wrong_ssl() {
		$server = $_SERVER;

		$_SERVER['REQUEST_URI'] = '/fr/test/';
		$_SERVER['SERVER_PORT'] = 80;
		$this->assertEquals( 'fr', self::$polylang->links_model->get_language_from_url() );

		$_SERVER['SERVER_PORT'] = 443;
		$this->assertEquals( 'fr', self::$polylang->links_model->get_language_from_url() );

		$_SERVER = $server;
	}
}
