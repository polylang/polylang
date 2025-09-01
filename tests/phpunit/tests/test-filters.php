<?php

class Filters_Test extends PLL_UnitTestCase {
	public $structure = '/%postname%/';

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE_formal' );
		self::create_language( 'es_ES' );

		self::require_api();
	}

	public function set_up() {
		parent::set_up();

		$links_model = self::$model->get_links_model();
		$this->frontend = new PLL_Frontend( $links_model );
		new PLL_Frontend_Filters_Links( $this->frontend );
	}

	public function test_get_pages() {
		foreach ( self::factory()->post->create_many( 3, array( 'post_type' => 'page' ) ) as $page ) {
			self::$model->post->set_language( $page, 'en' );
		}

		foreach ( self::factory()->post->create_many( 3, array( 'post_type' => 'page' ) ) as $page ) {
			self::$model->post->set_language( $page, 'fr' );
		}

		// one post for good measure
		$p = self::factory()->post->create();
		self::$model->post->set_language( $p, 'fr' );

		$this->frontend->curlang = self::$model->get_language( 'fr' );
		new PLL_Frontend_Filters( $this->frontend );

		// request all pages
		$pages = get_pages();
		$fr_page_ids = wp_list_pluck( $pages, 'ID' );
		$languages = wp_list_pluck( array_map( array( self::$model->post, 'get_language' ), $fr_page_ids ), 'slug' );
		$this->assertCount( 3, $fr_page_ids );
		$this->assertEquals( array( 'fr' ), array_values( array_unique( $languages ) ) );

		// request less pages than exist
		$pages = get_pages( array( 'number' => 2 ) );
		$pages = wp_list_pluck( $pages, 'ID' );
		$languages = wp_list_pluck( array_map( array( self::$model->post, 'get_language' ), $pages ), 'slug' );
		$this->assertCount( 2, $pages );
		$this->assertEquals( array( 'fr' ), array_values( array_unique( $languages ) ) );

		// request more pages than exist
		$pages = get_pages( array( 'number' => 20 ) );
		$pages = wp_list_pluck( $pages, 'ID' );
		$languages = wp_list_pluck( array_map( array( self::$model->post, 'get_language' ), $pages ), 'slug' );
		$this->assertCount( 3, $pages );
		$this->assertEquals( array( 'fr' ), array_values( array_unique( $languages ) ) );

		$fr_page_id = reset( $fr_page_ids ); // Just one valid page id
		$pages = get_pages( array( 'number' => 1, 'exclude' => array( $fr_page_id ) ) ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		$this->assertCount( 1, $pages );

		// Warning fixed in 2.3.2
		$pages = get_pages( array( 'number' => 1, 'exclude' => $fr_page_id ) ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		$this->assertCount( 1, $pages );
	}

	public function test_get_posts() {
		foreach ( self::factory()->post->create_many( 3, array() ) as $p ) {
			self::$model->post->set_language( $p, 'en' );
		}

		foreach ( self::factory()->post->create_many( 3, array() ) as $p ) {
			self::$model->post->set_language( $p, 'fr' );
		}

		$de = self::factory()->post->create();
		self::$model->post->set_language( $de, 'de' );

		$this->frontend->init();
		$this->frontend->curlang = self::$model->get_language( 'fr' );
		$posts = get_posts( array( 'fields' => 'ids' ) );
		$languages = wp_list_pluck( array_map( array( self::$model->post, 'get_language' ), $posts ), 'slug' );
		$this->assertCount( 3, $posts );
		$this->assertEquals( array( 'fr' ), array_values( array_unique( $languages ) ) );

		$posts = get_posts( array( 'fields' => 'ids', 'lang' => 'en' ) );
		$languages = wp_list_pluck( array_map( array( self::$model->post, 'get_language' ), $posts ), 'slug' );
		$this->assertCount( 3, $posts );
		$this->assertEquals( array( 'en' ), array_values( array_unique( $languages ) ) );

		$posts = get_posts( array( 'fields' => 'ids', 'lang' => 'en,de' ) );
		$this->assertCount( 4, $posts );

		$posts = get_posts( array( 'fields' => 'ids', 'lang' => array( 'fr', 'de' ) ) );
		$this->assertCount( 4, $posts );

		$args = array(
			'fields'   => 'ids',
			'tax_query'   => array(
				array(
					'taxonomy' => 'language',
					'terms'    => self::$model->get_language( 'en' )->term_id,
				),
			),
		);
		$posts = get_posts( $args );
		$languages = wp_list_pluck( array_map( array( self::$model->post, 'get_language' ), $posts ), 'slug' );
		$this->assertCount( 3, $posts );
		$this->assertEquals( array( 'en' ), array_values( array_unique( $languages ) ) );
	}

	public function test_sticky_posts() {
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );
		stick_post( $en );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );
		stick_post( $fr );

		$this->frontend->init();
		$this->frontend->curlang = self::$model->get_language( 'fr' );
		$sticky = get_option( 'sticky_posts' );
		$this->assertCount( 1, $sticky );
		$this->assertEquals( $fr, reset( $sticky ) ); // the sticky post
	}

	public function test_get_comments() {
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );
		$en = self::factory()->comment->create( array( 'comment_post_ID' => $en, 'comment_approved' => '1' ) );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );
		$fr = self::factory()->comment->create( array( 'comment_post_ID' => $fr, 'comment_approved' => '1' ) );

		$de = self::factory()->post->create();
		self::$model->post->set_language( $de, 'de' );
		$de = self::factory()->comment->create( array( 'comment_post_ID' => $de, 'comment_approved' => '1' ) );

		$this->frontend->curlang = self::$model->get_language( 'fr' );
		new PLL_Frontend_Filters( $this->frontend );
		$comments = get_comments();
		$this->assertCount( 1, $comments );
		$this->assertEquals( $fr, reset( $comments )->comment_ID );

		// don't use the same default args as above to avoid hitting the cache
		$comments = get_comments( array( 'fields' => 'ids', 'lang' => 'en' ) );
		$this->assertCount( 1, $comments );
		$this->assertEquals( $en, reset( $comments ) );

		$comments = get_comments( array( 'fields' => 'ids', 'lang' => 'en,fr' ) );
		$this->assertCount( 2, $comments );
		$this->assertEqualSets( array( $en, $fr ), $comments );
	}

	public function test_get_terms() {
		$fr = self::factory()->term->create( array( 'taxonomy' => 'post_tag' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$en = self::factory()->term->create( array( 'taxonomy' => 'post_tag' ) );
		self::$model->term->set_language( $en, 'en' );

		$de = self::factory()->term->create( array( 'taxonomy' => 'post_tag' ) );
		self::$model->term->set_language( $de, 'de' );

		$this->frontend->curlang = self::$model->get_language( 'fr' );
		new PLL_CRUD_Terms( $this->frontend );
		$terms = get_terms( array( 'taxonomy' => 'post_tag', 'fields' => 'ids', 'hide_empty' => false ) );
		$this->assertEqualSets( array( $fr ), $terms );

		$terms = get_terms( array( 'taxonomy' => 'post_tag', 'fields' => 'ids', 'hide_empty' => false, 'lang' => 'en' ) );
		$this->assertEqualSets( array( $en ), $terms );

		$terms = get_terms( array( 'taxonomy' => 'post_tag', 'fields' => 'ids', 'hide_empty' => false, 'lang' => 'en,fr' ) );
		$this->assertEqualSets( array( $en, $fr ), $terms );

		$terms = get_terms( array( 'taxonomy' => 'post_tag', 'fields' => 'ids', 'hide_empty' => false, 'lang' => array( 'fr', 'de' ) ) );
		$this->assertEqualSets( array( $de, $fr ), $terms );

		$terms = get_terms( array( 'taxonomy' => 'post_tag', 'fields' => 'ids', 'hide_empty' => false, 'lang' => '' ) );
		$this->assertEqualSets( array( $en, $fr, $de ), $terms );
	}

	public function test_adjacent_post_and_archives() {
		$en = array();
		$fr = array();

		for ( $i = 1; $i <= 3; $i++ ) {
			$m = 2 * $i - 1;
			$en[ $i ] = self::factory()->post->create( array( 'post_date' => "2012-0$m-01 12:00:00" ) );
			self::$model->post->set_language( $en[ $i ], 'en' );

			$m = 2 * $i;
			$fr[ $i ] = self::factory()->post->create( array( 'post_date' => "2012-0$m-01 12:00:00" ) );
			self::$model->post->set_language( $fr[ $i ], 'fr' );
		}

		$this->frontend->curlang = self::$model->get_language( 'fr' );
		new PLL_Frontend_Filters( $this->frontend );
		$this->go_to( get_permalink( $fr[2] ) );

		$this->assertEquals( get_post( $fr[1] ), get_previous_post() );
		$this->assertEquals( get_post( $fr[3] ), get_next_post() );

		ob_start();
		wp_get_archives();
		$archives = ob_get_clean();

		$this->assertFalse( strpos( $archives, 'January 2012' ) );
		$this->assertNotFalse( strpos( $archives, 'February 2012' ) );
	}

	/**
	 * Bug fixed in v1.9.
	 */
	public function test_adjacent_post_and_archives_for_untranslated_post_type() {
		register_post_type( 'cpt', array( 'public' => true, 'has_archive' => true ) ); // *untranslated* custom post type with archives

		$posts = array();

		for ( $m = 1; $m <= 3; $m++ ) {
			$posts[ $m ] = self::factory()->post->create( array( 'post_type' => 'cpt', 'post_date' => "2012-0$m-01 12:00:00" ) );
		}

		$this->frontend->curlang = self::$model->get_language( 'fr' );
		new PLL_Frontend_Filters( $this->frontend );
		$this->go_to( get_permalink( $posts[2] ) );

		$this->assertEquals( get_post( $posts[1] ), get_previous_post() );
		$this->assertEquals( get_post( $posts[3] ), get_next_post() );

		ob_start();
		wp_get_archives( array( 'post_type' => 'cpt' ) );
		$archives = ob_get_clean();
		$this->assertNotFalse( strpos( $archives, 'February 2012' ) );

		_unregister_post_type( 'cpt' );
	}

	public function test_language_attributes_for_valid_locale() {
		$this->frontend->curlang = self::$model->get_language( 'fr' );
		new PLL_Frontend_Filters( $this->frontend );

		$this->expectOutputString( 'lang="fr-FR"' );
		language_attributes();
	}

	public function test_language_attributes_for_invalid_locale() {
		$this->frontend->curlang = self::$model->get_language( 'de' );
		new PLL_Frontend_Filters( $this->frontend );

		$this->expectOutputString( 'lang="de-DE"' );
		language_attributes();
	}

	/**
	 * @ticket #2420
	 * @see https://github.com/polylang/polylang-pro/issues/2420
	 */
	public function test_language_attributes_for_login_page() {
		$this->frontend->curlang = self::$model->get_language( 'de' );
		new PLL_Frontend_Filters( $this->frontend );
		$GLOBALS['pagenow'] = 'wp-login.php';
		$_GET['wp_lang']    = 'fr_FR';

		$this->expectOutputString( 'lang="fr-FR"' );
		language_attributes();
	}

	public function test_save_post() {
		$this->frontend->posts = new PLL_CRUD_Posts( $this->frontend );
		$this->frontend->curlang = self::$model->get_language( 'en' );

		$post_id = self::factory()->post->create();
		$this->assertEquals( 'en', self::$model->post->get_language( $post_id )->slug );

		$_REQUEST['lang'] = 'fr';
		$post_id = self::factory()->post->create();
		$this->assertEquals( 'fr', self::$model->post->get_language( $post_id )->slug );
	}

	public function test_save_page_with_parent() {
		$this->frontend->posts = new PLL_CRUD_Posts( $this->frontend );
		$this->frontend->curlang = self::$model->get_language( 'en' );

		$parent = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $parent, 'fr' );
		$post_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_parent' => $parent ) );

		$this->assertEquals( 'fr', self::$model->post->get_language( $parent )->slug );
		$this->assertEquals( 'fr', self::$model->post->get_language( $post_id )->slug );
	}

	public function test_save_term() {
		new PLL_CRUD_Terms( $this->frontend );
		$this->frontend->curlang = self::$model->get_language( 'en' );

		$term_id = self::factory()->category->create();
		$this->assertEquals( 'en', self::$model->term->get_language( $term_id )->slug );

		$_REQUEST['lang'] = 'fr';
		$term_id = self::factory()->category->create();
		$this->assertEquals( 'fr', self::$model->term->get_language( $term_id )->slug );
	}

	public function test_save_category_with_parent() {
		new PLL_CRUD_Terms( $this->frontend );
		$this->frontend->curlang = self::$model->get_language( 'en' );

		$parent = self::factory()->category->create();
		self::$model->term->set_language( $parent, 'fr' );
		$term_id = self::factory()->category->create( array( 'parent' => $parent ) );

		$this->assertEquals( 'fr', self::$model->term->get_language( $parent )->slug );
		$this->assertEquals( 'fr', self::$model->term->get_language( $term_id )->slug );
	}

	public function test_get_pages_language_filter() {
		$en = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		$this->frontend->filters = new PLL_Filters( $this->frontend );

		$this->frontend->curlang = self::$model->get_language( 'en' );
		$pages = get_pages();
		$this->assertCount( 1, $pages );
		$this->assertEquals( $en, reset( $pages )->ID );

		$this->frontend->curlang = self::$model->get_language( 'fr' );
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

	public function _action_pre_get_posts() {
		$terms = get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) );
		$language = self::$model->term->get_language( $terms[0]->term_id );

		$this->assertCount( 1, $terms );
		$this->assertEquals( 'fr', $language->slug );
	}

	/**
	 * Bug fixed in 2.3.5.
	 */
	public function test_get_terms_inside_query() {
		$en = self::factory()->term->create( array( 'taxonomy' => 'post_tag' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->term->create( array( 'taxonomy' => 'post_tag' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$this->frontend->init();
		$this->frontend->curlang = self::$model->get_language( 'fr' );
		add_action( 'pre_get_posts', array( $this, '_action_pre_get_posts' ) );
		get_posts(); // fires the action and performs the assertions.
	}

	/**
	 * @ticket #1682 {@see https://github.com/polylang/polylang/issues/1682}
	 * @ticket #939  {@See https://github.com/polylang/polylang-wc/issues/939}
	 *
	 * @testWith ["1"]
	 *           ["20"]
	 *
	 * @param string $priority Priority of `comments_clauses` to apply.
	 */
	public function test_db_error_in_comments_clauses( $priority ) {
		global $wpdb;

		// Simulates WooCommerce adds a join comments clause.
		add_filter(
			'comments_clauses',
			function ( $clauses ) use ( $wpdb ) {
				$clauses['join'] .= " LEFT JOIN {$wpdb->posts} AS wp_posts_to_exclude_reviews ON comment_post_ID = wp_posts_to_exclude_reviews.ID ";

				return $clauses;
			},
			$priority
		);

		$this->frontend->filters = new PLL_Filters( $this->frontend );

		$this->assertCount( 0, get_comments( array( 'lang' => 'en' ) ) );
		$this->assertEmpty( $wpdb->last_error, 'It should not have database error.' );
	}
}
