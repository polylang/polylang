<?php

class Static_Pages_Test extends PLL_UnitTestCase {
	public $structure = '/%postname%/';
	static $home_en, $home_fr, $home_de, $posts_en, $posts_fr;

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		add_filter( 'pll_languages_list', array( 'PLL_Static_Pages', 'pll_languages_list' ), 2, 2 );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE_formal' );

		require_once PLL_INC . '/api.php';
		$GLOBALS['polylang'] = &self::$polylang;

		// page on front
		self::$home_en = $en = self::factory()->post->create( array(
			'post_title' => 'home',
			'post_type' => 'page',
			'post_content' => 'en1<!--nextpage-->en2',
		) );
		self::$polylang->model->post->set_language( $en, 'en' );

		self::$home_fr = $fr = self::factory()->post->create( array(
			'post_title' => 'accueil',
			'post_type' => 'page',
			'post_content' => 'fr1<!--nextpage-->fr2',
		) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$home_de = $de = self::factory()->post->create( array(
			'post_title' => 'startseite',
			'post_type' => 'page',
			'post_content' => 'de1<!--nextpage-->de2',
		) );
		self::$polylang->model->post->set_language( $de, 'de' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr', 'de' ) );

		// page for posts
		// intentionally do not create one in German
		self::$posts_en = $en = self::factory()->post->create( array( 'post_title' => 'posts', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		self::$posts_fr = $fr = self::factory()->post->create( array( 'post_title' => 'articles', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );
	}

	function setUp() {
		parent::setUp();

		self::$polylang->options['hide_default'] = 0;
		self::$polylang->options['redirect_lang'] = 0;

		global $wp_rewrite;

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );

		self::$polylang->model->post->register_taxonomy(); // needs this for 'lang' query var

		self::$polylang->links_model = self::$polylang->model->get_links_model();
		self::$polylang->links_model->init();

		self::$polylang->static_pages = new PLL_Admin_Static_Pages( self::$polylang );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', self::$home_fr );
		update_option( 'page_for_posts', self::$posts_fr );

		// go to frontend
		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		self::$polylang->init();

		self::$polylang->static_pages = new PLL_Frontend_Static_Pages( self::$polylang );
		self::$polylang->static_pages->pll_language_defined();
	}

	static function wpTearDownAfterClass() {
		wp_delete_post( self::$home_en );
		wp_delete_post( self::$home_fr );
		wp_delete_post( self::$home_de );
		wp_delete_post( self::$posts_en );
		wp_delete_post( self::$posts_fr );

		parent::wpTearDownAfterClass();
		remove_filter( 'pll_languages_list', array( 'PLL_Static_Pages', 'pll_languages_list' ), 2, 2 ); // Avoid breaking next tests
	}

	function test_front_page_with_default_options() {
		global $wp_rewrite;

		self::$polylang->model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		$this->assertEquals( home_url( '/en/home/' ), get_permalink( self::$home_en ) );
		$this->assertEquals( home_url( '/fr/accueil/' ), get_permalink( self::$home_fr ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/accueil/' ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_front_page' );
		$this->assertEquals( home_url( '/en/home/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( array( get_post( self::$home_fr ) ),  $GLOBALS['wp_query']->posts );
		$this->assertEmpty( redirect_canonical( home_url( '/fr/accueil/' ), false ) );
	}

	function test_front_page_with_query() {
		global $wp_rewrite;

		self::$polylang->model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/accueil/?query=1' ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_front_page' );
		$this->assertEquals( home_url( '/en/home/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( array( get_post( self::$home_fr ) ),  $GLOBALS['wp_query']->posts );
		$this->assertEmpty( redirect_canonical( home_url( '/fr/accueil/?query=1' ), false ) );
	}

	function test_paged_front_page() {
		global $wp_rewrite;

		self::$polylang->model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/accueil/page/2/' ) );
		the_post();

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_paged', 'is_front_page' );
		$this->assertEquals( home_url( '/en/home/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( 'fr2', get_the_content() );

		self::$polylang->curlang = self::$polylang->model->get_language( 'en' ); // brute force
		$this->go_to( home_url( '/en/home/page/2/' ) );
		the_post();

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_paged', 'is_front_page' );
		$this->assertEquals( home_url( '/fr/accueil/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEquals( 'en2',  get_the_content() );
		$this->assertEmpty( redirect_canonical( home_url( '/en/home/page/2/' ), false ) );
	}

	function test_front_page_with_hide_default() {
		global $wp_rewrite;

		self::$polylang->options['hide_default'] = 1;
		self::$polylang->model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		$this->assertEquals( home_url( '/' ), get_permalink( self::$home_en ) );
		$this->assertEquals( home_url( '/fr/accueil/' ), get_permalink( self::$home_fr ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/accueil/' ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_front_page' );
		$this->assertEquals( home_url( '/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( array( get_post( self::$home_fr ) ),  $GLOBALS['wp_query']->posts );
		$this->assertEmpty( redirect_canonical( home_url( '/fr/accueil/' ), false ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'en' ); // brute force
		$this->go_to( home_url( '/' ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_front_page' );
		$this->assertEquals( home_url( '/fr/accueil/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEquals( array( get_post( self::$home_en ) ),  $GLOBALS['wp_query']->posts );
		$this->assertEmpty( redirect_canonical( home_url( '/' ), false ) );
		$this->assertEquals( home_url( '/' ), redirect_canonical( home_url( '/en/home/' ), false ) );
	}

	// special case for default permalinks
	function test_front_page_with_hide_default_plain_permalinks() {
		global $wp_rewrite;
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '' );
		$wp_rewrite->flush_rules();

		self::$polylang->options['hide_default'] = 1;
		self::$polylang->links_model = self::$polylang->model->get_links_model();
		self::$polylang->model->clean_languages_cache();

		$this->assertEquals( home_url( '/' ), get_permalink( self::$home_en ) ); // trailing slash kept for home page
		$this->assertEquals( home_url( '?page_id=' . self::$home_fr . '&lang=fr' ), get_permalink( self::$home_fr ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '?page_id=' . self::$home_fr ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_front_page' );
		$this->assertEquals( home_url( '/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( array( get_post( self::$home_fr ) ),  $GLOBALS['wp_query']->posts );
		$this->assertEmpty( redirect_canonical( home_url( '/fr/accueil/' ), false ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'en' ); // brute force
		$this->go_to( home_url( '/' ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_front_page' );
		$this->assertEquals( home_url( '?page_id=' . self::$home_fr . '&lang=fr' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEquals( array( get_post( self::$home_en ) ),  $GLOBALS['wp_query']->posts );
		$this->assertEmpty( redirect_canonical( home_url( '/' ), false ) );
	}

	function test_paged_front_page_plain_permalinks() {
		global $wp_rewrite;
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '' );
		$wp_rewrite->flush_rules();

		self::$polylang->options['hide_default'] = 1;
		self::$polylang->links_model = self::$polylang->model->get_links_model();
		self::$polylang->model->clean_languages_cache();

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '?page_id=' . self::$home_fr . '&lang=fr&page=2' ) );
		the_post();

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_paged', 'is_front_page' );
		$this->assertEquals( home_url( '/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( 'fr2', get_the_content() );

		self::$polylang->curlang = self::$polylang->model->get_language( 'en' ); // brute force
		$this->go_to( home_url( '?page=2' ) );
		the_post();

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_paged', 'is_front_page' );
		$this->assertEquals( home_url( '?page_id=' . self::$home_fr . '&lang=fr' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEquals( 'en2',  get_the_content() );
		$this->assertEmpty( redirect_canonical( home_url( '?page=2' ), false ) );
	}

	function test_front_page_with_redirect_lang() {
		global $wp_rewrite;

		self::$polylang->options['redirect_lang'] = 1;
		self::$polylang->model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		$this->assertEquals( home_url( '/en/' ), get_permalink( self::$home_en ) );
		$this->assertEquals( home_url( '/fr/' ), get_permalink( self::$home_fr ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/' ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_front_page' );
		$this->assertEquals( home_url( '/en/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( array( get_post( self::$home_fr ) ),  $GLOBALS['wp_query']->posts );
		$this->assertEmpty( redirect_canonical( home_url( '/fr/' ), false ) );
	}

	function test_page_for_posts() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/articles/' ) );

		$this->assertQueryTrue( 'is_home', 'is_posts_page' );
		$this->assertEquals( home_url( '/en/posts/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( array( get_post( $fr ) ),  $GLOBALS['wp_query']->posts );

		self::$polylang->curlang = self::$polylang->model->get_language( 'en' ); // brute force
		$this->go_to( home_url( '/en/posts/' ) );

		$this->assertQueryTrue( 'is_home', 'is_posts_page' );
		$this->assertEquals( home_url( '/fr/articles/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEquals( array( get_post( $en ) ),  $GLOBALS['wp_query']->posts );
	}

	function test_paged_page_for_posts() {
		update_option( 'posts_per_page', 2 ); // to avoid creating too much posts

		$en = $this->factory->post->create_many( 3 );
		foreach ( $en as $post_id ) {
			self::$polylang->model->post->set_language( $post_id, 'en' );
		}

		$fr = $this->factory->post->create_many( 3 );
		foreach ( $fr as $post_id ) {
			self::$polylang->model->post->set_language( $post_id, 'fr' );
		}

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/articles/page/2/' ) );

		$this->assertQueryTrue( 'is_home', 'is_posts_page', 'is_paged' );
		$this->assertEquals( home_url( '/en/posts/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
		$this->assertCount( 1, $GLOBALS['wp_query']->posts );

		self::$polylang->curlang = self::$polylang->model->get_language( 'en' ); // brute force
		$this->go_to( home_url( '/en/posts/page/2/' ) );

		$this->assertQueryTrue( 'is_home', 'is_posts_page', 'is_paged' );
		$this->assertEquals( home_url( '/fr/articles/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertCount( 1, $GLOBALS['wp_query']->posts );
	}

	// bug fixed in 1.8beta3 : non translated posts page always link to the static front page even when they should not
	function test_untranslated_page_for_posts() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/articles/' ) );

		$this->assertEmpty( self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'de' ) ) );
	}

	// bug fixed in 1.8.1
	function test_paged_front_page_with_hide_default() {
		global $wp_rewrite;

		self::$polylang->options['hide_default'] = 1;
		self::$polylang->model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		self::$polylang->curlang = self::$polylang->model->get_language( 'en' ); // brute force
		$this->go_to( home_url( '/page/2/' ) );
		the_post();

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_paged', 'is_front_page' );
		$this->assertEquals( home_url( '/fr/accueil/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEquals( 'en2',  get_the_content() );
		$this->assertEmpty( redirect_canonical( home_url( '/page/2/' ), false ) );
	}

	// for good measure test that too
	function test_front_page_with_redirect_lang_and_hide_default() {
		global $wp_rewrite;

		self::$polylang->options['redirect_lang'] = 1;
		self::$polylang->model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/page/2/' ) );
		the_post();

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_paged', 'is_front_page' );
		$this->assertEquals( home_url( '/en/' ), self::$polylang->links->get_translation_url( self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( 'fr2',  get_the_content() );
		$this->assertEmpty( redirect_canonical( home_url( '/fr/page/2/' ), false ) );
	}

	function test_post_states() {
		ob_start();
		_post_states( get_post( self::$home_en ) );
		$this->assertNotFalse( strpos( ob_get_clean(), "<span class='post-state'>Front Page</span>" ) );
		ob_start();
		_post_states( get_post( self::$home_fr ) ); // in option
		$this->assertNotFalse( strpos( ob_get_clean(), "<span class='post-state'>Front Page</span>" ) );
		ob_start();
		_post_states( get_post( self::$posts_en ) );
		$this->assertNotFalse( strpos( ob_get_clean(), "<span class='post-state'>Posts Page</span>" ) );
		ob_start();
		_post_states( get_post( self::$posts_fr ) ); // in option
		$this->assertNotFalse( strpos( ob_get_clean(), "<span class='post-state'>Posts Page</span>" ) );

		// test for standard pages too
		$en = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		ob_start();
		_post_states( get_post( $en ) );
		$out = ob_get_clean();
		$this->assertFalse( strpos( $out, "<span class='post-state'>Front Page</span>" ) );
		$this->assertFalse( strpos( $out, "<span class='post-state'>Posts Page</span>" ) );

		ob_start();
		_post_states( get_post( $fr ) );
		$out = ob_get_clean();
		$this->assertFalse( strpos( $out, "<span class='post-state'>Front Page</span>" ) );
		$this->assertFalse( strpos( $out, "<span class='post-state'>Posts Page</span>" ) );
	}

	function test_update_page_on_front() {
		global $wp_settings_errors;
		unset( $wp_settings_errors ); // just in case

		// attempt to assign an untranslated message
		$en = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );
		update_option( 'page_on_front', $en );

		// test option in unchanged
		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		$this->assertEquals( self::$home_fr, get_option( 'page_on_front' ) );

		// test error message
		$this->assertNotEmpty( get_settings_errors() );

		unset( $wp_settings_errors ); // cleanup
	}

	// bug fixed in 2.0
	function test_get_post_type_archive_link_for_posts() {
		// get_post_type_archive_link() supports posts since WP 4.5
		if ( version_compare( $GLOBALS['wp_version'], '4.5', '<' ) ) {
			$this->markTestSkipped();
		}

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		$this->assertEquals( 'http://example.org/fr/articles/', get_post_type_archive_link( 'post' ) );
	}

	function test_deactivate_editor_for_page_for_posts() {
		$en = $this->factory->post->create( array( 'post_content' => '' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		update_option( 'page_for_posts', $en );

		$fr = $this->factory->post->create( array( 'post_content' => '' ) ); // Content must be empty to deactivate editor.
		self::$polylang->model->post->set_language( $fr, 'fr' );
		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		do_action( 'add_meta_boxes', 'page', get_post( $fr ) );
		$this->assertFalse( post_type_supports( 'page', 'editor' ) );

		ob_start();
		do_action( 'edit_form_after_title', get_post( $fr ) );
		$this->assertContains( 'You are currently editing the page that shows your latest posts.', ob_get_clean() );
	}
}
