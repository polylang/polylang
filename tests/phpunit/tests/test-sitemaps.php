<?php

class Sitemaps_Test extends PLL_UnitTestCase {
	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	protected function init( $sitemap_class = 'PLL_Sitemaps' ) {
		global $wp_rewrite, $wp_sitemaps;

		// Initialize sitemaps.
		$wp_sitemaps = null;

		// Switch to pretty permalinks.
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // Brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( '/%postname%/' );

		create_initial_taxonomies();
		register_post_type( 'cpt', array( 'public' => true ) ); // *Untranslated* custom post type.
		register_taxonomy( 'tax', 'cpt' ); // *Untranslated* custom tax.

		$links_model = self::$model->get_links_model();
		if ( method_exists( $links_model, 'init' ) ) {
			$links_model->init();
		}

		$this->pll_env = new PLL_Frontend( $links_model );
		$this->pll_env->sitemaps = new $sitemap_class( $this->pll_env );
		$this->pll_env->sitemaps->init();

		wp_sitemaps_get_server(); // Allows to register sitemaps rewrite rules.

		// Refresh languages.
		self::$model->clean_languages_cache();
		self::$model->get_languages_list();

		// Flush rules.
		$wp_rewrite->flush_rules();

		// Goto front and setup filters.
		$this->pll_env->filters = new PLL_Frontend_Filters( $this->pll_env );
		$this->pll_env->filters_links = new PLL_Frontend_Filters_Links( $this->pll_env );
		$this->pll_env->filters_links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		$this->pll_env->filters_links->cache->method( 'get' )->willReturn( false );
	}

	public function tear_down() {
		parent::tear_down();

		_unregister_post_type( 'cpt' );
		_unregister_taxonomy( 'tax' );
	}

	public function test_sitemap_providers() {
		$this->init();

		$providers = wp_get_sitemap_providers();
		foreach ( $providers as $provider ) {
			$this->assertTrue( $provider instanceof PLL_Multilingual_Sitemaps_Provider );
		}
	}

	/**
	 * The page sitemaps always include the homepages.
	 */
	public function test_sitemaps_page() {
		$this->init();

		$providers = wp_get_sitemap_providers();

		$expected = array(
			'http://example.org/en/wp-sitemap-posts-page-1.xml',
			'http://example.org/fr/wp-sitemap-posts-page-1.xml',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['posts']->get_sitemap_entries(), 'loc' ) );
	}

	public function test_sitemaps_posts() {
		$this->init();

		$en = self::factory()->post->create( array( 'post_author' => 1 ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_author' => 1 ) );
		self::$model->post->set_language( $fr, 'fr' );

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

	public function test_sitemaps_untranslated_cpt_and_tax() {
		$this->init();

		self::factory()->term->create( array( 'taxonomy' => 'tax', 'name' => 'test' ) );
		$post_id = self::factory()->post->create( array( 'post_type' => 'cpt' ) );
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

	public function test_home_urls() {
		self::$model->options['hide_default'] = 1;
		$this->init();

		// For the home_url filter.
		$this->pll_env->links = new PLL_Frontend_Links( $this->pll_env );
		$GLOBALS['wp_actions']['template_redirect'] = 1;

		$providers = wp_get_sitemap_providers();

		$this->pll_env->curlang = self::$model->get_language( 'en' );
		$expected = array(
			'http://example.org/',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['posts']->get_url_list( 1, 'page' ), 'loc' ) );

		$this->pll_env->curlang = self::$model->get_language( 'fr' );
		$expected = array(
			'http://example.org/fr/',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['posts']->get_url_list( 1, 'page' ), 'loc' ) );

		unset( $GLOBALS['wp_actions']['template_redirect'] );
	}

	public function test_url_list_posts() {
		self::$model->options['hide_default'] = 1;
		$this->init();
		$this->pll_env->terms = new PLL_CRUD_Terms( $this->pll_env );

		$tag_en = self::factory()->tag->create( array( 'name' => 'tag-en' ) );
		self::$model->term->set_language( $tag_en, 'en' );

		$tag_fr = self::factory()->tag->create( array( 'name' => 'tag-fr' ) );
		self::$model->term->set_language( $tag_fr, 'fr' );

		$en = self::factory()->post->create( array( 'post_title' => 'test', 'post_author' => 1 ) );
		self::$model->post->set_language( $en, 'en' );
		wp_set_post_terms( $en, array( $tag_en ), 'post_tag' );


		$fr = self::factory()->post->create( array( 'post_title' => 'essai', 'post_author' => 1 ) );
		self::$model->post->set_language( $fr, 'fr' );
		wp_set_post_terms( $fr, array( $tag_fr ), 'post_tag' );

		$providers = wp_get_sitemap_providers();

		$this->pll_env->curlang = self::$model->get_language( 'en' );

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

		$this->pll_env->curlang = self::$model->get_language( 'fr' );

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

	public function test_subdomains() {
		self::$model->options['force_lang'] = 2;
		$this->init( 'PLL_Sitemaps_Domain' );

		$_SERVER['HTTP_HOST'] = 'fr.example.org';
		$_SERVER['REQUEST_URI'] = '/wp-sitemap.xml';

		$providers = wp_get_sitemap_providers();

		$expected = array(
			'http://fr.example.org/wp-sitemap-posts-page-1.xml',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['posts']->get_sitemap_entries(), 'loc' ) );
	}

	public function test_subdomains_home_url() {
		self::$model->options['force_lang'] = 2;
		$this->init( 'PLL_Sitemaps_Domain' );

		$_SERVER['HTTP_HOST'] = 'fr.example.org';
		$_SERVER['REQUEST_URI'] = '/wp-sitemap-posts-page-1.xml';

		// For the home_url filter.
		$this->pll_env->links = new PLL_Frontend_Links( $this->pll_env );
		$GLOBALS['wp_actions']['template_redirect'] = 1;

		$providers = wp_get_sitemap_providers();

		$this->pll_env->curlang = self::$model->get_language( 'fr' );
		$expected = array(
			'http://fr.example.org/',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['posts']->get_url_list( 1, 'page' ), 'loc' ) );

		unset( $GLOBALS['wp_actions']['template_redirect'] );
	}

	public function test_domains() {
		self::$model->options['force_lang'] = 3;
		self::$model->options['domains'] = array(
			'en' => 'http://example.org',
			'fr' => 'http://example.fr',
		);
		$this->init( 'PLL_Sitemaps_Domain' );

		$_SERVER['HTTP_HOST'] = 'example.fr';
		$_SERVER['REQUEST_URI'] = '/wp-sitemap.xml';

		$providers = wp_get_sitemap_providers();

		$expected = array(
			'http://example.fr/wp-sitemap-posts-page-1.xml',
		);
		$this->assertEqualSets( $expected, wp_list_pluck( $providers['posts']->get_sitemap_entries(), 'loc' ) );
	}
}
