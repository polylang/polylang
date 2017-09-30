<?php

class Choose_Lang_Content_Test extends PLL_UnitTestCase {
	public $structure = '/%postname%/';

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once PLL_INC . '/api.php';
		$GLOBALS['polylang'] = &self::$polylang;
	}

	function setUp() {
		parent::setUp();

		global $wp_rewrite;

		self::$polylang->options['hide_default'] = 1;
		self::$polylang->options['force_lang'] = 0;
		self::$polylang->options['browser'] = 0;

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );

		self::$polylang->model->post->register_taxonomy(); // needs this for 'lang' query var
		create_initial_taxonomies();

		self::$polylang->links_model = self::$polylang->model->get_links_model();
		self::$polylang->links_model->init();

		// flush rules
		$wp_rewrite->flush_rules();

		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
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
		$parts = parse_url( $url );
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
		self::$polylang->init();

		// restart copy paste of WP_UnitTestCase::go_to
		$GLOBALS['wp_the_query'] = new WP_Query();
		$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'];
		$GLOBALS['wp'] = new WP();
		_cleanup_query_vars();

		$GLOBALS['wp']->main( $parts['query'] );
	}

	function test_home_latest_posts() {
		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/fr/' ) );
		$this->assertEquals( 'fr' , self::$polylang->curlang->slug );
	}

	function test_home_latest_posts_with_hide_default() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		$this->go_to( home_url( '/' ) );
		$this->assertEquals( 'en' , self::$polylang->curlang->slug );
	}

	function test_single_post() {
		$en = $this->factory->post->create( array( 'post_title' => 'test' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/essai/' ) );
		$this->assertEquals( 'fr' , self::$polylang->curlang->slug );

		$this->go_to( home_url( '/test/' ) );
		$this->assertEquals( 'en' , self::$polylang->curlang->slug );
	}

	function test_page() {
		$en = $this->factory->post->create( array( 'post_title' => 'test', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/essai/' ) );
		$this->assertEquals( 'fr' , self::$polylang->curlang->slug );

		$this->go_to( home_url( '/test/' ) );
		$this->assertEquals( 'en' , self::$polylang->curlang->slug );
	}

	function test_category() {
		$fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$this->go_to( home_url( '/category/essai/' ) );
		$this->assertEquals( 'fr' , self::$polylang->curlang->slug );

		$this->go_to( home_url( '/category/test/' ) );
		$this->assertEquals( 'en' , self::$polylang->curlang->slug );
	}

	function test_post_tag() {
		$en = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'essai' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/tag/essai/' ) );
		$this->assertEquals( 'fr' , self::$polylang->curlang->slug );

		$this->go_to( home_url( '/tag/test/' ) );
		$this->assertEquals( 'en' , self::$polylang->curlang->slug );
	}

	function test_archive() {
		$en = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/fr/2007/' ) );
		$this->assertEquals( 'fr' , self::$polylang->curlang->slug );

		$this->go_to( home_url( '/2007/' ) );
		$this->assertEquals( 'en' , self::$polylang->curlang->slug );
	}

	function test_archive_with_default_permalinks() {
		$GLOBALS['wp_rewrite']->set_permalink_structure( '' );

		$en = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '?year=2007&lang=fr' ) );
		$this->assertEquals( 'fr' , self::$polylang->curlang->slug );

		$this->go_to( home_url( '?year=2007' ) );
		$this->assertEquals( 'en' , self::$polylang->curlang->slug );
	}
}
