<?php

class Canonical_Test extends PLL_Canonical_UnitTestCase {
	public $structure = '/%postname%/';

	private static $post_en;
	private static $page_id;
	private static $custom_post_id;
	private static $term_en;
	private static $second_term_en;
	private static $custom_term_en;
	private static $tag_en;
	private static $page_for_posts_en;
	private static $page_for_posts_fr;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once POLYLANG_DIR . '/include/api.php';

		self::generate_shared_fixtures( $factory );
		self::$model->clean_languages_cache();
	}

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function generate_shared_fixtures( WP_UnitTest_Factory $factory ) {
		// Register CPT and custom taxonomy before creating their items later.
		self::register_post_types_and_taxonomies();

		self::$post_en = $factory->post->create( array( 'post_title' => 'post-format-test-audio' ) );
		self::$model->post->set_language( self::$post_en, 'en' );

		self::$page_id = $factory->post->create( array( 'post_type' => 'page', 'post_title' => 'parent-page' ) );
		self::$model->post->set_language( self::$page_id, 'en' );

		self::$custom_post_id = $factory->post->create(
			array(
				'import_id'  => 416,
				'post_type'  => 'pllcanonical',
				'post_title' => 'custom-post',
			)
		);
		self::$model->post->set_language( self::$custom_post_id, 'en' );

		self::$term_en = $factory->term->create( array( 'taxonomy' => 'category', 'name' => 'parent' ) );
		self::$model->term->set_language( self::$term_en, 'en' );

		self::$second_term_en = $factory->term->create( array( 'taxonomy' => 'category', 'name' => 'second' ) );
		self::$model->term->set_language( self::$second_term_en, 'en' );

		self::$tag_en = $factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'test-tag' ) );
		self::$model->term->set_language( self::$tag_en, 'en' );

		self::$custom_term_en = self::factory()->term->create( array( 'taxonomy' => 'custom_tax', 'name' => 'custom-term' ) );
		self::$model->term->set_language( self::$custom_term_en, 'en' );

		$en = self::$page_for_posts_en = $factory->post->create( array( 'post_title' => 'posts', 'post_type' => 'page' ) );
		self::$model->post->set_language( self::$page_for_posts_en, 'en' );

		$fr = self::$page_for_posts_fr = $factory->post->create( array( 'post_title' => 'articles', 'post_type' => 'page' ) );
		self::$model->post->set_language( self::$page_for_posts_fr, 'fr' );

		self::$model->post->save_translations( self::$page_for_posts_en, compact( 'en', 'fr' ) );
	}

	public function init_for_sitemaps() {
		add_action(
			'pll_init',
			function ( $polylang ) {
				$polylang->sitemaps = new PLL_Sitemaps( $polylang );
				$polylang->sitemaps->init();

				$GLOBALS['wp_sitemaps'] = null; // Reset the global 'wp_sitemaps', otherwise wp_sitemaps_get_server() doesn't run completely.
				wp_sitemaps_get_server(); // Allows to register sitemaps rewrite rules.
			}
		);
	}

	public static function wpTearDownAfterClass() {
		_unregister_post_type( 'pllcanonical' );
		_unregister_taxonomy( 'custom_tax' );

		parent::wpTearDownAfterClass();
	}

	protected static function register_post_types_and_taxonomies() {
		parent::register_post_types_and_taxonomies();

		register_post_type(
			'pllcanonical',
			array(
				'public' => true,
				'has_archive' => true, // Implies to build the feed permastruct by default.
			)
		);

		register_taxonomy(
			'custom_tax',
			'post',
			array(
				'public'  => true,
				'rewrite' => true,
			)
		);
	}

	public function set_up() {
		parent::set_up();

		$GLOBALS['polylang'] = &$this->pll_env;

		$this->options = array_merge(
			PLL_Install::get_default_options(),
			array(
				'default_lang' => 'en',
				'hide_default' => 0,
				'post_types'   => array(
					'pllcanonical' => 'pllcanonical',
				),
				'taxonomies'   => array(
					'custom_tax' => 'custom_tax',
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

	public function test_should_not_remove_query_string_parameter_from_post_plain_permalink_url() {
		$this->assertCanonical( '?foo=bar&p=' . self::$post_en, '/en/post-format-test-audio/?foo=bar' );
	}

	public function test_should_not_remove_query_string_parameter_from_post_rewritten_url() {
		$this->assertCanonical( '/en/post-format-test-audio/?foo=bar&p=' . self::$post_en, '/en/post-format-test-audio/?foo=bar' );
	}

	public function test_post_feed_with_incorrect_language() {
		$this->assertCanonical( '/fr/post-format-test-audio/feed/', '/en/post-format-test-audio/feed/' );
	}

	public function test_post_feed_without_language() {
		$this->assertCanonical( '/post-format-test-audio/feed/', '/en/post-format-test-audio/feed/' );
	}

	public function test_post_feed_from_plain_permalink() {
		$this->assertCanonical( '?feed=rss&p=' . self::$post_en, '/en/post-format-test-audio/feed/' );
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

	public function test_should_not_remove_query_string_parameter_from_page_plain_permalink_url() {
		$this->assertCanonical( '?foo=bar&page_id=' . self::$page_id, '/en/parent-page/?foo=bar' );
	}

	public function test_should_not_remove_query_string_parameter_from_page_rewritten_url() {
		$this->assertCanonical( '/en/parent-page/?foo=bar&page_id=' . self::$page_id, '/en/parent-page/?foo=bar' );
	}

	public function test_page_feed_with_incorrect_language() {
		$this->assertCanonical( '/fr/parent-page/feed/', '/en/parent-page/feed/' );
	}

	public function test_page_feed_without_language() {
		$this->assertCanonical( '/parent-page/feed/', '/en/parent-page/feed/' );
	}

	public function test_page_feed_from_plain_permalink() {
		$this->assertCanonical( '?feed=rss&page_id=' . self::$page_id, '/en/parent-page/feed/' );
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

	public function test_should_not_remove_query_string_parameter_from_custom_post_type_rewritten_url() {
		$this->assertCanonical(
			'/en/pllcanonical/custom-post/?foo=bar',
			array(
				'url' => '/en/pllcanonical/custom-post/?foo=bar',
				'qv'  => array(
					'lang'         => 'en',
					'pllcanonical' => 'custom-post',
					'foo'          => 'bar',
					'page'         => '',
					'post_type'    => 'pllcanonical',
					'name'         => 'custom-post',
				),
			)
		);
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

	public function test_paged_category_with_name_and_language() {
		$this->assertCanonical(
			'/en/category/parent/page/2/',
			array(
				'url' => '/en/category/parent/page/2/',
				'qv'  => array(
					'lang'          => 'en',
					'category_name' => 'parent',
					'paged'         => 2,
				),
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

	public function test_should_not_remove_query_string_parameter_from_category_rewritten_url() {
		$this->assertCanonical(
			'/en/category/parent/?foo=bar',
			array(
				'url' => '/en/category/parent/?foo=bar',
				'qv'  => array(
					'lang'          => 'en',
					'category_name' => 'parent',
					'foo'           => 'bar',
				),
			)
		);
	}

	public function test_should_not_remove_query_string_parameter_from_tag_rewritten_url() {
		$this->assertCanonical(
			'/en/tag/test-tag/?foo=bar',
			array(
				'url' => '/en/tag/test-tag/?foo=bar',
				'qv'  => array(
					'lang' => 'en',
					'tag'  => 'test-tag',
					'foo'  => 'bar',
				),
			)
		);
	}

	public function test_custom_taxonomy_with_incorrect_language() {
		$this->assertCanonical( '/fr/custom_tax/custom-term/', '/en/custom_tax/custom-term/' );
	}

	public function test_custom_taxonomy_without_language() {
		$this->assertCanonical( '/custom_tax/custom-term/', '/en/custom_tax/custom-term/' );
	}

	public function test_custom_taxonomy_with_correct_language() {
		$this->assertCanonical(
			'/en/custom_tax/custom-term/',
			array(
				'url' => '/en/custom_tax/custom-term/',
				'qv'  => array(
					'lang'       => 'en',
					'custom_tax' => 'custom-term',
				),
			)
		);
	}

	public function test_should_not_remove_query_string_parameter_from_custom_taxonomy_plain_permalink_url() {
		$this->assertCanonical( '?foo=bar&custom_tax=custom-term', '/en/?foo=bar&custom_tax=custom-term' );
	}

	public function test_should_not_remove_query_string_parameter_from_custom_taxonomy_rewritten_url() {
		$this->assertCanonical( '/en/custom_tax/custom-term/?foo=bar', '/en/custom_tax/custom-term/?foo=bar' );
	}

	public function test_paged_category_from_plain_permalink() {
		update_option( 'posts_per_page', 1 );

		// Create 1 additional English post to have a paged category.
		$en = $this->factory->post->create();
		self::$model->post->set_language( $en, 'en' );

		// Set category to the posts.
		wp_set_post_terms( self::$post_en, array( self::$term_en ), 'category' );
		wp_set_post_terms( $en, array( self::$term_en ), 'category' );

		$this->assertCanonical( '?paged=2&cat=' . self::$term_en, '/en/category/parent/page/2/' );
	}

	public function test_page_for_posts_with_name_and_language() {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', self::$page_for_posts_fr );
		self::$model->clean_languages_cache(); // Clean the languages transient.
		$this->assertCanonical(
			'/en/posts/',
			array(
				'url' => '/en/posts/',
				'qv'  => array( 'lang' => 'en', 'pagename' => 'posts', 'page' => '' ),
			)
		);
	}

	public function test_page_for_posts_should_match_page_for_post_option_when_language_is_incorrect() {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', self::$page_for_posts_fr );
		self::$model->clean_languages_cache(); // Clean the languages transient.
		$this->assertCanonical( '/fr/posts/', '/en/posts/' );
	}

	public function test_page_for_posts_should_match_page_for_post_option_posts_without_language() {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', self::$page_for_posts_fr );
		self::$model->clean_languages_cache(); // Clean the languages transient.
		$this->assertCanonical( '/posts/', '/en/posts/' );
	}

	public function test_page_for_posts_should_match_page_for_post_option_posts_from_plain_permalink() {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', self::$page_for_posts_fr );
		self::$model->clean_languages_cache(); // Clean the languages transient.
		$this->assertCanonical( '?page_id=' . self::$page_for_posts_en, '/en/posts/' );
	}

	public function test_paged_page_for_posts_should_match_page_for_post_option_posts_from_plain_permalink() {
		update_option( 'posts_per_page', 1 );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', self::$page_for_posts_fr );

		// Create 1 additional English post to have a paged page for posts.
		$en = $this->factory->post->create();
		self::$model->post->set_language( $en, 'en' );

		self::$model->clean_languages_cache(); // Clean the languages transient.
		$this->assertCanonical( '?paged=2&page_id=' . self::$page_for_posts_en, '/en/posts/page/2/' );
	}

	public function test_page_for_post_option_should_be_translated_when_language_is_incorrect() {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', self::$page_for_posts_fr );
		self::$model->clean_languages_cache(); // Clean the languages transient.
		$this->assertCanonical( '/en/articles/', '/fr/articles/' );
	}

	public function test_page_for_post_option_should_be_translated_when_no_language_is_set() {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', self::$page_for_posts_fr );
		self::$model->clean_languages_cache(); // Clean the languages transient.
		$this->assertCanonical( '/articles/', '/fr/articles/' );
	}

	public function test_page_for_post_option_should_be_translated_from_plain_permalink() {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', self::$page_for_posts_fr );
		self::$model->clean_languages_cache(); // Clean the languages transient.
		$this->assertCanonical( '?page_id=' . self::$page_for_posts_fr, '/fr/articles/' );
	}

	/**
	 *  Bug introduced in 1.8.2 and fixed in 1.8.3.
	 */
	public function test_page_with_name_and_language_when_front_page_displays_posts() {
		update_option( 'show_on_front', 'posts' );

		$this->assertCanonical(
			'/en/parent-page/',
			array(
				'url' => '/en/parent-page/',
				'qv'  => array( 'lang' => 'en', 'pagename' => 'parent-page', 'page' => '' ),
			)
		);
	}

	/**
	 *  Bug introduced in 1.8.2 and fixed in 1.8.3.
	 */
	public function test_page_with_incorrect_language_when_front_page_displays_posts() {
		update_option( 'show_on_front', 'posts' );
		$this->assertCanonical( '/fr/parent-page/', '/en/parent-page/' );
	}

	/**
	 *  Bug introduced in 1.8.2 and fixed in 1.8.3.
	 */
	public function test_page_without_language_when_front_page_displays_posts() {
		update_option( 'show_on_front', 'posts' );
		$this->assertCanonical( '/parent-page/', '/en/parent-page/' );
	}

	public function test_page_from_plain_permalink_when_front_page_displays_posts() {
		update_option( 'show_on_front', 'posts' );
		self::$model->clean_languages_cache(); // Clean the languages transient.
		$this->assertCanonical( '?page_id=' . self::$page_id, '/en/parent-page/' );
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

	public function test_custom_post_type_feed_with_incorrect_language() {
		$this->assertCanonical( '/fr/pllcanonical/custom-post/feed/', '/en/pllcanonical/custom-post/feed/' );
	}

	public function test_custom_post_type_feed_without_language() {
		$this->assertCanonical( '/pllcanonical/custom-post/feed/', '/en/pllcanonical/custom-post/feed/' );
	}

	public function test_multiple_category() {
		$this->assertCanonical(
			'/en/category/parent,second/',
			array(
				'url' => '/en/category/parent,second/',
				'qv'  => array(
					'lang'          => 'en',
					'category_name' => 'parent,second',
				),
			)
		);
	}

	public function test_multiple_category_without_language() {
		$this->assertCanonical( '/category/parent,second/', '/en/category/parent,second/' );
	}

	public function test_multiple_category_with_wrong_language() {
		$this->assertCanonical( '/fr/category/parent,second/', '/en/category/parent,second/' );
	}

	// phpcs:disable
	// public function test_should_not_remove_query_string_parameter_from_category_plain_permalink_url() {
	// $this->assertCanonical( '?foo=bar&cat=' . self::$term_en, '/en/category/parent/?foo=bar' );
	// }

	// public function test_should_not_remove_query_string_parameter_from_tag_plain_permalink_url() {
	// $this->assertCanonical( '?foo=bar&tag=test-tag', '/en/tag/test-tag/?foo=bar' );
	// }

	// public function test_plain_cat_feed() {
	// $this->assertCanonical( '/?cat=' . self::$term_en . '&feed=rss2', '/en/category/parent/feed/' );
	// }

	// public function test_plain_tag_feed() {
	// $this->assertCanonical( '/?tag=test-tag&feed=rss2', '/en/tag/test-tag/feed/' );
	// }

	// public function test_untranslated_category_feed() {
	// $this->assertCanonical( '/fr/category/parent/feed/', '/en/category/parent/feed/' );
	// }

	// public function test_should_not_remove_query_string_parameter_from_custom_post_type_plain_permalink_url() {
	// 	// WordPress redirect_canonical() doesn't rewrite plain permalink for custom post types.
	// 	$this->assertCanonical( '?foo=bar&pllcanonical=custom-post', '/en/pllcanonical/custom-post/?foo=bar' );
	// }

	// public function test_custom_taxonomy_from_plain_permalink() {
	// 	// WordPress does redirect for custom category plain permalink.
	// 	$this->assertCanonical( '?custom_tax=custom-term', '/en/custom_tax/custom-term/' );
	// }
	// phpcs:enable
}
