<?php

class Canonical_Test extends PLL_Canonical_UnitTestCase {
	public $structure = '/%postname%/';

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once PLL_INC . '/api.php';
		$GLOBALS['polylang'] = &self::$polylang;
		self::$polylang->options['hide_default'] = 0;
	}

	function setUp() {
		parent::setUp();

		global $wp_rewrite;

		self::$polylang->options['post_types'] = array(
			'cpt' => 'cpt', // translate the cpt // FIXME /!\ 'after_setup_theme' already fired and the list of translated post types is already cached :(
		);

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $this->structure );

		// register post types and taxonomies
		self::$polylang->model->post->register_taxonomy(); // needs this for 'lang' query var
		create_initial_taxonomies();
		register_post_type( 'cpt', array( 'public' => true ) ); // add custom post type

		// reset the links model according to the permalink structure
		self::$polylang->links_model = self::$polylang->model->get_links_model();
		self::$polylang->links_model->init();

		// flush rules
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		self::$polylang->filters_links = new PLL_Frontend_Filters_Links( self::$polylang );
	}

	function tearDown() {
		parent::tearDown();

		_unregister_post_type( 'cpt' );
	}

	function test_post() {
		$post_id = $this->factory->post->create( array( 'post_title' => 'post-format-test-audio' ) );
		self::$polylang->model->post->set_language( $post_id, 'en' );
		$this->assertCanonical(
			'/en/post-format-test-audio/',
			array(
				'url' => '/en/post-format-test-audio/',
				'qv' => array( 'lang' => 'en', 'name' => 'post-format-test-audio', 'page' => '' ),
			)
		);
		$this->assertCanonical( '/fr/post-format-test-audio/', '/en/post-format-test-audio/' );
		$this->assertCanonical( '/post-format-test-audio/', '/en/post-format-test-audio/' );
	}

	function test_page() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'parent-page' ) );
		self::$polylang->model->post->set_language( $post_id, 'en' );

		$this->assertCanonical(
			'/en/parent-page/',
			array(
				'url' => '/en/parent-page/',
				'qv' => array( 'lang' => 'en', 'pagename' => 'parent-page', 'page' => '' ),
			)
		);
		$this->assertCanonical( '/fr/parent-page/', '/en/parent-page/' );
		$this->assertCanonical( '/parent-page/', '/en/parent-page/' );
	}

	function test_cpt() {
		// custom post type
		$post_id = $this->factory->post->create( array( 'import_id' => 416, 'post_type' => 'cpt', 'post_title' => 'custom-post' ) );
		self::$polylang->model->post->set_language( $post_id, 'en' );

		$this->assertCanonical(
			'/en/cpt/custom-post/',
			array(
				'url' => '/en/cpt/custom-post/',
				'qv' => array( 'lang' => 'en', 'cpt' => 'custom-post', 'name' => 'custom-post', 'post_type' => 'cpt', 'page' => '' ),
			)
		);
		$this->assertCanonical( '/fr/cpt/custom-post/', '/en/cpt/custom-post/' );
		$this->assertCanonical( '/cpt/custom-post/', '/en/cpt/custom-post/' );
	}

	function test_category() {
		$term_id = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'parent' ) );
		self::$polylang->model->term->set_language( $term_id, 'en' );

		$term_id = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'parent-fr' ) );
		self::$polylang->model->term->set_language( $term_id, 'fr' );

		$this->assertCanonical(
			'/en/category/parent/',
			array(
				'url' => '/en/category/parent/',
				'qv' => array( 'lang' => 'en', 'category_name' => 'parent' ),
			)
		);
		$this->assertCanonical( '/fr/category/parent/', '/en/category/parent/' );
		$this->assertCanonical( '/category/parent/', '/en/category/parent/' );

		$this->assertCanonical( '/en/category/parent-fr/', '/fr/category/parent-fr/' );
		$this->assertCanonical( '/category/parent-fr/', '/fr/category/parent-fr/' );
	}

	function test_posts_page() {
		self::$polylang->static_pages = new PLL_Admin_Static_Pages( self::$polylang );
		update_option( 'show_on_front', 'page' );

		$this->posts_en = $en = $this->factory->post->create( array( 'post_title' => 'posts', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$this->posts_fr = $fr = $this->factory->post->create( array( 'post_title' => 'articles', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		update_option( 'page_for_posts', $fr );

		// go to frontend
		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		self::$polylang->init();

		self::$polylang->static_pages = new PLL_Frontend_Static_Pages( self::$polylang );
		self::$polylang->static_pages->pll_language_defined();

		$this->assertCanonical(
			'/en/posts/',
			array(
				'url' => '/en/posts/',
				'qv' => array( 'lang' => 'en', 'pagename' => 'posts', 'page' => '' ),
			)
		);
		$this->assertCanonical( '/fr/posts/', '/en/posts/' );
		$this->assertCanonical( '/posts/', '/en/posts/' );

		$this->assertCanonical( '/en/articles/', '/fr/articles/' );
		$this->assertCanonical( '/articles/', '/fr/articles/' );
	}

	// bug introduced in 1.8.2 and fixed in 1.8.3
	function test_page_when_static_front_page_displays_posts() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'parent-page' ) );
		self::$polylang->model->post->set_language( $post_id, 'en' );

		self::$polylang->static_pages = new PLL_Admin_Static_Pages( self::$polylang );
		update_option( 'show_on_front', 'posts' );

		// go to frontend
		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		self::$polylang->init();

		self::$polylang->static_pages = new PLL_Frontend_Static_Pages( self::$polylang );
		self::$polylang->static_pages->pll_language_defined();

		$this->assertCanonical(
			'/en/parent-page/',
			array(
				'url' => '/en/parent-page/',
				'qv' => array( 'lang' => 'en', 'pagename' => 'parent-page', 'page' => '' ),
			)
		);
		$this->assertCanonical( '/fr/parent-page/', '/en/parent-page/' );
		$this->assertCanonical( '/parent-page/', '/en/parent-page/' );
	}
}
