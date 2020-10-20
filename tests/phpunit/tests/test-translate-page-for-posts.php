<?php

class Translate_Page_For_Posts_Test extends PLL_UnitTestCase {
	static $posts_en, $posts_fr;
	public $structure = '/%postname%/';


	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once POLYLANG_DIR . '/include/api.php';
		$GLOBALS['polylang'] = &self::$polylang;

		// page for posts
		self::$posts_en = $en = self::factory()->post->create( array( 'post_title' => 'posts', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		self::$posts_fr = $fr = self::factory()->post->create( array( 'post_title' => 'articles', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );
	}

	function setUp() {
		parent::setUp();

		global $wp_rewrite;

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );

		self::$polylang->model->post->register_taxonomy(); // needs this for 'lang' query var

		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', self::$posts_en );

		// go to frontend
		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		self::$polylang->init();
	}

	public function test_translate_page_for_posts_on_default_language() {
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );

		$return = self::$polylang->static_pages->translate_page_for_posts( get_option( 'page_for_posts' ) );

		$this->assertEquals(self::$posts_en, $return );
	}

	public function test_translate_page_for_posts_on_secondary_language() {
		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );

		$return = self::$polylang->static_pages->translate_page_for_posts( get_option( 'page_for_posts' ) );

		$this->assertEquals(self::$posts_fr, $return );

	}

	public function test_translate_page_for_posts_when_page_for_posts_has_no_translations() {
		wp_delete_post( self::$posts_fr, true );
		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );

		$return = self::$polylang->static_pages->translate_page_for_posts( get_option( 'page_for_posts' ) );

		$this->assertEquals(self::$posts_en, $return );
	}
}
