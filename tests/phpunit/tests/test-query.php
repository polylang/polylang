<?php

class Query_Test extends PLL_UnitTestCase {
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
		self::$polylang->options['post_types'] = array(
			'trcpt' => 'trcpt',
		);
		self::$polylang->options['taxonomies'] = array(
			'trtax' => 'trtax',
		);

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );

		self::$polylang->model->post->register_taxonomy(); // needs this for 'lang' query var
		create_initial_taxonomies();
		register_post_type( 'trcpt', array( 'public' => true, 'has_archive' => true ) ); // translated custom post type with archives
		register_taxonomy( 'trtax', 'trcpt' ); // translated custom tax
		register_post_type( 'cpt', array( 'public' => true, 'has_archive' => true ) ); // *untranslated* custom post type with archives
		register_taxonomy( 'tax', 'cpt' ); // *untranslated* custom tax

		self::$polylang->links_model = self::$polylang->model->get_links_model();
		self::$polylang->links_model->init();

		// flush rules
		$wp_rewrite->flush_rules();

		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		self::$polylang->init();

		// de-activate cache for links
		self::$polylang->links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		self::$polylang->links->cache->method( 'get' )->willReturn( false );

		self::$polylang->filters_links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		self::$polylang->filters_links->cache->method( 'get' )->willReturn( false );
	}

	function tearDown() {
		parent::tearDown();

		_unregister_post_type( 'cpt' );
		_unregister_taxonomy( 'tax' );
		_unregister_post_type( 'trcpt' );
		_unregister_taxonomy( 'trtax' );
	}

	function test_home_latest_posts() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/' ) );

		$this->assertQueryTrue( 'is_home', 'is_front_page' );
		$this->assertEquals( array( get_post( $en ) ), $GLOBALS['wp_query']->posts );

		$this->go_to( home_url( '/fr/' ) );

		$this->assertQueryTrue( 'is_home', 'is_front_page' );
		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts );
		$this->assertEquals( home_url( '/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
	}

	function test_single_post() {
		$en = $this->factory->post->create( array( 'post_title' => 'test' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->go_to( home_url( '/fr/essai/' ) );

		$this->assertQueryTrue( 'is_single', 'is_singular' );
		$this->assertEquals( home_url( '/fr/essai/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEquals( home_url( '/test/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
	}

	function test_single_post_private_translation() {
		// the 'get_user_metadata' filter in frontend-filters breaks this user_description gets '' instead of an array ?
		$author_en = $this->factory->user->create( array( 'role' => 'author' ) );

		$en = $this->factory->post->create( array( 'post_title' => 'test', 'post_author' => $author_en, 'post_status' => 'private' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->go_to( home_url( '/fr/essai/' ) );

		// administator can read everything
		wp_set_current_user( 1 );
		$this->assertEquals( home_url( '/test/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );

		// author can read his own post
		wp_set_current_user( $author_en );
		$this->assertEquals( home_url( '/test/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );

		wp_set_current_user( 0 );
		$this->assertEmpty( self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );

		$this->delete_user( $author_en );
	}

	function test_page() {
		$en = $this->factory->post->create( array( 'post_title' => 'test', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->go_to( home_url( '/fr/essai/' ) );

		$this->assertQueryTrue( 'is_page', 'is_singular' );
		$this->assertEquals( home_url( '/fr/essai/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEquals( home_url( '/test/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
	}

	function test_category() {
		$fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $en, 'en' );
		self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$post_id = $this->factory->post->create();
		self::$polylang->model->post->set_language( $post_id, 'fr' );
		wp_set_post_terms( $post_id, array( $fr ), 'category' );

		$this->go_to( home_url( '/fr/category/essai/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_category' );
		$this->assertEquals( array( get_post( $post_id ) ), $GLOBALS['wp_query']->posts );
		$this->assertEquals( home_url( '/fr/category/essai/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) ); // Link to self
		$this->assertEmpty( self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) ); // no content in translation

		$post_id = $this->factory->post->create();
		self::$polylang->model->post->set_language( $post_id, 'en' );
		wp_set_post_terms( $post_id, array( $en ), 'category' );

		$this->assertEquals( home_url( '/category/test/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
	}

	function test_post_tag() {
		$en = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'essai' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );
		self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$post_id = $this->factory->post->create( array( 'tags_input' => array( 'essai' ) ) );
		self::$polylang->model->post->set_language( $post_id, 'fr' );

		$this->go_to( home_url( '/fr/tag/essai/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_tag' );
		$this->assertEquals( array( get_post( $post_id ) ), $GLOBALS['wp_query']->posts );
		$this->assertEquals( home_url( '/fr/tag/essai/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEmpty( self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) ); // no content in translation

		$post_id = $this->factory->post->create( array( 'tags_input' => array( 'test' ) ) );
		self::$polylang->model->post->set_language( $post_id, 'en' );

		$this->assertEquals( home_url( '/tag/test/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
	}

	function test_post_format() {
		$post_id = $this->factory->post->create();
		set_post_format( $post_id, 'aside' );
		self::$polylang->model->post->set_language( $post_id, 'fr' );

		$this->go_to( home_url( '/fr/type/aside/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_tax' );
		$this->assertEquals( array( get_post( $post_id ) ), $GLOBALS['wp_query']->posts );
		$this->assertEquals( home_url( '/fr/type/aside/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEmpty( self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) ); // no content in translation

		$post_id = $this->factory->post->create();
		set_post_format( $post_id, 'aside' );
		self::$polylang->model->post->set_language( $post_id, 'en' );
		wp_cache_flush(); // otherwise count_posts has only posts in fr

		$this->assertEquals( home_url( '/type/aside/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
	}

	function test_translated_custom_tax() {
		$en = $this->factory->term->create( array( 'taxonomy' => 'trtax', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'trtax', 'name' => 'essai' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );
		self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$post_id = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		wp_set_post_terms( $post_id, 'essai', 'trtax' ); // don't use 'tax_input' above as we don't pass current_user_can test in wp_insert_post
		self::$polylang->model->post->set_language( $post_id, 'fr' );

		$this->go_to( home_url( '/fr/trtax/essai/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_tax' );
		$this->assertTrue( is_tax( 'trtax' ) );
		$this->assertFalse( is_tax( 'language' ) );
		$this->assertEquals( array( get_post( $post_id ) ), $GLOBALS['wp_query']->posts );
		$this->assertEquals( home_url( '/fr/trtax/essai/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEmpty( self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) ); // no content in translation

		$post_id = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		wp_set_post_terms( $post_id, 'test', 'trtax' );
		self::$polylang->model->post->set_language( $post_id, 'en' );

		$this->assertEquals( home_url( '/trtax/test/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
	}

	function test_untranslated_custom_tax() {
		$term_id = $this->factory->term->create( array( 'taxonomy' => 'tax', 'name' => 'test' ) );
		$post_id = $this->factory->post->create( array( 'post_type' => 'cpt' ) );
		wp_set_post_terms( $post_id, 'test', 'tax' );

		$this->go_to( home_url( '/tax/test/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_tax' );
		$this->assertTrue( is_tax( 'tax' ) );
		$this->assertEquals( array( get_post( $post_id ) ), $GLOBALS['wp_query']->posts );
		$this->assertEmpty( self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
	}

	function test_translated_post_type_archive() {
		$fr = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/fr/trcpt/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive' ); // we don't want is_tax
		$this->assertEquals( home_url( '/fr/trcpt/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEmpty( self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) ); // no content in translation

		$en = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$this->go_to( home_url( '/fr/trcpt/' ) );

		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/trcpt/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
	}

	function test_untranslated_post_type_archive() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'cpt' ) );

		$this->go_to( home_url( '/cpt/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive' );
		$this->assertEquals( array( get_post( $post_id ) ), $GLOBALS['wp_query']->posts );
		$this->assertEmpty( self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );

		// Secondary query which would erroneously forces the language
		$query = new WP_Query( array( 'post_type' => 'cpt', 'lang' => 'fr' ) );
		$this->assertEquals( array( get_post( $post_id ) ), $GLOBALS['wp_query']->posts );
	}

	function test_archives() {
		$fr = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00', 'post_author' => 1 ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		// author
		$this->go_to( home_url( '/fr/author/admin/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_author' ); // we don't want is_tax
		$this->assertEquals( home_url( '/fr/author/admin/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEmpty( self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) ); // no content in translation

		// year
		$this->go_to( home_url( '/fr/2007/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_date', 'is_year' ); // we don't want is_tax
		$this->assertEquals( home_url( '/fr/2007/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEmpty( self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) ); // no content in translation

		// month
		$this->go_to( home_url( '/fr/2007/09/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_date', 'is_month' ); // we don't want is_tax
		$this->assertEquals( home_url( '/fr/2007/09/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEmpty( self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) ); // no content in translation

		// day
		$this->go_to( home_url( '/fr/2007/09/04/' ) );

		$this->assertQueryTrue( 'is_archive', 'is_date', 'is_day' ); // we don't want is_tax
		$this->assertEquals( home_url( '/fr/2007/09/04/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEmpty( self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) ); // no content in translation

		$en = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00', 'post_author' => 1 ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		// author
		$this->go_to( home_url( '/fr/author/admin/' ) );

		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/author/admin/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );

		// year
		$this->go_to( home_url( '/fr/2007/' ) );

		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/2007/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );

		// month
		$this->go_to( home_url( '/fr/2007/09/' ) );

		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/2007/09/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );

		// day
		$this->go_to( home_url( '/fr/2007/09/04/' ) );

		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/2007/09/04/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
	}

	function test_search() {
		$en = $this->factory->post->create( array( 'post_title' => 'test' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'test fr' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/fr/?s=test' ) );

		$this->assertQueryTrue( 'is_search' ); // we don't want is_tax
		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/fr/?s=test' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEquals( home_url( '/?s=test' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
	}

	function test_search_in_category() {
		$en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );
		self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$post_id = $this->factory->post->create( array( 'post_title' => 'test' ) );
		self::$polylang->model->post->set_language( $post_id, 'en' );
		wp_set_post_terms( $post_id, array( $en ), 'category' );

		$searched = $this->factory->post->create( array( 'post_title' => 'test fr' ) );
		self::$polylang->model->post->set_language( $searched, 'fr' );
		wp_set_post_terms( $searched, array( $fr ), 'category' );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' ); // brute force otherwise get_translation_url fails to translate the category slug
		$this->go_to( home_url( '/fr/category/essai/?s=test' ) );

		$this->assertQueryTrue( 'is_search', 'is_category', 'is_archive' ); // we don't want is_tax
		$this->assertEquals( array( get_post( $searched ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/category/test/?s=test' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
	}

	// bug fixed in v1.7.11: error 404 for attachments
	// bug fixed in v1.9.1: language switcher does not link to media translation for anonymous user
	function test_attachment() {
		$post_en = $this->factory->post->create( array( 'post_title' => 'test' ) );
		self::$polylang->model->post->set_language( $post_en, 'en' );

		$post_fr = $this->factory->post->create( array( 'post_title' => 'essai' ) );
		self::$polylang->model->post->set_language( $post_fr, 'fr' );

		$en = $this->factory->post->create( array( 'post_title' => 'img_en', 'post_type' => 'attachment', 'post_parent' => $post_en ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'img_fr', 'post_type' => 'attachment', 'post_parent' => $post_fr ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->go_to( home_url( '/fr/essai/img_fr/' ) );

		$this->assertQueryTrue( 'is_attachment', 'is_singular', 'is_single' ); // bug fixed in v1.7.11
		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/test/img_en/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) ); // bug fixed in v1.9.1
	}

	// Bug fixed in 2.1: language switcher does not link to media translation for unattached media
	function test_unattached_attachment() {
		$en = $this->factory->post->create( array( 'post_title' => 'img_en', 'post_type' => 'attachment' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'img_fr', 'post_type' => 'attachment' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->go_to( home_url( '/fr/img_fr/' ) );

		$this->assertQueryTrue( 'is_attachment', 'is_singular', 'is_single' );
		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr
		$this->assertEquals( home_url( '/img_en/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) ); // bug fixed in v1.9.1
	}

	// bug fixed in v1.8: is_tax set on main feeds
	function test_main_feed() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/fr/feed/' ) );
		$this->assertQueryTrue( 'is_feed' ); // we don't want is_tax
		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts ); // only posts in fr

		$this->go_to( home_url( '/feed/' ) );
		$this->assertQueryTrue( 'is_feed' );
	}

	// bug in 1.8 on secondary query, fixed in 1.8.1
	// see https://wordpress.org/support/topic/issue-with-get_posts-in-version-18
	function test_untranslated_custom_tax_with_translated_cpt() {
		register_taxonomy( 'tax', 'trcpt' );
		$term_id = $this->factory->term->create( array( 'taxonomy' => 'tax', 'name' => 'test' ) );

		$en = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		self::$polylang->model->post->set_language( $en, 'en' );
		wp_set_post_terms( $en, 'test', 'tax' );

		$fr = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );
		wp_set_post_terms( $fr, 'test', 'tax' );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		$this->assertEquals( array( get_post( $fr ) ), get_posts( array( 'post_type' => 'trcpt', 'tax' => 'test' ) ) ); // initial bug

		// additional test for post_type = any
		$this->assertEquals( array( get_post( $fr ) ), get_posts( array( 'post_type' => 'any', 'tax' => 'test' ) ) );

		// additional test for empty post_type
		$query = new WP_Query( array( 'tax' => 'test' ) ); // get_posts sets 'post' as default post type
		$this->assertEquals( array( get_post( $fr ) ), $query->posts );
	}

	// "Issue" fixed in 2.0.10: Drafts should not appear in language switcher
	function test_draft() {
		$en = $this->factory->post->create( array( 'post_title' => 'test', 'post_status' => 'draft' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->go_to( home_url( '/fr/essai/' ) );

		$this->assertEmpty( self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
	}

	function test_cat() {
		// Categories
		$cat_en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $cat_en, 'en' );

		$cat_fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$polylang->model->term->set_language( $cat_fr, 'fr' );

		// Posts
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );
		wp_set_post_terms( $en, array( $cat_en ), 'category' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );
		wp_set_post_terms( $fr, array( $cat_fr ), 'category' );

		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );

		$query = new WP_Query( array( 'cat' => $cat_en ) );
		$this->assertEquals( array( get_post( $en ) ), $query->posts );

		$query = new WP_Query( array( 'cat' => -$cat_fr ) );
		$this->assertEquals( array( get_post( $en ) ), $query->posts );

		// Bug fixed in 2.2.1
		$query = new WP_Query( array( 'cat' => -$cat_en ) );
		$this->assertEmpty( $query->posts );

		// The test was broken by WP 4.9 and fixed in 2.2.7
		// See also https://core.trac.wordpress.org/ticket/42104
		$query = new WP_Query( array( 'cat' => $cat_fr ) );
		$this->assertEquals( array( get_post( $fr ) ), $query->posts );
	}

	// Bug introduced in 2.2 and fixed in 2.2.4
	function test_any() {
		// Posts
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$query = new WP_Query( array( 'post_type' => 'any', 'lang' => 'en' ) );
		$this->assertEquals( array( get_post( $en ) ), $query->posts );

		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );

		$query = new WP_Query( array( 'post_type' => 'any' ) );
		$this->assertEquals( array( get_post( $en ) ), $query->posts );

		$query = new WP_Query( array( 'post_type' => 'any', 'lang' => 'fr' ) );
		$this->assertEquals( array( get_post( $fr ) ), $query->posts );
	}
}
