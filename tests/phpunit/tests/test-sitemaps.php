<?php

class Sitemaps_Test extends PLL_UnitTestCase {
	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		// Sitemaps were introduced in WP 5.5.
		if ( ! function_exists( 'wp_get_sitemap_providers' ) ) {
			self::markTestSkipped( 'These tests require WP 5.5+' );
		}

		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once POLYLANG_DIR . '/include/api.php';
		$GLOBALS['polylang'] = &self::$polylang;
	}

	function init() {
		global $wp_rewrite, $wp_sitemaps;

		// Initialize sitemaps.
		$wp_sitemaps = null;

		// Switch to pretty permalinks.
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // Brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( '/%postname%/' );

		self::$polylang->model->post->register_taxonomy(); // Needs this for 'lang' query var.
		create_initial_taxonomies();
		register_post_type( 'cpt', array( 'public' => true ) ); // *Untranslated* custom post type.
		register_taxonomy( 'tax', 'cpt' ); // *Untranslated* custom tax.

		self::$polylang->links_model = self::$polylang->model->get_links_model();
		if ( method_exists( self::$polylang->links_model, 'init' ) ) {
			self::$polylang->links_model->init();
		}

		self::$polylang->sitemaps = new PLL_Sitemaps( self::$polylang );
		self::$polylang->sitemaps->init();

		wp_sitemaps_get_server(); // Allows to register sitemaps rewrite rules.

		// Flush rules.
		$wp_rewrite->flush_rules();

		// Goto front and setup filters.
		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		self::$polylang->filters = new PLL_Frontend_Filters( self::$polylang );
		self::$polylang->filters_links = new PLL_Frontend_Filters_Links( self::$polylang );
		self::$polylang->filters_links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		self::$polylang->filters_links->cache->method( 'get' )->willReturn( false );
	}

	function tearDown() {
		parent::tearDown();

		_unregister_post_type( 'cpt' );
		_unregister_taxonomy( 'tax' );
	}

	function test_sitemap_providers() {
		$this->init();

		$providers = wp_get_sitemap_providers();
		foreach ( $providers as $provider ) {
			$this->assertTrue( $provider instanceof PLL_Multilingual_Sitemaps_Provider );
		}
	}

	// The page sitemaps always include the homepages.
	function test_sitemaps_page() {
		$this->init();

		$providers = wp_get_sitemap_providers();

		$expected = array(
			'http://example.org/en/wp-sitemap-posts-page-1.xml',
			'http://example.org/fr/wp-sitemap-posts-page-1.xml',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['posts']->get_sitemap_entries(), 'loc' ) );
	}

	function test_sitemaps_posts() {
		$this->init();

		$en = self::factory()->post->create( array( 'post_author' => 1 ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_author' => 1 ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$providers = wp_get_sitemap_providers();

		$expected = array(
			'http://example.org/en/wp-sitemap-posts-post-1.xml',
			'http://example.org/fr/wp-sitemap-posts-post-1.xml',
			'http://example.org/en/wp-sitemap-posts-page-1.xml',
			'http://example.org/fr/wp-sitemap-posts-page-1.xml',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['posts']->get_sitemap_entries(), 'loc' ) );

		$expected = array(
			'http://example.org/en/wp-sitemap-users-1.xml',
			'http://example.org/fr/wp-sitemap-users-1.xml',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['users']->get_sitemap_entries(), 'loc' ) );

		$expected = array(
			'http://example.org/en/wp-sitemap-taxonomies-category-1.xml',
			'http://example.org/fr/wp-sitemap-taxonomies-category-1.xml',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['taxonomies']->get_sitemap_entries(), 'loc' ) );
	}

	function test_sitemaps_untranslated_cpt_and_tax() {
		$this->init();

		$term_id = $this->factory->term->create( array( 'taxonomy' => 'tax', 'name' => 'test' ) );
		$post_id = $this->factory->post->create( array( 'post_type' => 'cpt' ) );
		wp_set_post_terms( $post_id, 'test', 'tax' );

		$providers = wp_get_sitemap_providers();

		$expected = array(
			'http://example.org/wp-sitemap-posts-cpt-1.xml',
			'http://example.org/en/wp-sitemap-posts-page-1.xml',
			'http://example.org/fr/wp-sitemap-posts-page-1.xml',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['posts']->get_sitemap_entries(), 'loc' ) );

		$expected = array(
			'http://example.org/wp-sitemap-taxonomies-tax-1.xml',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['taxonomies']->get_sitemap_entries(), 'loc' ) );
	}

	function test_home_urls() {
		self::$polylang->options['hide_default'] = 1;
		$this->init();

		// For the home_url filter.
		self::$polylang->links = new PLL_Frontend_Links( self::$polylang );
		$GLOBALS['wp_actions']['template_redirect'] = 1;

		$providers = wp_get_sitemap_providers();

		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );
		$expected = array(
			'http://example.org/',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['posts']->get_url_list( 1, 'page' ), 'loc' ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		$expected = array(
			'http://example.org/fr/',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['posts']->get_url_list( 1, 'page' ), 'loc' ) );

		unset( $GLOBALS['wp_actions']['template_redirect'] );
	}

	function test_url_list_posts() {
		self::$polylang->options['hide_default'] = 1;
		$this->init();
		self::$polylang->terms = new PLL_CRUD_Terms( self::$polylang );

		$tag_en = self::factory()->tag->create( array( 'name' => 'tag-en' ) );
		self::$polylang->model->term->set_language( $tag_en, 'en' );

		$tag_fr = self::factory()->tag->create( array( 'name' => 'tag-fr' ) );
		self::$polylang->model->term->set_language( $tag_fr, 'fr' );

		$en = self::factory()->post->create( array( 'post_title' => 'test', 'post_author' => 1 ) );
		self::$polylang->model->post->set_language( $en, 'en' );
		wp_set_post_terms( $en, array( $tag_en ), 'post_tag' );


		$fr = self::factory()->post->create( array( 'post_title' => 'essai', 'post_author' => 1 ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );
		wp_set_post_terms( $fr, array( $tag_fr ), 'post_tag' );

		$providers = wp_get_sitemap_providers();

		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );

		$expected = array(
			'http://example.org/test/',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['posts']->get_url_list( 1, 'post' ), 'loc' ) );

		$expected = array(
			'http://example.org/author/admin/',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['users']->get_url_list( 1 ), 'loc' ) );

		$expected = array(
			'http://example.org/tag/tag-en/',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['taxonomies']->get_url_list( 1, 'post_tag' ), 'loc' ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );

		$expected = array(
			'http://example.org/fr/essai/',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['posts']->get_url_list( 1, 'post' ), 'loc' ) );

		$expected = array(
			'http://example.org/fr/author/admin/',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['users']->get_url_list( 1 ), 'loc' ) );

		$expected = array(
			'http://example.org/fr/tag/tag-fr/',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['taxonomies']->get_url_list( 1, 'post_tag' ), 'loc' ) );
	}

	function test_subdomains() {
		self::$polylang->options['force_lang'] = 2;
		$this->init();

		$_SERVER['HTTP_HOST'] = 'fr.example.org';
		$_SERVER['REQUEST_URI'] = '/wp-sitemap.xml';

		$providers = wp_get_sitemap_providers();

		$expected = array(
			'http://fr.example.org/wp-sitemap-posts-page-1.xml',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['posts']->get_sitemap_entries(), 'loc' ) );
	}

	function test_subdomains_home_url() {
		self::$polylang->options['force_lang'] = 2;
		$this->init();

		$_SERVER['HTTP_HOST'] = 'fr.example.org';
		$_SERVER['REQUEST_URI'] = '/wp-sitemap-posts-page-1.xml';

		// For the home_url filter.
		self::$polylang->links = new PLL_Frontend_Links( self::$polylang );
		$GLOBALS['wp_actions']['template_redirect'] = 1;

		$providers = wp_get_sitemap_providers();

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		$expected = array(
			'http://fr.example.org/',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['posts']->get_url_list( 1, 'page' ), 'loc' ) );

		unset( $GLOBALS['wp_actions']['template_redirect'] );
	}

	function test_domains() {
		self::$polylang->options['force_lang'] = 3;
		self::$polylang->options['domains'] = array(
			'en' => 'http://example.org',
			'fr' => 'http://example.fr',
		);
		$this->init();

		$_SERVER['HTTP_HOST'] = 'example.fr';
		$_SERVER['REQUEST_URI'] = '/wp-sitemap.xml';

		$providers = wp_get_sitemap_providers();

		$expected = array(
			'http://example.fr/wp-sitemap-posts-page-1.xml',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['posts']->get_sitemap_entries(), 'loc' ) );
	}
}
