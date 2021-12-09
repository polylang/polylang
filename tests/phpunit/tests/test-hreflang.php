<?php

class Hreflang_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_GB', array( 'slug' => 'uk' ) );
		self::create_language( 'en_US', array( 'slug' => 'us' ) );
		self::create_language( 'fr_FR' );

		require_once POLYLANG_DIR . '/include/api.php';

		self::$model->options['hide_default'] = 0;
	}

	public function set_up() {
		parent::set_up();

		$links_model = self::$model->get_links_model();
		$this->frontend = new PLL_Frontend( $links_model );
		$this->frontend->init();

		// add links filter and de-activate the cache
		$this->frontend->filters_links = new PLL_Frontend_Filters_Links( $this->frontend );

		$this->frontend->links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		$this->frontend->links->cache->method( 'get' )->willReturn( false );

		$this->frontend->filters_links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		$this->frontend->filters_links->cache->method( 'get' )->willReturn( false );

		$GLOBALS['polylang'] = &$this->frontend;
	}

	public function test_hreflang() {
		$uk = $this->factory->post->create();
		self::$model->post->set_language( $uk, 'uk' );

		$us = $this->factory->post->create();
		self::$model->post->set_language( $us, 'us' );

		$fr = $this->factory->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $fr, compact( 'uk', 'us', 'fr' ) );

		// posts
		$this->go_to( get_permalink( $fr ) );

		ob_start();
		$this->frontend->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'hreflang="en-GB"' ) );
		$this->assertNotFalse( strpos( $out, 'hreflang="en-US"' ) );
		$this->assertNotFalse( strpos( $out, 'hreflang="fr"' ) );
		$this->assertFalse( strpos( $out, 'x-default' ) );

		// home page with x-default
		$this->go_to( home_url( '?lang=fr' ) );

		ob_start();
		$this->frontend->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'hreflang="en-GB"' ) );
		$this->assertNotFalse( strpos( $out, 'hreflang="en-US"' ) );
		$this->assertNotFalse( strpos( $out, 'hreflang="fr"' ) );
		$this->assertNotFalse( strpos( $out, 'x-default' ) );
	}

	public function test_paginated_post() {
		$uk = $this->factory->post->create( array( 'post_content' => 'en1<!--nextpage-->en2' ) );
		self::$model->post->set_language( $uk, 'uk' );

		$us = $this->factory->post->create( array( 'post_content' => 'en1<!--nextpage-->en2' ) );
		self::$model->post->set_language( $us, 'us' );

		self::$model->post->save_translations( $uk, compact( 'uk', 'us' ) );

		// Page 1
		$this->go_to( get_permalink( $uk ) );

		ob_start();
		$this->frontend->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'hreflang="en-GB"' ) );

		// Page 2
		$this->go_to( add_query_arg( 'page', 2, get_permalink( $uk ) ) );

		ob_start();
		$this->frontend->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertEmpty( $out );
	}

	public function test_paged_archive() {
		update_option( 'posts_per_page', 2 ); // to avoid creating too much posts

		$posts_us = $this->factory->post->create_many( 3 );
		$posts_uk = $this->factory->post->create_many( 3 );

		for ( $i = 0; $i < 3; $i++ ) {
			self::$model->post->set_language( $us = $posts_us[ $i ], 'us' );
			self::$model->post->set_language( $uk = $posts_uk[ $i ], 'uk' );
			self::$model->post->save_translations( $uk, compact( 'uk', 'us' ) );
		}

		// Page 1
		$this->go_to( home_url( '?lang=us' ) );

		ob_start();
		$this->frontend->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'hreflang="en-GB"' ) );

		// Page 2
		$this->go_to( home_url( '?lang=us&paged=2' ) );

		ob_start();
		$this->frontend->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertEmpty( $out );
	}
}
