<?php

class Static_Pages_Test extends PLL_UnitTestCase {

	public $structure = '/%postname%/';
	protected static $home_en;
	protected static $home_fr;
	protected static $home_de;
	protected static $posts_en;
	protected static $posts_fr;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE_formal' );
		self::create_language( 'es_ES' );

		// page on front
		self::$home_en = $en = self::factory()->post->create(
			array(
				'post_title'   => 'home',
				'post_type'    => 'page',
				'post_content' => 'en1<!--nextpage-->en2',
			)
		);
		self::$model->post->set_language( $en, 'en' );

		self::$home_fr = $fr = self::factory()->post->create(
			array(
				'post_title'   => 'accueil',
				'post_type'    => 'page',
				'post_content' => 'fr1<!--nextpage-->fr2',
			)
		);
		self::$model->post->set_language( $fr, 'fr' );

		self::$home_de = $de = self::factory()->post->create(
			array(
				'post_title'   => 'startseite',
				'post_type'    => 'page',
				'post_content' => 'de1<!--nextpage-->de2',
			)
		);
		self::$model->post->set_language( $de, 'de' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr', 'de' ) );

		// page for posts
		// intentionally do not create one in German
		self::$posts_en = $en = self::factory()->post->create( array( 'post_title' => 'posts', 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		self::$posts_fr = $fr = self::factory()->post->create( array( 'post_title' => 'articles', 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		self::$model->clean_languages_cache();
	}

	public function set_up() {
		parent::set_up();


		self::$model->options['hide_default'] = 0;
		self::$model->options['redirect_lang'] = 0;

		global $wp_rewrite;

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );

		self::$model->post->register_taxonomy(); // needs this for 'lang' query var

		$this->links_model = self::$model->get_links_model();
		$this->links_model->init();
	}

	private function init_test( $env = 'frontend' ) {
		$pll_admin = new PLL_Admin( $this->links_model );
		$pll_admin->links = new PLL_Admin_Links( $pll_admin );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', self::$home_fr );
		update_option( 'page_for_posts', self::$posts_fr );

		if ( 'frontend' === $env ) {
			// Go to frontend.
			$this->pll_env = new PLL_Frontend( $this->links_model );
		} else {
			// Go to admin.
			$this->pll_env = $pll_admin;
		}

		$this->pll_env->init();
		$this->pll_env->static_pages->pll_language_defined();
	}

	public static function wpTearDownAfterClass() {
		wp_delete_post( self::$home_en, true );
		wp_delete_post( self::$home_fr, true );
		wp_delete_post( self::$home_de, true );
		wp_delete_post( self::$posts_en, true );
		wp_delete_post( self::$posts_fr, true );

		parent::wpTearDownAfterClass();
	}

	public function test_front_page_with_default_options() {
		global $wp_rewrite;

		$this->init_test();
		self::$model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		$this->assertEquals( home_url( '/en/home/' ), get_permalink( self::$home_en ) );
		$this->assertEquals( home_url( '/fr/accueil/' ), get_permalink( self::$home_fr ) );

		$this->pll_env->curlang = self::$model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/accueil/' ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_front_page' );
		$this->assertEquals( home_url( '/en/home/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'en' ) ) );
		$this->assertEquals( array( get_post( self::$home_fr ) ), $GLOBALS['wp_query']->posts );
		$this->assertEmpty( redirect_canonical( home_url( '/fr/accueil/' ), false ) );
	}

	public function test_front_page_with_query() {
		global $wp_rewrite;

		$this->init_test();
		self::$model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		$this->pll_env->curlang = self::$model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/accueil/?query=1' ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_front_page' );
		$this->assertEquals( home_url( '/en/home/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'en' ) ) );
		$this->assertEquals( array( get_post( self::$home_fr ) ), $GLOBALS['wp_query']->posts );
		$this->assertEmpty( redirect_canonical( home_url( '/fr/accueil/?query=1' ), false ) );
	}

	public function test_paged_front_page() {
		global $wp_rewrite;

		$this->init_test();
		self::$model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		$this->pll_env->curlang = self::$model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/accueil/page/2/' ) );
		the_post();

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_paged', 'is_front_page' );
		$this->assertEquals( home_url( '/en/home/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'en' ) ) );
		$this->assertEquals( 'fr2', get_the_content() );

		$this->pll_env->curlang = self::$model->get_language( 'en' ); // brute force
		$this->go_to( home_url( '/en/home/page/2/' ) );
		the_post();

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_paged', 'is_front_page' );
		$this->assertEquals( home_url( '/fr/accueil/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEquals( 'en2', get_the_content() );
		$this->assertEmpty( redirect_canonical( home_url( '/en/home/page/2/' ), false ) );
	}

	public function test_front_page_with_hide_default() {
		global $wp_rewrite;

		$this->init_test();
		self::$model->options['hide_default'] = 1;
		self::$model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		$this->assertEquals( home_url( '/' ), get_permalink( self::$home_en ) );
		$this->assertEquals( home_url( '/fr/accueil/' ), get_permalink( self::$home_fr ) );

		$this->pll_env->curlang = self::$model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/accueil/' ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_front_page' );
		$this->assertEquals( home_url( '/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'en' ) ) );
		$this->assertEquals( array( get_post( self::$home_fr ) ), $GLOBALS['wp_query']->posts );
		$this->assertEmpty( redirect_canonical( home_url( '/fr/accueil/' ), false ) );

		$this->pll_env->curlang = self::$model->get_language( 'en' ); // brute force
		$this->go_to( home_url( '/' ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_front_page' );
		$this->assertEquals( home_url( '/fr/accueil/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEquals( array( get_post( self::$home_en ) ), $GLOBALS['wp_query']->posts );
		$this->assertEmpty( redirect_canonical( home_url( '/' ), false ) );
		$this->assertEquals( home_url( '/' ), redirect_canonical( home_url( '/en/home/' ), false ) );
	}

	/**
	 * Special case for default permalinks.
	 */
	public function test_front_page_with_hide_default_plain_permalinks() {
		global $wp_rewrite;

		$this->init_test();

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '' );
		$wp_rewrite->flush_rules();

		self::$model->options['hide_default'] = 1;
		$this->pll_env->links_model = self::$model->get_links_model();
		self::$model->clean_languages_cache();

		$this->assertEquals( home_url( '/' ), get_permalink( self::$home_en ) ); // trailing slash kept for home page
		$this->assertEquals( home_url( '?page_id=' . self::$home_fr . '&lang=fr' ), get_permalink( self::$home_fr ) );

		$this->pll_env->curlang = self::$model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '?page_id=' . self::$home_fr ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_front_page' );
		$this->assertEquals( home_url( '/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'en' ) ) );
		$this->assertEquals( array( get_post( self::$home_fr ) ), $GLOBALS['wp_query']->posts );
		$this->assertEmpty( redirect_canonical( home_url( '/fr/accueil/' ), false ) );

		$this->pll_env->curlang = self::$model->get_language( 'en' ); // brute force
		$this->go_to( home_url( '/' ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_front_page' );
		$this->assertEquals( home_url( '?page_id=' . self::$home_fr . '&lang=fr' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEquals( array( get_post( self::$home_en ) ), $GLOBALS['wp_query']->posts );
		$this->assertEmpty( redirect_canonical( home_url( '/' ), false ) );
	}

	public function test_paged_front_page_plain_permalinks() {
		global $wp_rewrite;

		$this->init_test();

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '' );
		$wp_rewrite->flush_rules();

		self::$model->options['hide_default'] = 1;
		$this->pll_env->links_model = self::$model->get_links_model();
		self::$model->clean_languages_cache();

		$this->pll_env->curlang = self::$model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '?page_id=' . self::$home_fr . '&lang=fr&page=2' ) );
		the_post();

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_paged', 'is_front_page' );
		$this->assertEquals( home_url( '/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'en' ) ) );
		$this->assertEquals( 'fr2', get_the_content() );

		$this->pll_env->curlang = self::$model->get_language( 'en' ); // brute force
		$this->go_to( home_url( '?page=2' ) );
		the_post();

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_paged', 'is_front_page' );
		$this->assertEquals( home_url( '?page_id=' . self::$home_fr . '&lang=fr' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEquals( 'en2', get_the_content() );
		$this->assertEmpty( redirect_canonical( home_url( '?page=2' ), false ) );
	}

	public function test_front_page_with_redirect_lang() {
		global $wp_rewrite;

		$this->init_test();
		self::$model->options['redirect_lang'] = 1;
		self::$model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		$this->assertEquals( home_url( '/en/' ), get_permalink( self::$home_en ) );
		$this->assertEquals( home_url( '/fr/' ), get_permalink( self::$home_fr ) );

		$this->pll_env->curlang = self::$model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/' ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_front_page' );
		$this->assertEquals( home_url( '/en/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'en' ) ) );
		$this->assertEquals( array( get_post( self::$home_fr ) ), $GLOBALS['wp_query']->posts );
		$this->assertEmpty( redirect_canonical( home_url( '/fr/' ), false ) );
	}

	public function test_page_for_posts() {
		$this->init_test();

		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$this->pll_env->curlang = self::$model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/articles/' ) );

		$this->assertQueryTrue( 'is_home', 'is_posts_page' );
		$this->assertEquals( home_url( '/en/posts/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'en' ) ) );
		$this->assertEquals( array( get_post( $fr ) ), $GLOBALS['wp_query']->posts );

		$this->pll_env->curlang = self::$model->get_language( 'en' ); // brute force
		$this->go_to( home_url( '/en/posts/' ) );

		$this->assertQueryTrue( 'is_home', 'is_posts_page' );
		$this->assertEquals( home_url( '/fr/articles/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEquals( array( get_post( $en ) ), $GLOBALS['wp_query']->posts );
	}

	public function test_paged_page_for_posts() {
		$this->init_test();

		update_option( 'posts_per_page', 2 ); // to avoid creating too much posts

		$en = self::factory()->post->create_many( 3 );
		foreach ( $en as $post_id ) {
			self::$model->post->set_language( $post_id, 'en' );
		}

		$fr = self::factory()->post->create_many( 3 );
		foreach ( $fr as $post_id ) {
			self::$model->post->set_language( $post_id, 'fr' );
		}

		$this->pll_env->curlang = self::$model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/articles/page/2/' ) );

		$this->assertQueryTrue( 'is_home', 'is_posts_page', 'is_paged' );
		$this->assertEquals( home_url( '/en/posts/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'en' ) ) );
		$this->assertCount( 1, $GLOBALS['wp_query']->posts );

		$this->pll_env->curlang = self::$model->get_language( 'en' ); // brute force
		$this->go_to( home_url( '/en/posts/page/2/' ) );

		$this->assertQueryTrue( 'is_home', 'is_posts_page', 'is_paged' );
		$this->assertEquals( home_url( '/fr/articles/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertCount( 1, $GLOBALS['wp_query']->posts );
	}

	/**
	 * Bug fixed in 1.8beta3 : non translated posts page always link to the static front page even when they should not
	 */
	public function test_untranslated_page_for_posts() {
		$this->init_test();

		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$this->pll_env->curlang = self::$model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/articles/' ) );

		$this->assertEmpty( $this->pll_env->links->get_translation_url( self::$model->get_language( 'de' ) ) );
	}

	/**
	 * Bug fixed in 1.8.1.
	 */
	public function test_paged_front_page_with_hide_default() {
		global $wp_rewrite;

		$this->init_test();
		self::$model->options['hide_default'] = 1;
		self::$model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		$this->pll_env->curlang = self::$model->get_language( 'en' ); // brute force
		$this->go_to( home_url( '/page/2/' ) );
		the_post();

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_paged', 'is_front_page' );
		$this->assertEquals( home_url( '/fr/accueil/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'fr' ) ) );
		$this->assertEquals( 'en2', get_the_content() );
		$this->assertEmpty( redirect_canonical( home_url( '/page/2/' ), false ) );
	}

	/**
	 * For good measure test that too.
	 */
	public function test_front_page_with_redirect_lang_and_hide_default() {
		global $wp_rewrite;

		$this->init_test();
		self::$model->options['redirect_lang'] = 1;
		self::$model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		$this->pll_env->curlang = self::$model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/page/2/' ) );
		the_post();

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_paged', 'is_front_page' );
		$this->assertEquals( home_url( '/en/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'en' ) ) );
		$this->assertEquals( 'fr2', get_the_content() );
		$this->assertEmpty( redirect_canonical( home_url( '/fr/page/2/' ), false ) );
	}

	public function test_post_states() {
		$this->init_test();

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
		$en = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

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

	/**
	 * Bug fixed in 2.0.
	 */
	public function test_get_post_type_archive_link_for_posts() {
		$this->init_test();

		$this->pll_env->curlang = self::$model->get_language( 'fr' );
		$this->assertSame( 'http://example.org/fr/articles/', get_post_type_archive_link( 'post' ) );

		$this->pll_env->curlang = self::$model->get_language( 'en' );
		$this->assertSame( 'http://example.org/en/posts/', get_post_type_archive_link( 'post' ) );
	}

	/**
	 * Bug introduced and fixed in 2.3.
	 */
	public function test_archives_with_front_page_with_redirect_lang() {
		global $wp_rewrite;

		$this->init_test();

		$en = self::factory()->post->create( array( 'post_title' => 'test', 'post_date' => '2007-09-04 00:00:00', 'post_author' => 1 ) );
		self::$model->post->set_language( $en, 'en' );

		self::$model->options['redirect_lang'] = 1;
		self::$model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		$this->go_to( home_url( '/en/author/admin/' ) );
		$this->assertEquals( array( get_post( $en ) ), $GLOBALS['wp_query']->posts );

		$this->go_to( home_url( '/en/2007/' ) );
		$this->assertEquals( array( get_post( $en ) ), $GLOBALS['wp_query']->posts );

		$this->go_to( home_url( '/en/feed/' ) );
		$this->assertEquals( array( get_post( $en ) ), $GLOBALS['wp_query']->posts );

		$this->go_to( home_url( '/en/?s=test' ) );
		$this->assertEquals( array( get_post( $en ) ), $GLOBALS['wp_query']->posts );
	}

	/**
	 * Bug introduced and fixed in 2.3.
	 */
	public function test_post_type_archives_with_front_page_with_redirect_lang() {
		global $wp_rewrite;

		$this->init_test();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(

		self::$model->options['post_types'] = array(
			'trcpt' => 'trcpt',
		);

		register_post_type( 'trcpt', array( 'public' => true, 'has_archive' => true ) ); // translated custom post type with archives

		$en = self::factory()->post->create( array( 'post_type' => 'trcpt' ) );
		self::$model->post->set_language( $en, 'en' );

		self::$model->options['redirect_lang'] = 1;
		self::$model->clean_languages_cache();

		$wp_rewrite->flush_rules();

		$this->go_to( home_url( '/en/trcpt/' ) );
		$this->assertEquals( array( get_post( $en ) ), $GLOBALS['wp_query']->posts );

		_unregister_post_type( 'trcpt' );
	}

	/**
	 * Add custom query var.
	 *
	 * @param string[] $query_vars Query vars.
	 */
	public function extra_query_vars( $query_vars ) {
		$query_vars[] = 'action';
		return $query_vars;
	}

	/**
	 * Add custom root rewrite rule.
	 *
	 * @param string[] $rules Extra rewrite rules.
	 */
	public function extra_root_rewrite_rules( $rules ) {
		$rules['^testing/?$'] = 'index.php?action=testing';
		return $rules;
	}

	/**
	 * Bug introduced in 2.3 and fixed in 2.3.1.
	 */
	public function test_extra_query_var_with_front_page_with_query_with_redirect_lang() {
		global $wp_rewrite;

		$this->init_test();

		add_filter( 'query_vars', array( $this, 'extra_query_vars' ) );
		add_filter( 'root_rewrite_rules', array( $this, 'extra_root_rewrite_rules' ), 1 );

		self::$model->options['hide_default'] = 1;
		self::$model->options['redirect_lang'] = 1;
		self::$model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		$this->pll_env->curlang = self::$model->get_language( 'en' ); // brute force
		$this->go_to( home_url( '/testing/' ) );

		$this->assertFalse( is_front_page() );
	}

	public function test_front_page_with_orderby_with_redirect_lang() {
		global $wp_rewrite;

		$this->init_test();
		self::$model->options['redirect_lang'] = 1;
		self::$model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		$this->pll_env->curlang = self::$model->get_language( 'fr' ); // brute force
		$this->go_to( home_url( '/fr/?orderby=price' ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_front_page' );
		$this->assertEquals( home_url( '/en/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'en' ) ) );
		$this->assertEquals( array( get_post( self::$home_fr ) ), $GLOBALS['wp_query']->posts );
		$this->assertEmpty( redirect_canonical( home_url( '/fr/?orderby=price' ), false ) );
	}

	public function test_page_for_posts_on_frontend() {
		$this->init_test();

		$this->pll_env->curlang = self::$model->get_language( 'fr' );
		$this->assertSame( self::$posts_fr, get_option( 'page_for_posts' ), 'Expected the page for posts on FR frontend to be ' . self::$posts_fr );

		$this->pll_env->curlang = self::$model->get_language( 'en' );
		$this->assertSame( self::$posts_en, get_option( 'page_for_posts' ), 'Expected the page for posts on EN frontend to be ' . self::$posts_en );
	}

	public function test_page_for_posts_on_admin() {
		$this->init_test( 'admin' );

		$this->pll_env->curlang = self::$model->get_language( 'fr' );
		$this->assertSame( self::$posts_fr, get_option( 'page_for_posts' ), 'Expected the page for posts on FR admin to be ' . self::$posts_fr );

		$this->pll_env->curlang = self::$model->get_language( 'en' );
		$this->assertSame( self::$posts_en, get_option( 'page_for_posts' ), 'Expected the page for posts on EN admin to be ' . self::$posts_en );
	}

	public function test_untranslated_front_page() {
		global $wp_rewrite;

		$this->init_test();
		self::$model->clean_languages_cache();

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		$es = self::factory()->post->create();
		self::$model->post->set_language( $es, 'es' );

		$this->pll_env->curlang = self::$model->get_language( 'es' ); // brute force
		$this->go_to( home_url( '/es/' ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_home', 'is_front_page' );
		$this->assertEquals( home_url( '/en/home/' ), $this->pll_env->links->get_translation_url( self::$model->get_language( 'en' ) ) );
		$this->assertEquals( array( get_post( $es ) ), $GLOBALS['wp_query']->posts );
	}

	/**
	 * @dataProvider page_deletion_provider
	 * @ticket 1701
	 * @see https://github.com/polylang/polylang-pro/issues/1701
	 *
	 * @param string $delete   Name of the class property that stores the ID of the page to delete.
	 * @param bool   $trash    Either the page should be deleted or trashed.
	 * @param string $lang     Code of the current language.
	 * @param array  $expected {
	 *     Values to expect.
	 *
	 *     @var string     $show_on_front  Value of the option `show_on_front`.
	 *     @var string|int $page_on_front  Name of the class property holding the value of the option `page_on_front`. Can also be `0`.
	 *     @var string|int $page_for_posts Name of the class property holding the value of the option `page_for_posts`. Can also be `0`.
	 * }
	 * @return void
	 */
	public function test_page_deletion( $delete, $trash, $lang, $expected ) {
		$this->init_test( 'admin' );

		$this->pll_env->curlang = self::$model->get_language( $lang );
		wp_delete_post( self::$$delete, ! $trash );

		$expected_page_on_front  = is_string( $expected['page_on_front'] ) ? self::${$expected['page_on_front']} : $expected['page_on_front'];
		$expected_page_for_posts = is_string( $expected['page_for_posts'] ) ? self::${$expected['page_for_posts']} : $expected['page_for_posts'];

		// Assert the real values by shunting `translate_page_for_posts()` and `translate_page_on_front()`.
		$GLOBALS['wp_current_filter']['test'] = 'before_delete_post';
		$this->assertSame( $expected['show_on_front'], get_option( 'show_on_front' ) );
		$this->assertSame( $expected_page_on_front, get_option( 'page_on_front' ) );
		$this->assertSame( $expected_page_for_posts, get_option( 'page_for_posts' ) );
		unset( $GLOBALS['wp_current_filter']['test'] );
	}

	/**
	 * @ticket 1701
	 * @see https://github.com/polylang/polylang-pro/issues/1701
	 *
	 * @return void
	 */
	public function test_page_deletion_without_translations() {
		// Delete translations.
		self::$model->post->delete_translation( self::$home_en );
		self::$model->post->delete_translation( self::$home_de, true );
		self::$model->post->delete_translation( self::$posts_en, true );

		$this->init_test( 'admin' );

		$this->pll_env->curlang = self::$model->get_language( 'fr' );
		wp_delete_post( self::$home_fr, true );

		// Assert the real values by shunting `translate_page_for_posts()` and `translate_page_on_front()`.
		$GLOBALS['wp_current_filter']['test'] = 'before_delete_post';
		$this->assertSame( 'posts', get_option( 'show_on_front' ) );
		$this->assertSame( 0, get_option( 'page_on_front' ) );
		$this->assertSame( self::$posts_fr, get_option( 'page_for_posts' ) );
		unset( $GLOBALS['wp_current_filter']['test'] );

		wp_delete_post( self::$posts_fr, true );

		// Assert the real values by shunting `translate_page_for_posts()` and `translate_page_on_front()`.
		$GLOBALS['wp_current_filter']['test'] = 'before_delete_post';
		$this->assertSame( 'posts', get_option( 'show_on_front' ) );
		$this->assertSame( 0, get_option( 'page_on_front' ) );
		$this->assertSame( 0, get_option( 'page_for_posts' ) );
		unset( $GLOBALS['wp_current_filter']['test'] );
	}

	public function page_deletion_provider() {
		return array(
			'Delete page on front not in option'  => array(
				'delete'   => 'home_de',
				'trash'    => false,
				'lang'     => 'de',
				'expected' => array(
					'show_on_front'  => 'page',
					'page_on_front'  => 'home_fr',
					'page_for_posts' => 'posts_fr',
				),
			),
			'Trash page on front not in option'   => array(
				'delete'   => 'home_en',
				'trash'    => true,
				'lang'     => 'en',
				'expected' => array(
					'show_on_front'  => 'page',
					'page_on_front'  => 'home_fr',
					'page_for_posts' => 'posts_fr',
				),
			),
			'Delete page on front in option'      => array(
				'delete'   => 'home_fr',
				'trash'    => false,
				'lang'     => 'fr',
				'expected' => array(
					'show_on_front'  => 'posts',
					'page_on_front'  => 0,
					'page_for_posts' => 'posts_fr',
				),
			),
			'Delete page for posts not in option' => array(
				'delete'   => 'posts_en',
				'trash'    => false,
				'lang'     => 'en',
				'expected' => array(
					'show_on_front'  => 'page',
					'page_on_front'  => 'home_fr',
					'page_for_posts' => 'posts_fr',
				),
			),
			'Delete page for posts in option'     => array(
				'delete'   => 'posts_fr',
				'trash'    => false,
				'lang'     => 'fr',
				'expected' => array(
					'show_on_front'  => 'page',
					'page_on_front'  => 'home_fr',
					'page_for_posts' => 0,
				),
			),
		);
	}
}
