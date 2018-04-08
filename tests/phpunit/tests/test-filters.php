<?php

class Filters_Test extends PLL_UnitTestCase {
	public $structure = '/%postname%/';

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE_formal' );

		require_once PLL_INC . '/api.php';
		$GLOBALS['polylang'] = &self::$polylang;

		self::$polylang->model->post->register_taxonomy(); // needs this for 'lang' query var
	}

	function setUp() {
		parent::setUp();

		self::$polylang->filters_links = new PLL_Frontend_Filters_Links( self::$polylang );
	}

	function test_get_pages() {
		foreach ( $this->factory->post->create_many( 3, array( 'post_type' => 'page' ) ) as $page ) {
			self::$polylang->model->post->set_language( $page, 'en' );
		}

		foreach ( $this->factory->post->create_many( 3, array( 'post_type' => 'page' ) ) as $page ) {
			self::$polylang->model->post->set_language( $page, 'fr' );
		}

		// one post for good measure
		$p = $this->factory->post->create();
		self::$polylang->model->post->set_language( $p, 'fr' );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		new PLL_Frontend_Filters( self::$polylang );

		// request all pages
		$pages = get_pages();
		$fr_page_ids = $pages = wp_list_pluck( $pages, 'ID' );
		$languages = wp_list_pluck( array_map( array( self::$polylang->model->post, 'get_language' ), $pages ), 'slug' );
		$this->assertCount( 3, $pages );
		$this->assertEquals( array( 'fr' ), array_values( array_unique( $languages ) ) );

		// request less pages than exist
		$pages = get_pages( array( 'number' => 2 ) );
		$pages = wp_list_pluck( $pages, 'ID' );
		$languages = wp_list_pluck( array_map( array( self::$polylang->model->post, 'get_language' ), $pages ), 'slug' );
		$this->assertCount( 2, $pages );
		$this->assertEquals( array( 'fr' ), array_values( array_unique( $languages ) ) );

		// request more pages than exist
		$pages = get_pages( array( 'number' => 20 ) );
		$pages = wp_list_pluck( $pages, 'ID' );
		$languages = wp_list_pluck( array_map( array( self::$polylang->model->post, 'get_language' ), $pages ), 'slug' );
		$this->assertCount( 3, $pages );
		$this->assertEquals( array( 'fr' ), array_values( array_unique( $languages ) ) );

		$fr_page_id = reset( $fr_page_ids ); // Just one valid page id
		$pages = get_pages( array( 'number' => 1, 'exclude' => array( $fr_page_id ) ) );
		$this->assertCount( 1, $pages );

		// Warning fixed in 2.3.2
		$pages = get_pages( array( 'number' => 1, 'exclude' => $fr_page_id ) );
		$this->assertCount( 1, $pages );
	}

	function test_get_posts() {
		foreach ( $this->factory->post->create_many( 3, array() ) as $p ) {
			self::$polylang->model->post->set_language( $p, 'en' );
		}

		foreach ( $this->factory->post->create_many( 3, array() ) as $p ) {
			self::$polylang->model->post->set_language( $p, 'fr' );
		}

		$de = $this->factory->post->create();
		self::$polylang->model->post->set_language( $de, 'de' );

		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		self::$polylang->init();
		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		$posts = get_posts( array( 'fields' => 'ids' ) );
		$languages = wp_list_pluck( array_map( array( self::$polylang->model->post, 'get_language' ), $posts ), 'slug' );
		$this->assertCount( 3, $posts );
		$this->assertEquals( array( 'fr' ), array_values( array_unique( $languages ) ) );

		$posts = get_posts( array( 'fields' => 'ids', 'lang' => 'en' ) );
		$languages = wp_list_pluck( array_map( array( self::$polylang->model->post, 'get_language' ), $posts ), 'slug' );
		$this->assertCount( 3, $posts );
		$this->assertEquals( array( 'en' ), array_values( array_unique( $languages ) ) );

		$posts = get_posts( array( 'fields' => 'ids', 'lang' => 'en,de' ) );
		$this->assertCount( 4, $posts );

		$posts = get_posts( array( 'fields' => 'ids', 'lang' => array( 'fr', 'de' ) ) );
		$this->assertCount( 4, $posts );
	}

	function test_sticky_posts() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );
		stick_post( $en );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );
		stick_post( $fr );

		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		self::$polylang->init();
		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		$sticky = get_option( 'sticky_posts' );
		$this->assertCount( 1, $sticky );
		$this->assertEquals( $fr, reset( $sticky ) ); // the sticky post
	}

	function test_get_comments() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );
		$en = $this->factory->comment->create( array( 'comment_post_ID' => $en, 'comment_approved' => '1' ) );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );
		$fr = $this->factory->comment->create( array( 'comment_post_ID' => $fr, 'comment_approved' => '1' ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		new PLL_Frontend_Filters( self::$polylang );
		$comments = get_comments();
		$this->assertCount( 1, $comments );
		$this->assertEquals( get_comment( $fr )->comment_ID, reset( $comments )->comment_ID );

		// don't use the same default args as above to avoid hitting the cache
		$comments = get_comments( array( 'fields' => 'ids', 'lang' => 'en' ) );
		$this->assertCount( 1, $comments );
		$this->assertEquals( get_comment( $en )->comment_ID, reset( $comments ) );
	}

	function test_get_terms() {
		$fr = $this->factory->term->create( array( 'taxonomy' => 'post_tag' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'post_tag' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$de = $this->factory->term->create( array( 'taxonomy' => 'post_tag' ) );
		self::$polylang->model->term->set_language( $de, 'de' );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		new PLL_Frontend_Filters( self::$polylang );
		$terms = get_terms( 'post_tag', array( 'fields' => 'ids', 'hide_empty' => false ) );
		$this->assertEqualSets( array( $fr ), $terms );

		$terms = get_terms( 'post_tag', array( 'fields' => 'ids', 'hide_empty' => false, 'lang' => 'en' ) );
		$this->assertEqualSets( array( $en ), $terms );

		$terms = get_terms( 'post_tag', array( 'fields' => 'ids', 'hide_empty' => false, 'lang' => 'en,fr' ) );
		$this->assertEqualSets( array( $en, $fr ), $terms );

		$terms = get_terms( 'post_tag', array( 'fields' => 'ids', 'hide_empty' => false, 'lang' => array( 'fr', 'de' ) ) );
		$this->assertEqualSets( array( $de, $fr ), $terms );

		$terms = get_terms( 'post_tag', array( 'fields' => 'ids', 'hide_empty' => false, 'lang' => '' ) );
		$this->assertEqualSets( array( $en, $fr, $de ), $terms );
	}

	function test_adjacent_post_and_archives() {
		for ( $i = 1; $i <= 3; $i++ ) {
			$m = 2 * $i - 1;
			$en[ $i ] = $this->factory->post->create( array( 'post_date' => "2012-0$m-01 12:00:00" ) );
			self::$polylang->model->post->set_language( $en[ $i ], 'en' );

			$m = 2 * $i;
			$fr[ $i ] = $this->factory->post->create( array( 'post_date' => "2012-0$m-01 12:00:00" ) );
			self::$polylang->model->post->set_language( $fr[ $i ], 'fr' );
		}

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		new PLL_Frontend_Filters( self::$polylang );
		$this->go_to( get_permalink( $fr[2] ) );

		$this->assertEquals( get_post( $fr[1] ), get_previous_post() );
		$this->assertEquals( get_post( $fr[3] ), get_next_post() );

		ob_start();
		wp_get_archives();
		$archives = ob_get_clean();

		$this->assertFalse( strpos( $archives, 'January 2012' ) );
		$this->assertNotFalse( strpos( $archives, 'February 2012' ) );
	}

	// Bug fixed in v1.9
	function test_adjacent_post_and_archives_for_untranslated_post_type() {
		register_post_type( 'cpt', array( 'public' => true, 'has_archive' => true ) ); // *untranslated* custom post type with archives

		for ( $m = 1; $m <= 3; $m++ ) {
			$p[ $m ] = $this->factory->post->create( array( 'post_type' => 'cpt', 'post_date' => "2012-0$m-01 12:00:00" ) );
		}

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		new PLL_Frontend_Filters( self::$polylang );
		$this->go_to( get_permalink( $p[2] ) );

		$this->assertEquals( get_post( $p[1] ), get_previous_post() );
		$this->assertEquals( get_post( $p[3] ), get_next_post() );

		ob_start();
		wp_get_archives( array( 'post_type' => 'cpt' ) );
		$archives = ob_get_clean();
		$this->assertNotFalse( strpos( $archives, 'February 2012' ) );

		_unregister_post_type( 'cpt' );
	}

	function test_language_attributes_for_valid_locale() {
		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		new PLL_Frontend_Filters( self::$polylang );

		$this->expectOutputString( 'lang="fr-FR"' );
		language_attributes();
	}

	function test_language_attributes_for_invalid_locale() {
		self::$polylang->curlang = self::$polylang->model->get_language( 'de' );
		new PLL_Frontend_Filters( self::$polylang );

		$this->expectOutputString( 'lang="de-DE"' );
		language_attributes();
	}

	function test_save_post() {
		new PLL_Frontend_Filters( self::$polylang );
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );

		$post_id = $this->factory->post->create();
		$this->assertEquals( 'en', self::$polylang->model->post->get_language( $post_id )->slug );

		$_REQUEST['lang'] = 'fr';
		$post_id = $this->factory->post->create();
		$this->assertEquals( 'fr', self::$polylang->model->post->get_language( $post_id )->slug );

		unset( $_REQUEST );
	}

	function test_save_page_with_parent() {
		new PLL_Frontend_Filters( self::$polylang );
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );

		$parent = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $parent, 'fr' );
		$post_id = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => $parent ) );

		$this->assertEquals( 'fr', self::$polylang->model->post->get_language( $parent )->slug );
		$this->assertEquals( 'fr', self::$polylang->model->post->get_language( $post_id )->slug );
	}

	function test_save_term() {
		new PLL_Frontend_Filters( self::$polylang );
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );

		$term_id = $this->factory->category->create();
		$this->assertEquals( 'en', self::$polylang->model->term->get_language( $term_id )->slug );

		$_REQUEST['lang'] = 'fr';
		$term_id = $this->factory->category->create();
		$this->assertEquals( 'fr', self::$polylang->model->term->get_language( $term_id )->slug );

		unset( $_REQUEST );
	}

	function test_save_category_with_parent() {
		new PLL_Frontend_Filters( self::$polylang );
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );

		$parent = $this->factory->category->create();
		self::$polylang->model->term->set_language( $parent, 'fr' );
		$term_id = $this->factory->category->create( array( 'parent' => $parent ) );

		$this->assertEquals( 'fr', self::$polylang->model->term->get_language( $parent )->slug );
		$this->assertEquals( 'fr', self::$polylang->model->term->get_language( $term_id )->slug );
	}

	function test_get_pages_language_filter() {
		$en = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->filters = new PLL_Filters( self::$polylang );

		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );
		$pages = get_pages();
		$this->assertCount( 1, $pages );
		$this->assertEquals( $en, reset( $pages )->ID );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		$pages = get_pages();
		$this->assertCount( 1, $pages );
		$this->assertEquals( $fr, reset( $pages )->ID );

		$pages = get_pages( array( 'lang' => 'en' ) );
		$this->assertCount( 1, $pages );
		$this->assertEquals( $en, reset( $pages )->ID );

		$pages = get_pages( array( 'lang' => 'fr' ) );
		$this->assertCount( 1, $pages );
		$this->assertEquals( $fr, reset( $pages )->ID );

		// Bug fixed in 1.9.3
		$this->assertCount( 2, get_pages( array( 'lang' => '' ) ) );
	}

	function test_site_title_in_password_change_email() {
		// FIXME The test works locally but fails on Travis CI (also needs WP 4.6+)
		$this->markTestSkipped();

		$language = self::$polylang->model->get_language( 'fr' );
		$_mo = new PLL_MO();
		$_mo->add_entry( $_mo->make_entry( get_bloginfo( 'name' ), 'Mon site' ) );
		$_mo->export_to_db( $language );

		reset_phpmailer_instance();
		$user_id = self::factory()->user->create();
		update_user_meta( $user_id, 'locale', 'fr_FR' );

		self::$polylang = new PLL_Admin( self::$polylang->links_model );
		self::$polylang->filters = new PLL_Admin_Filters( self::$polylang );

		$userdata = array(
			'ID'        => $user_id,
			'user_pass' => 'new password',
		);
		wp_update_user( $userdata );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertNotFalse( strpos( $mailer->get_sent()->subject, 'Mon site' ) );
		$this->assertNotFalse( strpos( $mailer->get_sent()->body, 'Mon site' ) );
		reset_phpmailer_instance();
	}

	function _action_pre_get_posts() {
		$terms = get_terms( 'post_tag', array( 'hide_empty' => false ) );
		$language = self::$polylang->model->term->get_language( $terms[0]->term_id );

		$this->assertCount( 1, $terms );
		$this->assertEquals( 'fr', $language->slug );
	}

	// Bug fixed in 2.3.5
	function test_get_terms_inside_query() {
		$en = $this->factory->term->create( array( 'taxonomy' => 'post_tag' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'post_tag' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		self::$polylang->init();
		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		add_action( 'pre_get_posts', array( $this, '_action_pre_get_posts' ) );
		$posts = get_posts();
	}
}
