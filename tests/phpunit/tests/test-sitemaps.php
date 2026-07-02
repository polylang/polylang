<?php

class Sitemaps_Test extends PLL_UnitTestCase {
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		$factory->language->create_many( 2 );
	}

	protected function init( $sitemap_class = 'PLL_Sitemaps', array $options = array() ): void {
		global $wp_rewrite, $wp_sitemaps;

		// To avoid a redirect due to the 'hide_default' option during the context setup.
		add_filter( 'pll_redirect_home', '__return_false' );
		add_filter( 'pll_check_canonical_url', '__return_false' );

		// Initialize sitemaps.
		$wp_sitemaps = null;

		// Switch to pretty permalinks.
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // Brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( '/%postname%/' );

		create_initial_taxonomies();
		// Use a custom post type and a taxonomy whose slug ends with a language slug. See https://github.com/polylang/polylang-pro/issues/3017.
		register_post_type( 'cpten', array( 'public' => true ) ); // *Untranslated* custom post type.
		register_taxonomy( 'tax-en', 'cpten' ); // *Untranslated* custom tax.

		$options = array_merge(
			array(
				'default_lang' => 'en',
				'hide_default' => false,
				'force_lang'   => 1,
			),
			$options
		);
		$this->pll_env = ( new PLL_Context_Frontend( array( 'options' => $options ) ) )->get();

		$this->pll_env->sitemaps = new $sitemap_class( $this->pll_env );
		$this->pll_env->sitemaps->init();

		wp_sitemaps_get_server(); // Allows to register sitemaps rewrite rules.

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

		_unregister_post_type( 'cpten' );
		_unregister_taxonomy( 'tax-en' );
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
		$this->assertSameSets( $expected, wp_list_pluck( $providers['posts']->get_sitemap_entries(), 'loc' ) );
	}

	public function test_sitemaps_posts() {
		$this->init();

		$post_en = self::factory()->post->create( array( 'post_author' => 1, 'lang' => 'en' ) );
		$cat_en  = self::factory()->category->create( array( 'lang' => 'en' ) );
		wp_set_post_terms( $post_en, array( $cat_en ), 'category' );

		$post_fr = self::factory()->post->create( array( 'post_author' => 1, 'lang' => 'fr' ) );
		$cat_fr  = self::factory()->category->create( array( 'lang' => 'fr' ) );
		wp_set_post_terms( $post_fr, array( $cat_fr ), 'category' );

		$providers = wp_get_sitemap_providers();

		$expected = array(
			'http://example.org/en/wp-sitemap-posts-post-1.xml',
			'http://example.org/fr/wp-sitemap-posts-post-1.xml',
			'http://example.org/en/wp-sitemap-posts-page-1.xml',
			'http://example.org/fr/wp-sitemap-posts-page-1.xml',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['posts']->get_sitemap_entries(), 'loc' ) );

		$expected = array(
			'http://example.org/en/wp-sitemap-users-1.xml',
			'http://example.org/fr/wp-sitemap-users-1.xml',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['users']->get_sitemap_entries(), 'loc' ) );

		$expected = array(
			'http://example.org/en/wp-sitemap-taxonomies-category-1.xml',
			'http://example.org/fr/wp-sitemap-taxonomies-category-1.xml',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['taxonomies']->get_sitemap_entries(), 'loc' ) );
	}

	public function test_sitemaps_untranslated_cpt_and_tax() {
		$this->init();

		self::factory()->term->create( array( 'taxonomy' => 'tax-en', 'name' => 'test' ) );
		$post_id = self::factory()->post->create( array( 'post_type' => 'cpten' ) );
		wp_set_post_terms( $post_id, 'test', 'tax-en' );

		$providers = wp_get_sitemap_providers();

		$expected = array(
			'http://example.org/wp-sitemap-posts-cpten-1.xml',
			'http://example.org/en/wp-sitemap-posts-page-1.xml',
			'http://example.org/fr/wp-sitemap-posts-page-1.xml',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['posts']->get_sitemap_entries(), 'loc' ) );

		$expected = array(
			'http://example.org/wp-sitemap-taxonomies-tax-en-1.xml',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['taxonomies']->get_sitemap_entries(), 'loc' ) );
	}

	public function test_home_urls() {
		$this->init( 'PLL_Sitemaps', array( 'hide_default' => true ) );

		// For the home_url filter.
		$this->pll_env->links = new PLL_Frontend_Links( $this->pll_env );
		$GLOBALS['wp_actions']['template_redirect'] = 1;

		$providers = wp_get_sitemap_providers();

		$this->pll_env->curlang = $this->pll_env->model->get_language( 'en' );
		$expected = array(
			'http://example.org/',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['posts']->get_url_list( 1, 'page' ), 'loc' ) );

		$this->pll_env->curlang = $this->pll_env->model->get_language( 'fr' );
		$expected = array(
			'http://example.org/fr/',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['posts']->get_url_list( 1, 'page' ), 'loc' ) );

		unset( $GLOBALS['wp_actions']['template_redirect'] );
	}

	public function test_url_list_posts() {
		$this->init( 'PLL_Sitemaps', array( 'hide_default' => true ) );

		$this->pll_env->terms = new PLL_CRUD_Terms( $this->pll_env );

		$tag_en = self::factory()->tag->create( array( 'name' => 'tag-en', 'lang' => 'en' ) );
		$tag_fr = self::factory()->tag->create( array( 'name' => 'tag-fr', 'lang' => 'fr' ) );

		$en = self::factory()->post->create( array( 'post_title' => 'test', 'post_author' => 1, 'lang' => 'en' ) );
		wp_set_post_terms( $en, array( $tag_en ), 'post_tag' );

		$fr = self::factory()->post->create( array( 'post_title' => 'essai', 'post_author' => 1, 'lang' => 'fr' ) );
		wp_set_post_terms( $fr, array( $tag_fr ), 'post_tag' );

		$providers = wp_get_sitemap_providers();

		$this->pll_env->curlang = $this->pll_env->model->get_language( 'en' );

		$expected = array(
			'http://example.org/test/',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['posts']->get_url_list( 1, 'post' ), 'loc' ) );

		$expected = array(
			'http://example.org/author/admin/',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['users']->get_url_list( 1 ), 'loc' ) );

		$expected = array(
			'http://example.org/tag/tag-en/',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['taxonomies']->get_url_list( 1, 'post_tag' ), 'loc' ) );

		$this->pll_env->curlang = $this->pll_env->model->get_language( 'fr' );

		$expected = array(
			'http://example.org/fr/essai/',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['posts']->get_url_list( 1, 'post' ), 'loc' ) );

		$expected = array(
			'http://example.org/fr/author/admin/',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['users']->get_url_list( 1 ), 'loc' ) );

		$expected = array(
			'http://example.org/fr/tag/tag-fr/',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['taxonomies']->get_url_list( 1, 'post_tag' ), 'loc' ) );
	}

	public function test_subdomains() {
		$this->init( 'PLL_Sitemaps_Domain', array( 'force_lang' => 2 ) );

		$_SERVER['HTTP_HOST'] = 'fr.example.org';
		$_SERVER['REQUEST_URI'] = '/wp-sitemap.xml';

		$providers = wp_get_sitemap_providers();

		$expected = array(
			'http://fr.example.org/wp-sitemap-posts-page-1.xml',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['posts']->get_sitemap_entries(), 'loc' ) );
	}

	public function test_subdomains_home_url() {
		$this->init( 'PLL_Sitemaps_Domain', array( 'force_lang' => 2 ) );

		$_SERVER['HTTP_HOST'] = 'fr.example.org';
		$_SERVER['REQUEST_URI'] = '/wp-sitemap-posts-page-1.xml';

		// For the home_url filter.
		$this->pll_env->links = new PLL_Frontend_Links( $this->pll_env );
		$GLOBALS['wp_actions']['template_redirect'] = 1;

		$providers = wp_get_sitemap_providers();

		$this->pll_env->curlang = $this->pll_env->model->get_language( 'fr' );
		$expected = array(
			'http://fr.example.org/',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['posts']->get_url_list( 1, 'page' ), 'loc' ) );

		unset( $GLOBALS['wp_actions']['template_redirect'] );
	}

	public function test_domains() {
		$_SERVER['HTTP_HOST'] = 'example.fr';
		$_SERVER['REQUEST_URI'] = '/wp-sitemap.xml';

		$this->init(
			'PLL_Sitemaps_Domain',
			array(
				'force_lang' => 3,
				'domains'    => array(
					'en' => 'http://example.org',
					'fr' => 'http://example.fr',
				),
			)
		);

		$providers = wp_get_sitemap_providers();

		$expected = array(
			'http://example.fr/wp-sitemap-posts-page-1.xml',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['posts']->get_sitemap_entries(), 'loc' ) );
	}

	public function test_users() {
		$this->init();

		self::factory()->post->create( array( 'post_author' => 1, 'lang' => 'en' ) );
		self::factory()->post->create( array( 'post_author' => 1, 'lang' => 'fr' ) );

		$providers = wp_get_sitemap_providers();

		$expected = array(
			'http://example.org/en/wp-sitemap-users-1.xml',
			'http://example.org/fr/wp-sitemap-users-1.xml',
		);
		$this->assertSameSets( $expected, wp_list_pluck( $providers['users']->get_sitemap_entries(), 'loc' ) );
	}

	public function test_set_language_from_query() {
		// Arrange
		$this->init();
		$query_without_lang = (object) array(
			'query' => array(
				'sitemap' => 'posts',
				'lang'    => '',
			),
		);
		$query_with_lang = (object) array(
			'query' => array(
				'sitemap' => 'posts',
				'lang'    => 'fr',
			),
		);
		$empty_query = (object) array(
			'query' => array(),
		);
		$lang = $this->pll_env->model->get_language( 'fr' );
		$lang_false = false;
		$expected_default_lang = $this->pll_env->model->get_language( 'en' );

		// Act
		$actual_default_lang = $this->pll_env->sitemaps->set_language_from_query( $lang, $query_without_lang );
		$actual_lang = $this->pll_env->sitemaps->set_language_from_query( $lang, $query_with_lang );
		$actual_empty = $this->pll_env->sitemaps->set_language_from_query( $lang, $empty_query );
		$actual_false = $this->pll_env->sitemaps->set_language_from_query( $lang_false, $query_without_lang );

		// Assert
		$this->assertSame( $expected_default_lang->slug, $actual_default_lang->slug );
		$this->assertSame( $lang->slug, $actual_lang->slug );
		$this->assertSame( $lang->slug, $actual_empty->slug );
		$this->assertSame( $expected_default_lang->slug, $actual_false->slug );
	}
}
