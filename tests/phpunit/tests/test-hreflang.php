<?php

class Hreflang_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_GB', array( 'slug' => 'uk' ) );
		self::create_language( 'en_US', array( 'slug' => 'us' ) );
		self::create_language( 'fr_FR' );

		require_once PLL_INC . '/api.php';
		$GLOBALS['polylang'] = &self::$polylang;

		self::$polylang->options['hide_default'] = 0;
	}

	function setUp() {
		parent::setUp();

		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		self::$polylang->init();

		// add links filter and de-activate the cache
		self::$polylang->filters_links = new PLL_Frontend_Filters_Links( self::$polylang );

		self::$polylang->links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		self::$polylang->links->cache->method( 'get' )->willReturn( false );

		self::$polylang->filters_links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		self::$polylang->filters_links->cache->method( 'get' )->willReturn( false );
	}

	function test_hreflang() {
		$uk = $this->factory->post->create();
		self::$polylang->model->post->set_language( $uk, 'uk' );

		$us = $this->factory->post->create();
		self::$polylang->model->post->set_language( $us, 'us' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $fr, compact( 'uk', 'us', 'fr' ) );

		// posts
		$this->go_to( get_permalink( $fr ) );

		ob_start();
		self::$polylang->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'hreflang="en-GB"' ) );
		$this->assertNotFalse( strpos( $out, 'hreflang="en-US"' ) );
		$this->assertNotFalse( strpos( $out, 'hreflang="fr"' ) );
		$this->assertFalse( strpos( $out, 'x-default' ) );

		// home page with x-default
		$this->go_to( home_url( '?lang=fr' ) );

		ob_start();
		self::$polylang->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'hreflang="en-GB"' ) );
		$this->assertNotFalse( strpos( $out, 'hreflang="en-US"' ) );
		$this->assertNotFalse( strpos( $out, 'hreflang="fr"' ) );
		$this->assertNotFalse( strpos( $out, 'x-default' ) );
	}

	function test_paginated_post() {
		$uk = $this->factory->post->create( array( 'post_content' => 'en1<!--nextpage-->en2' ) );
		self::$polylang->model->post->set_language( $uk, 'uk' );

		$us = $this->factory->post->create( array( 'post_content' => 'en1<!--nextpage-->en2' ) );
		self::$polylang->model->post->set_language( $us, 'us' );

		self::$polylang->model->post->save_translations( $uk, compact( 'uk', 'us' ) );

		// Page 1
		$this->go_to( get_permalink( $uk ) );

		ob_start();
		self::$polylang->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'hreflang="en-GB"' ) );

		// Page 2
		$this->go_to( add_query_arg( 'page', 2, get_permalink( $uk ) ) );

		ob_start();
		self::$polylang->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertEmpty( $out );
	}

	function test_paged_archive() {
		update_option( 'posts_per_page', 2 ); // to avoid creating too much posts

		$posts_us = $this->factory->post->create_many( 3 );
		$posts_uk = $this->factory->post->create_many( 3 );

		for( $i = 0; $i < 3; $i++ ) {
			self::$polylang->model->post->set_language( $us = $posts_us[ $i ], 'us' );
			self::$polylang->model->post->set_language( $uk = $posts_uk[ $i ], 'uk' );
			self::$polylang->model->post->save_translations( $uk, compact( 'uk', 'us' ) );
		}

		// Page 1
		$this->go_to( home_url( '?lang=us' ) );

		ob_start();
		self::$polylang->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'hreflang="en-GB"' ) );

		// Page 2
		$this->go_to( home_url( '?lang=us&paged=2' ) );

		ob_start();
		self::$polylang->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertEmpty( $out );
	}
}
