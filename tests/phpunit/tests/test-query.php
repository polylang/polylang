<?php

class Query_Test extends PLL_UnitTestCase {
	public $structure = '/%postname%/';

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	public function set_up() {
		parent::set_up();

		global $wp_rewrite;

		self::$model->options['hide_default'] = 1;
		self::$model->options['post_types'] = array(
			'trcpt' => 'trcpt',
		);
		self::$model->options['taxonomies'] = array(
			'trtax' => 'trtax',
		);

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );

		self::$model->post->register_taxonomy(); // needs this for 'lang' query var
		create_initial_taxonomies();
		register_post_type( 'trcpt', array( 'public' => true, 'has_archive' => true ) ); // translated custom post type with archives
		register_taxonomy( 'trtax', 'trcpt' ); // translated custom tax
		register_post_type( 'cpt', array( 'public' => true, 'has_archive' => true ) ); // *untranslated* custom post type with archives
		register_taxonomy( 'tax', 'cpt' ); // *untranslated* custom tax

		$links_model = self::$model->get_links_model();
		$links_model->init();

		// flush rules
		$wp_rewrite->flush_rules();

		$this->frontend = new PLL_Frontend( $links_model );
		$this->frontend->init();

		// de-activate cache for links
		$this->frontend->links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		$this->frontend->links->cache->method( 'get' )->willReturn( false );

