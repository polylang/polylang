<?php

class Translate_Page_For_Posts_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	function setUp() {
		parent::setUp();

		update_option( 'show_on_front', 'page' );

		$links_model = self::$model->get_links_model();
		$this->frontend = new PLL_Frontend( $links_model );
		$this->frontend->init();
		$this->frontend->static_pages = new PLL_Static_Pages( $this->frontend );
	}

	public function test_translate_page_for_posts_on_default_language() {
		// Pages for posts.
		$en = self::factory()->post->create( array( 'post_title' => 'posts', 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_title' => 'articles', 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		update_option( 'page_for_posts', $en );


		$this->frontend->curlang = self::$model->get_language( 'en' );

		$return = $this->frontend->static_pages->translate_page_for_posts( get_option( 'page_for_posts' ) );

		$this->assertEquals( $en, $return );
	}

	public function test_translate_page_for_posts_on_secondary_language() {
		// Pages for posts.
		$en = self::factory()->post->create( array( 'post_title' => 'posts', 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_title' => 'articles', 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		update_option( 'page_for_posts', $en );

		$this->frontend->curlang = self::$model->get_language( 'fr' );

		$return = $this->frontend->static_pages->translate_page_for_posts( get_option( 'page_for_posts' ) );

		$this->assertEquals( $fr, $return );

	}

	public function test_translate_page_for_posts_when_page_for_posts_has_no_translations() {
		// Only one page for posts.
		$en = self::factory()->post->create( array( 'post_title' => 'posts', 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		update_option( 'page_for_posts', $en );

		$this->frontend->curlang = self::$model->get_language( 'fr' );

		$return = $this->frontend->static_pages->translate_page_for_posts( get_option( 'page_for_posts' ) );

		$this->assertEquals( $en, $return );
	}
}
