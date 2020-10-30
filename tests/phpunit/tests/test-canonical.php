<?php

class Canonical_Test extends PLL_Canonical_UnitTestCase {
	public $structure = '/%postname%/';

	private static $post_en;
	private static $page_id;
	private static $custom_post_id;
	private static $term_en;
	private static $page_for_posts_en;
	private static $page_for_posts_fr;
	private static $page_on_front_en;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once POLYLANG_DIR . '/include/api.php';

		self::generate_shared_fixtures( $factory );
	}

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function generate_shared_fixtures( $factory ) {
		self::$post_en = $factory->post->create( array( 'post_title' => 'post-format-test-audio' ) );
		self::$polylang->model->post->set_language( self::$post_en, 'en' );

		self::$page_id = $factory->post->create( array( 'post_type' => 'page', 'post_title' => 'parent-page' ) );
		self::$polylang->model->post->set_language( self::$page_id, 'en' );

		add_action(
			'registered_taxonomy',
			function( $taxonomy ) {
				if ( 'post_format' === $taxonomy && ! post_type_exists( 'pllcanonical' ) ) { // Last taxonomy registered in {@see https://github.com/WordPress/wordpress-develop/blob/36ef9cbca96fca46e7daf1ee687bb6a20788385c/src/wp-includes/taxonomy.php#L158-L174 create_initial_taxonomies()}
					register_post_type( 'pllcanonical', array( 'public' => true ) );
				}
			}
		);
		self::$custom_post_id = $factory->post->create(
			array(
				'import_id'  => 416,
				'post_type'  => 'pllcanonical',
				'post_title' => 'custom-post',
			)
		);
		self::$polylang->model->post->set_language( self::$custom_post_id, 'en' );

		self::$term_en = $factory->term->create( array( 'taxonomy' => 'category', 'name' => 'parent' ) );
		self::$polylang->model->term->set_language( self::$term_en, 'en' );

		self::$polylang->static_pages = new PLL_Admin_Static_Pages( self::$polylang );
		update_option( 'show_on_front', 'page' );

		$en = self::$page_for_posts_en = $factory->post->create( array( 'post_title' => 'posts', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( self::$page_for_posts_en, 'en' );

		$fr = self::$page_for_posts_fr = $factory->post->create( array( 'post_title' => 'articles', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( self::$page_for_posts_fr, 'fr' );

		self::$polylang->model->post->save_translations( self::$page_for_posts_en, compact( 'en', 'fr' ) );

		update_option( 'page_for_posts', self::$page_for_posts_fr );

		self::$page_on_front_en = $factory->post->create( array( 'post_type' => 'page', 'post_title' => 'home' ) );

		self::$polylang->model->post->set_language( self::$page_on_front_en, 'en' );

		self::$polylang->static_pages = new PLL_Admin_Static_Pages( self::$polylang );
		update_option( 'show_on_front', 'posts' );

		// Translated cpt and tax
		register_post_type( 'trcpt', array( 'public' => true ) );
		register_taxonomy( 'trtax', 'trcpt' );

		$trcpt_en = $factory->post->create( array( 'post_type' => 'trcpt' ) );
		self::$polylang->model->post->set_language( $trcpt_en, 'en' );
		$trcpt_fr = $factory->post->create( array( 'post_type' => 'trcpt' ) );
		self::$polylang->model->post->set_language( $trcpt_fr, 'fr' );

		$trtax_en = $factory->term->create( array( 'taxonomy' => 'trtax', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $trtax_en, 'en' );

		wp_set_post_terms( $trcpt_en, 'test', 'trtax' );


		// Untranslated cpt and tax
		register_post_type( 'cpt', array( 'public' => true ) );
		register_taxonomy( 'tax', 'cpt' );

		$cpt = $factory->post->create( array( 'post_type' => 'cpt' ) );
		$factory->term->create( array( 'taxonomy' => 'tax', 'name' => 'test' ) );

		wp_set_post_terms( $cpt, 'test', 'tax' );
	}

	public function init_for_sitemaps() {
		if ( ! function_exists( 'wp_get_sitemap_providers' ) ) {
			self::markTestSkipped( 'This test requires WP 5.5+' );
		}

		self::$polylang->links_model = self::$polylang->model->get_links_model();
		if ( method_exists( self::$polylang->links_model, 'init' ) ) {
			self::$polylang->links_model->init();
		}

		self::$polylang->sitemaps = new PLL_Sitemaps( self::$polylang );
		self::$polylang->sitemaps->init();

		wp_sitemaps_get_server(); // Allows to register sitemaps rewrite rules.
	}

	public static function wpTearDownAfterClass() {
		_unregister_post_type( 'pllcanonical' );

		parent::wpTearDownAfterClass();
	}

	public function setUp() {
		parent::setUp();

		$GLOBALS['polylang'] = &$this->pll_env;

		$this->options = array_merge(
			PLL_Install::get_default_options(),
			array(
				'default_lang' => 'en',
				'hide_default' => 0,
				'post_types'   => array(
					'cpt' => 'pllcanonical',
					// translate the cpt // FIXME /!\ 'after_setup_theme' already fired and the list of translated post types is already cached :(
				),
			)
		);
	}

	public function test_post_with_name_and_language() {
		$this->assertCanonical(
			'/en/post-format-test-audio/',
			array(
				'url' => '/en/post-format-test-audio/',
				'qv'  => array( 'lang' => 'en', 'name' => 'post-format-test-audio', 'page' => '' ),
			)
		);
	}

	public function test_post_with_incorrect_language() {
		$this->assertCanonical( '/fr/post-format-test-audio/', '/en/post-format-test-audio/' );
	}

	public function test_post_without_language() {
		$this->assertCanonical( '/post-format-test-audio/', '/en/post-format-test-audio/' );
	}

	public function test_post_from_plain_permalink() {
		$this->assertCanonical( '?p=' . self::$post_en, '/en/post-format-test-audio/' );
	}

	public function test_page_with_name_and_language() {
		$this->assertCanonical(
			'/en/parent-page/',
			array(
				'url' => '/en/parent-page/',
				'qv'  => array( 'lang' => 'en', 'pagename' => 'parent-page', 'page' => '' ),
			)
		);
	}

	public function test_page_with_incorrect_language() {
		$this->assertCanonical( '/fr/parent-page/', '/en/parent-page/' );
	}

	public function test_page_without_language() {
		$this->assertCanonical( '/parent-page/', '/en/parent-page/' );
	}

	public function test_page_from_plain_permalink() {
		$this->assertCanonical( '?page_id=' . self::$page_id, '/en/parent-page/' );
	}

	public function test_custom_post_type_with_name_and_language() {
		$this->assertCanonical(
			'/en/pllcanonical/custom-post/',
			array(
				'url' => '/en/pllcanonical/custom-post/',
				'qv'  => array( 'lang' => 'en', 'pllcanonical' => 'custom-post', 'name' => 'custom-post', 'post_type' => 'pllcanonical', 'page' => '' ),
			)
		);
	}

	public function test_custom_post_type_with_incorrect_language() {
		$this->assertCanonical( '/fr/pllcanonical/custom-post/', '/en/pllcanonical/custom-post/' );
	}


	public function test_custom_post_type_without_language() {
		$this->assertCanonical( '/pllcanonical/custom-post/', '/en/pllcanonical/custom-post/' );
	}

	public function test_category_with_name_and_language() {
		$this->assertCanonical(
			'/en/category/parent/',
			array(
				'url' => '/en/category/parent/',
				'qv'  => array( 'lang' => 'en', 'category_name' => 'parent' ),
			)
		);
	}

	public function test_category_with_incorrect_language() {
		$this->assertCanonical( '/fr/category/parent/', '/en/category/parent/' );
	}

	public function test_category_without_language() {
		$this->assertCanonical( '/category/parent/', '/en/category/parent/' );
	}

	public function test_category_from_plain_permalink() {
		$this->assertCanonical( '?cat=' . self::$term_en, '/en/category/parent/' );
	}

	public function test_page_for_posts_with_name_and_language() {
		$this->assertCanonical(
			'/en/posts/',
			array(
				'url' => '/en/posts/',
				'qv'  => array( 'lang' => 'en', 'pagename' => 'posts', 'page' => '' ),
			)
		);
	}

	public function test_page_for_posts_should_match_page_for_post_option_when_language_is_incorrect() {
		$this->assertCanonical( '/fr/posts/', '/en/posts/' );
	}

	public function test_page_for_posts_should_match_page_for_post_option_posts_without_language() {
		$this->assertCanonical( '/posts/', '/en/posts/' );
	}

	public function test_page_for_posts_should_match_page_for_post_option_posts_from_plain_permalink() {
		$this->assertCanonical( '?page_id=' . self::$page_for_posts_en, '/en/posts/' );
	}

	public function test_page_for_post_option_should_be_translated_when_language_is_incorrect() {
		$this->assertCanonical( '/en/articles/', '/fr/articles/' );
	}

	public function test_page_for_post_option_should_be_translated_when_no_language_is_set() {
		$this->assertCanonical( '/articles/', '/fr/articles/' );
	}

	public function test_page_for_post_option_should_be_translated_from_plain_permalink() {
		$this->assertCanonical( '?page_id=' . self::$page_for_posts_fr, '/fr/articles/' );
	}

	public function test_static_front_page_with_name_and_language() {
		$this->assertCanonical(
			'/en/home/',
			array(
				'url' => '/en/home/',
				'qv'  => array( 'lang' => 'en', 'pagename' => 'home', 'page' => '' ),
			)
		);
	}

	public function test_static_front_page_with_incorrect_language() {
		$this->assertCanonical( '/fr/home/', '/en/home/' );
	}

	public function test_static_front_page_without_language() {
		$this->assertCanonical( '/home/', '/en/home/' );
	}

	public function test_static_front_page_from_plain_permalink() {
		$this->assertCanonical( '?page_id=' . self::$page_on_front_en, '/en/home/' );
	}

	public function test_sitemap_with_translated_post() {
		$this->init_for_sitemaps();

		$this->assertCanonical(
			'/en/wp-sitemap-posts-post-1.xml',
			array(
				'url' => '/en/wp-sitemap-posts-post-1.xml',
				'qv'  => array(
					'lang' => 'en',
					'sitemap' => 'posts',
					'sitemap-subtype' => 'post',
					'paged' => '1',
				),
			)
		);
	}

	public function test_sitemap_with_translated_cpt() {
		$this->init_for_sitemaps();

		$this->assertCanonical(
			'/en/wp-sitemap-posts-trcpt-1.xml',
			array(
				'url' => '/en/wp-sitemap-posts-trcpt-1.xml',
				'qv'  => array(
					'lang' => 'en',
					'sitemap' => 'posts',
					'sitemap-subtype' => 'trcpt',
					'paged' => '1',
				),
			)
		);
	}

	public function test_sitemap_with_translated_cpt_and_tax() {
		$this->init_for_sitemaps();

		$this->assertCanonical(
			'/en/wp-sitemap-taxonomies-trtax-1.xml',
			array(
				'url' => '/en/wp-sitemap-taxonomies-trtax-1.xml',
				'qv'  => array(
					'lang' => 'en',
					'sitemap' => 'taxonomies',
					'sitemap-subtype' => 'trtax',
					'paged' => '1',
				),
			)
		);
	}

	public function test_sitemap_with_untranslated_cpt() {
		$this->init_for_sitemaps();

		$this->assertCanonical(
			'/wp-sitemap-posts-cpt-1.xml',
			array(
				'url' => '/wp-sitemap-posts-cpt-1.xml',
				'qv'  => array(
					'sitemap' => 'posts',
					'sitemap-subtype' => 'cpt',
					'paged' => '1',
				),
			)
		);
	}

	public function test_sitemap_with_untranslated_cpt_and_tax() {
		$this->init_for_sitemaps();

		$this->assertCanonical(
			'/wp-sitemap-taxonomies-tax-1.xml',
			array(
				'url' => '/wp-sitemap-taxonomies-tax-1.xml',
				'qv'  => array(
					'sitemap' => 'taxonomies',
					'sitemap-subtype' => 'tax',
					'paged' => '1',
				),
			)
		);
	}

	public function test_sitemap_with_user() {
		$this->init_for_sitemaps();

		$this->assertCanonical(
			'/en/wp-sitemap-users-1.xml',
			array(
				'url' => '/en/wp-sitemap-users-1.xml',
				'qv'  => array(
					'lang' => 'en',
					'sitemap' => 'users',
					'paged' => '1',
				),
			)
		);
	}

	public function test_sitemap_with_category() {
		$this->init_for_sitemaps();

		$this->assertCanonical(
			'/en/wp-sitemap-taxonomies-category-1.xml',
			array(
				'url' => '/en/wp-sitemap-taxonomies-category-1.xml',
				'qv'  => array(
					'lang' => 'en',
					'sitemap' => 'taxonomies',
					'sitemap-subtype' => 'category',
					'paged' => '1',
				),
			)
		);
	}
}
