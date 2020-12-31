<?php

class Choose_Lang_Domain_Test extends PLL_UnitTestCase {
	public $structure = '/%postname%/';

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once POLYLANG_DIR . '/include/api.php';
		$GLOBALS['polylang'] = &self::$polylang;
	}

	function setUp() {
		parent::setUp();

		global $wp_rewrite;

		$this->server = $_SERVER; // save this

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
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );

		self::$polylang->model->post->register_taxonomy(); // needs this for 'lang' query var
		create_initial_taxonomies();

		self::$polylang->links_model = self::$polylang->model->get_links_model();

		// flush rules
		$wp_rewrite->flush_rules();

		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
	}

	function tearDown() {
		parent::tearDown();

		$_SERVER = $this->server;
	}

	// overrides WP_UnitTestCase::go_to
	function go_to( $url ) {
		// copy paste of WP_UnitTestCase::go_to
		$_GET = $_POST = array();
		foreach ( array( 'query_string', 'id', 'postdata', 'authordata', 'day', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages', 'pagenow' ) as $v ) {
			if ( isset( $GLOBALS[ $v ] ) ) {
				unset( $GLOBALS[ $v ] );
			}
		}
		$parts = wp_parse_url( $url );
		if ( isset( $parts['scheme'] ) ) {
			$req = isset( $parts['path'] ) ? $parts['path'] : '';
			if ( isset( $parts['query'] ) ) {
				$req .= '?' . $parts['query'];
				// parse the url query vars into $_GET
				parse_str( $parts['query'], $_GET );
			}
		} else {
			$req = $url;
		}
		if ( ! isset( $parts['query'] ) ) {
			$parts['query'] = '';
		}

		$_SERVER['REQUEST_URI'] = $req;
		unset( $_SERVER['PATH_INFO'] );

		$this->flush_cache();
		unset( $GLOBALS['wp_query'], $GLOBALS['wp_the_query'] );

		// insert Polylang specificity
		unset( $GLOBALS['wp_actions']['pll_language_defined'] );
		unset( self::$polylang->curlang );
		$_SERVER['HTTP_HOST'] = wp_parse_url( $url, PHP_URL_HOST );
		self::$polylang->init();

		// restart copy paste of WP_UnitTestCase::go_to
		$GLOBALS['wp_the_query'] = new WP_Query();
		$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'];
		$GLOBALS['wp'] = new WP();
		_cleanup_query_vars();

		$GLOBALS['wp']->main( $parts['query'] );
	}

	function test_home_latest_posts() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->go_to( $this->hosts['fr'] );
		$this->assertEquals( 'fr', self::$polylang->curlang->slug );
		$this->assertQueryTrue( 'is_home', 'is_front_page' );
		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // bug introduced in 1.8.0.1, fixed in 1.8.0.2
		$this->assertEquals( trailingslashit( $this->hosts['en'] ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );

		$this->go_to( $this->hosts['en'] );
		$this->assertEquals( 'en', self::$polylang->curlang->slug );
		$this->assertQueryTrue( 'is_home', 'is_front_page' );
		$this->assertEquals( array( get_post( $en ) ), $GLOBALS['wp_query']->posts );
		$this->assertEquals( trailingslashit( $this->hosts['fr'] ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
	}

	function test_single_post() {
		$en = $this->factory->post->create( array( 'post_title' => 'test' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->go_to( $this->hosts['fr'] . '/essai/' );
		$this->assertEquals( 'fr', self::$polylang->curlang->slug );

		$this->go_to( $this->hosts['en'] . '/test/' );
		$this->assertEquals( 'en', self::$polylang->curlang->slug );
	}
}