		$this->frontend->filters_links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		$this->frontend->filters_links->cache->method( 'get' )->willReturn( false );
	}

	public function tear_down() {
		parent::tear_down();

		_unregister_post_type( 'cpt' );
		_unregister_taxonomy( 'tax' );
		_unregister_post_type( 'trcpt' );
		_unregister_taxonomy( 'trtax' );
	}

	public function test_home_latest_posts() {
		$en = $this->factory->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/' ) );

		$this->assertQueryTrue( 'is_home', 'is_front_page' );
		$this->assertEquals( array( get_post( $en ) ), $GLOBALS['wp_query']->posts );

		$this->go_to( home_url( '/fr/' ) );

		$this->assertQueryTrue( 'is_home', 'is_front_page' );
		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts );
		$this->assertEquals( home_url( '/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );
	}

	public function test_single_post() {
		$en = $this->factory->post->create( array( 'post_title' => 'test' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->go_to( home_url( '/fr/essai/' ) );

		$this->assertQueryTrue( 'is_single', 'is_singular' );
		$this->assertEquals( home_url( '/fr/essai/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEquals( home_url( '/test/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );
	}

	public function test_single_post_private_translation() {
		// the 'get_user_metadata' filter in frontend-filters breaks this user_description gets '' instead of an array ?
		$author_en = $this->factory->user->create( array( 'role' => 'author' ) );

		$en = $this->factory->post->create( array( 'post_title' => 'test', 'post_author' => $author_en, 'post_status' => 'private' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->go_to( home_url( '/fr/essai/' ) );

		// administator can read everything
		wp_set_current_user( 1 );
		$this->assertEquals( home_url( '/test/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );

		// author can read his own post
		wp_set_current_user( $author_en );
		$this->assertEquals( home_url( '/test/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );

		wp_set_current_user( 0 );
		$this->assertEmpty( $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );

		$this->delete_user( $author_en );
	}

	public function test_page() {
		$en = $this->factory->post->create( array( 'post_title' => 'test', 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai', 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->go_to( home_url( '/fr/essai/' ) );

		$this->assertQueryTrue( 'is_page', 'is_singular' );
		$this->assertEquals( home_url( '/fr/essai/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEquals( home_url( '/test/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );
	}

	public function test_category() {
		$fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );
		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$post_id = $this->factory->post->create();
		self::$model->post->set_language( $post_id, 'fr' );
		wp_set_post_terms( $post_id, array( $fr ), 'category' );

		$this->go_to( home_url( '/fr/category/essai/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_category' );
		$this->assertEquals( array( get_post( $post_id ) ), $GLOBALS['wp_query']->posts );
		$this->assertEquals( home_url( '/fr/category/essai/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'fr' ) ) ); // Link to self
		$this->assertEmpty( $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) ); // no content in translation

		$post_id = $this->factory->post->create();
		self::$model->post->set_language( $post_id, 'en' );
		wp_set_post_terms( $post_id, array( $en ), 'category' );

		$this->assertEquals( home_url( '/category/test/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );
	}

	public function test_post_tag() {
		$en = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );
		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$post_id = $this->factory->post->create( array( 'tags_input' => array( 'essai' ) ) );
		self::$model->post->set_language( $post_id, 'fr' );

		$this->go_to( home_url( '/fr/tag/essai/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_tag' );
		$this->assertEquals( array( get_post( $post_id ) ), $GLOBALS['wp_query']->posts );
		$this->assertEquals( home_url( '/fr/tag/essai/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEmpty( $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) ); // no content in translation

		$post_id = $this->factory->post->create( array( 'tags_input' => array( 'test' ) ) );
		self::$model->post->set_language( $post_id, 'en' );

		$this->assertEquals( home_url( '/tag/test/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );
	}

	public function test_post_format() {
		$post_id = $this->factory->post->create();
		set_post_format( $post_id, 'aside' );
		self::$model->post->set_language( $post_id, 'fr' );

		$this->go_to( home_url( '/fr/type/aside/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_tax' );
		$this->assertEquals( array( get_post( $post_id ) ), $GLOBALS['wp_query']->posts );
		$this->assertEquals( home_url( '/fr/type/aside/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEmpty( $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) ); // no content in translation

		$post_id = $this->factory->post->create();
		set_post_format( $post_id, 'aside' );
		self::$model->post->set_language( $post_id, 'en' );
		wp_cache_flush(); // otherwise count_posts has only posts in fr

		$this->assertEquals( home_url( '/type/aside/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );
	}

	public function test_translated_custom_tax() {
		$en = $this->factory->term->create( array( 'taxonomy' => 'trtax', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'trtax', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );
		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$post_id = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		self::$model->post->set_language( $post_id, 'fr' );
		wp_set_post_terms( $post_id, 'essai', 'trtax' ); // don't use 'tax_input' above as we don't pass current_user_can test in wp_insert_post

		$this->frontend->curlang = self::$model->get_language( 'fr' ); // Otherwise the test fails for WP 4.8+ due to the changes in get_term_by()

		$this->go_to( home_url( '/fr/trtax/essai/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_tax' );
		$this->assertTrue( is_tax( 'trtax' ) );
		$this->assertFalse( is_tax( 'language' ) );
		$this->assertEquals( array( get_post( $post_id ) ), $GLOBALS['wp_query']->posts );
		$this->assertEquals( home_url( '/fr/trtax/essai/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEmpty( $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) ); // no content in translation

		$post_id = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		self::$model->post->set_language( $post_id, 'en' );
		wp_set_post_terms( $post_id, 'test', 'trtax' );

		$this->assertEquals( home_url( '/trtax/test/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );
	}

	public function test_untranslated_custom_tax() {
		$term_id = $this->factory->term->create( array( 'taxonomy' => 'tax', 'name' => 'test' ) );
		$post_id = $this->factory->post->create( array( 'post_type' => 'cpt' ) );
		wp_set_post_terms( $post_id, 'test', 'tax' );

		$this->go_to( home_url( '/tax/test/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_tax' );
		$this->assertTrue( is_tax( 'tax' ) );
		$this->assertEquals( array( get_post( $post_id ) ), $GLOBALS['wp_query']->posts );
		$this->assertEmpty( $this->frontend->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
	}

	public function test_translated_post_type_archive() {
		$fr = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		self::$model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/fr/trcpt/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive' ); // we don't want is_tax
		$this->assertEquals( home_url( '/fr/trcpt/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEmpty( $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) ); // no content in translation

		$en = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		self::$model->post->set_language( $en, 'en' );

		$this->go_to( home_url( '/fr/trcpt/' ) );

		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/trcpt/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );
	}

	public function test_untranslated_post_type_archive() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'cpt' ) );

		$this->go_to( home_url( '/cpt/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive' );
		$this->assertEquals( array( get_post( $post_id ) ), $GLOBALS['wp_query']->posts );
		$this->assertEmpty( $this->frontend->links->get_translation_url( self::$model->get_language( 'fr' ) ) );

		// Secondary query which would erroneously forces the language
		$query = new WP_Query( array( 'post_type' => 'cpt', 'lang' => 'fr' ) );
		$this->assertEquals( array( get_post( $post_id ) ), $GLOBALS['wp_query']->posts );
	}

	public function test_archives() {
		$fr = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00', 'post_author' => 1 ) );
		self::$model->post->set_language( $fr, 'fr' );

		// author
		$this->go_to( home_url( '/fr/author/admin/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_author' ); // we don't want is_tax
		$this->assertEquals( home_url( '/fr/author/admin/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEmpty( $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) ); // no content in translation

		// year
		$this->go_to( home_url( '/fr/2007/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_date', 'is_year' ); // we don't want is_tax
		$this->assertEquals( home_url( '/fr/2007/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEmpty( $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) ); // no content in translation

		// month
		$this->go_to( home_url( '/fr/2007/09/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_date', 'is_month' ); // we don't want is_tax
		$this->assertEquals( home_url( '/fr/2007/09/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEmpty( $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) ); // no content in translation

		// day
		$this->go_to( home_url( '/fr/2007/09/04/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_date', 'is_day' ); // we don't want is_tax
		$this->assertEquals( home_url( '/fr/2007/09/04/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEmpty( $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) ); // no content in translation

		$en = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00', 'post_author' => 1 ) );
		self::$model->post->set_language( $en, 'en' );

		// author
		$this->go_to( home_url( '/fr/author/admin/' ) );

		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/author/admin/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );

		// year
		$this->go_to( home_url( '/fr/2007/' ) );

		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/2007/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );

		// month
		$this->go_to( home_url( '/fr/2007/09/' ) );

		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/2007/09/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );

		// day
		$this->go_to( home_url( '/fr/2007/09/04/' ) );

		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/2007/09/04/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );
	}

	public function test_search() {
		$en = $this->factory->post->create( array( 'post_title' => 'test' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'test fr' ) );
		self::$model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/fr/?s=test' ) );

		$this->assertQueryTrue( 'is_search' ); // we don't want is_tax
		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/fr/?s=test' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEquals( home_url( '/?s=test' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );
	}

	/**
	 * After https://core.trac.wordpress.org/ticket/11330 an empty search doesn't return the homepage anymore.
	 */
	public function test_empty_search() {
		$this->go_to( home_url( '/fr/?s=' ) );
		$this->assertQueryTrue( 'is_search' );
	}

	/**
	 * Issue #937.
	 */
	public function test_invalid_search() {
		$this->go_to( home_url( '/fr/random/?s=' ) );
		$this->assertQueryTrue( 'is_404' );
	}

	public function test_search_in_category() {
		$en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );
		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$post_id = $this->factory->post->create( array( 'post_title' => 'test' ) );
		self::$model->post->set_language( $post_id, 'en' );
		wp_set_post_terms( $post_id, array( $en ), 'category' );

		$searched = $this->factory->post->create( array( 'post_title' => 'test fr' ) );
		self::$model->post->set_language( $searched, 'fr' );
		wp_set_post_terms( $searched, array( $fr ), 'category' );

		$this->frontend->curlang = self::$model->get_language( 'fr' ); // brute force otherwise get_translation_url fails to translate the category slug
		$this->go_to( home_url( '/fr/category/essai/?s=test' ) );

		$this->assertQueryTrue( 'is_search', 'is_category', 'is_archive' ); // we don't want is_tax
		$this->assertEquals( array( get_post( $searched ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/category/test/?s=test' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );
	}

	/**
	 * Bug fixed in v1.7.11: error 404 for attachments.
	 * Bug fixed in v1.9.1: language switcher does not link to media translation for anonymous user.
	 */
	public function test_attachment() {
		$post_en = $this->factory->post->create( array( 'post_title' => 'test' ) );
		self::$model->post->set_language( $post_en, 'en' );

		$post_fr = $this->factory->post->create( array( 'post_title' => 'essai' ) );
		self::$model->post->set_language( $post_fr, 'fr' );

		$en = $this->factory->post->create( array( 'post_title' => 'img_en', 'post_type' => 'attachment', 'post_parent' => $post_en ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'img_fr', 'post_type' => 'attachment', 'post_parent' => $post_fr ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->go_to( home_url( '/fr/essai/img_fr/' ) );

		$this->assertQueryTrue( 'is_attachment', 'is_singular', 'is_single' ); // bug fixed in v1.7.11
		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/test/img_en/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) ); // bug fixed in v1.9.1
	}

	/**
	 * Bug fixed in 2.1: language switcher does not link to media translation for unattached media.
	 */
	public function test_unattached_attachment() {
		$en = $this->factory->post->create( array( 'post_title' => 'img_en', 'post_type' => 'attachment' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'img_fr', 'post_type' => 'attachment' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->go_to( home_url( '/fr/img_fr/' ) );

		$this->assertQueryTrue( 'is_attachment', 'is_singular', 'is_single' );
		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/img_en/' ), $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) ); // bug fixed in v1.9.1
	}

	/**
	 * Bug fixed in v1.8: is_tax set on main feeds.
	 */
	public function test_main_feed() {
		$en = $this->factory->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/fr/feed/' ) );
		$this->assertQueryTrue( 'is_feed' ); // we don't want is_tax
		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr

		$this->go_to( home_url( '/feed/' ) );
		$this->assertQueryTrue( 'is_feed' );
	}

	/**
	 * Bug in 1.8 on secondary query, fixed in 1.8.1.
	 *
	 * @see https://wordpress.org/support/topic/issue-with-get_posts-in-version-18
	 */
	public function test_untranslated_custom_tax_with_translated_cpt() {
		register_taxonomy( 'tax', 'trcpt' );
		$term_id = $this->factory->term->create( array( 'taxonomy' => 'tax', 'name' => 'test' ) );

		$en = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		self::$model->post->set_language( $en, 'en' );
		wp_set_post_terms( $en, 'test', 'tax' );

		$fr = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		self::$model->post->set_language( $fr, 'fr' );
		wp_set_post_terms( $fr, 'test', 'tax' );

		$this->frontend->curlang = self::$model->get_language( 'fr' );
		$this->assertEquals( array( get_post( $fr ) ), get_posts( array( 'post_type' => 'trcpt', 'tax' => 'test' ) ) ); // initial bug

		// additional test for post_type = any
		$this->assertEquals( array( get_post( $fr ) ), get_posts( array( 'post_type' => 'any', 'tax' => 'test' ) ) );

		// additional test for empty post_type
		$query = new WP_Query( array( 'tax' => 'test' ) ); // get_posts sets 'post' as default post type
		$this->assertEquals( array( get_post( $fr ) ), $query->posts );
	}

	/**
	 * "Issue" fixed in 2.0.10: Drafts should not appear in language switcher.
	 */
	public function test_draft() {
		$en = $this->factory->post->create( array( 'post_title' => 'test', 'post_status' => 'draft' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->go_to( home_url( '/fr/essai/' ) );

		$this->assertEmpty( $this->frontend->links->get_translation_url( self::$model->get_language( 'en' ) ) );
	}

	public function test_cat() {
		// Categories
		$cat_en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $cat_en, 'en' );

		$cat_fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$model->term->set_language( $cat_fr, 'fr' );

		// Posts
		$en = $this->factory->post->create();
		self::$model->post->set_language( $en, 'en' );
		wp_set_post_terms( $en, array( $cat_en ), 'category' );

		$fr = $this->factory->post->create();
		self::$model->post->set_language( $fr, 'fr' );
		wp_set_post_terms( $fr, array( $cat_fr ), 'category' );

		$this->frontend->curlang = self::$model->get_language( 'en' );

		$query = new WP_Query( array( 'cat' => $cat_en ) );
		$this->assertEquals( array( get_post( $en ) ), $query->posts );

		$query = new WP_Query( array( 'cat' => -$cat_fr ) );
		$this->assertEquals( array( get_post( $en ) ), $query->posts );

		// Bug fixed in 2.2.1
		$query = new WP_Query( array( 'cat' => -$cat_en ) );
		$this->assertEmpty( $query->posts );

		$query = new WP_Query( array( 'cat' => $cat_fr ) );
		$this->assertEmpty( $query->posts );

		$query = new WP_Query( array( 'cat' => "{$cat_en},{$cat_fr}" ) );
		$this->assertEquals( array( get_post( $en ) ), $query->posts );
	}

	/**
	 * Bug introduced in 2.2 and fixed in 2.2.4.
	 */
	public function test_any() {
		// Posts
		$en = $this->factory->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$query = new WP_Query( array( 'post_type' => 'any', 'lang' => 'en' ) );
		$this->assertEquals( array( get_post( $en ) ), $query->posts );

		$this->frontend->curlang = self::$model->get_language( 'en' );

		$query = new WP_Query( array( 'post_type' => 'any' ) );
		$this->assertEquals( array( get_post( $en ) ), $query->posts );

		$query = new WP_Query( array( 'post_type' => 'any', 'lang' => 'fr' ) );
		$this->assertEquals( array( get_post( $fr ) ), $query->posts );
	}

	/**
	 * Bug fixed in 2.3.3
	 */
	public function test_tax_query_with_relation_or() {
		register_taxonomy_for_object_type( 'tax', 'trcpt' ); // *untranslated* custom tax

		// Taxonomy
		$tag = $this->factory->term->create( array( 'taxonomy' => 'tax', 'name' => 'test' ) );

		// Posts
		$en = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		self::$model->post->set_language( $en, 'en' );
		wp_set_post_terms( $en, array( $tag ), 'tax' );

		$fr = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		self::$model->post->set_language( $fr, 'fr' );
		wp_set_post_terms( $fr, array( $tag ), 'tax' );

		$this->frontend->curlang = self::$model->get_language( 'en' );

		$args = array(
			'post_type' => 'trcpt',
			'tax_query' => array(
				'relation' => 'OR',
				array(
					'field'    => 'id',
					'terms'    => $tag,
					'taxonomy' => 'tax',
				),
			),
		);

		$query = new WP_Query( $args );
		$this->assertEquals( array( get_post( $en ) ), $query->posts );
	}

	/**
	 * Tests cases with 'lang' and no post type in query.
	 */
	public function test_language_and_no_post_type_in_query() {
		$post_id = $this->factory->post->create( array( 'post_title' => 'test', 'post_date' => '2007-09-04 00:00:00', 'post_author' => 1 ) );
		self::$model->post->set_language( $post_id, 'fr' );

		$page_id = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'test' ) );
		self::$model->post->set_language( $page_id, 'fr' );

		$query = new WP_Query( array( 'lang' => 'fr' ) );
		$this->assertEquals( array( get_post( $post_id ) ), $query->posts );

		$query = new WP_Query( array( 'lang' => 'fr', 'name' => 'test' ) );
		$this->assertEquals( array( get_post( $post_id ) ), $query->posts );

		$query = new WP_Query( array( 'lang' => 'fr', 's' => 'test' ) );
		$this->assertEqualSets( array( get_post( $post_id ), get_post( $page_id ) ), $query->posts );

		$query = new WP_Query( array( 'lang' => 'fr', 'pagename' => 'test' ) );
		$this->assertEquals( array( get_post( $page_id ) ), $query->posts );

		$media_id = $this->factory->post->create( array( 'post_type' => 'attachment', 'post_title' => 'attached' ) );
		self::$model->post->set_language( $media_id, 'fr' );

		$query = new WP_Query( array( 'lang' => 'fr', 'attachment' => 'attached' ) );
		$this->assertEquals( array( get_post( $media_id ) ), $query->posts );

		$cpt_id = $this->factory->post->create( array( 'post_type' => 'trcpt', 'post_title' => 'test', 'post_date' => '2007-09-04 00:00:00', 'post_author' => 1 ) );
		self::$model->post->set_language( $cpt_id, 'fr' );

		$tax_id = $this->factory->term->create( array( 'taxonomy' => 'trtax' ) );
		self::$model->term->set_language( $tax_id, 'fr' );
		wp_set_post_terms( $cpt_id, array( $tax_id ), 'trtax' );

		$args = array(
			'lang'      => 'fr',
			'tax_query' => array(
				array(
					'field'    => 'id',
					'terms'    => $tax_id,
					'taxonomy' => 'trtax',
				),
			),
		);

		$query = new WP_Query( $args );
		$this->assertEquals( array( get_post( $cpt_id ) ), $query->posts );

		$query = new WP_Query( array( 'lang' => 'fr', 'm' => 200709 ) );
		$this->assertEquals( array( get_post( $post_id ) ), $query->posts );

		$query = new WP_Query( array( 'lang' => 'fr', 'author' => 1 ) );
		$this->assertEquals( array( get_post( $post_id ) ), $query->posts );
	}

	/**
	 * Issue fixed in 2.6.6.
	 */
	public function test_category_with_post_type_added_late_in_query() {
		register_taxonomy_for_object_type( 'category', array( 'post', 'trcpt' ) );

		$cpt_id = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		self::$model->post->set_language( $cpt_id, 'fr' );

		$cat_id = $this->factory->category->create();
		self::$model->term->set_language( $cat_id, 'fr' );
		wp_set_post_terms( $cpt_id, array( $cat_id ), 'category' );

		// Assign the post type in a hook after our pare_query action
		add_action(
			'pre_get_posts',
			function( $query ) {
				if ( empty( $query->query_vars['post_type'] ) ) {
					$query->set( 'post_type', 'trcpt' );
				}
			}
		);

		$query = new WP_Query( array( 'lang' => 'fr', 'cat' => $cat_id ) );
		$this->assertEquals( array( get_post( $cpt_id ) ), $query->posts );
	}

	/**
	 * Bug introduced by WP 5.5 and fixed in Polylang 2.8.
	 * The sticky posts should appear only once.
	 */
	public function test_sticky_posts() {
		$fr = $this->factory->post->create();
		self::$model->post->set_language( $fr, 'fr' );
		stick_post( $fr );

		$this->frontend->curlang = self::$model->get_language( 'fr' );

		$this->go_to( home_url( '/fr/' ) );
		$this->assertEquals( 1, $GLOBALS['wp_query']->post_count );
	}
}
