<?php

class Hreflang_Test extends PLL_UnitTestCase {

	/**
	 * @param PLL_UnitTest_Factory $factory
	 * @return void
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_and_get( array( 'locale' => 'en_US', 'slug' => 'us' ) );
		$factory->language->create_and_get( array( 'locale' => 'en_GB', 'slug' => 'uk' ) );
		$factory->language->create_and_get( array( 'locale' => 'fr_FR' ) );
	}

	public function set_up() {
		parent::set_up();

		$options = array( 'hide_default' => 0 ); // To get a 'x-default' on the homepage.
		add_filter( 'pll_redirect_home', '__return_false' ); // To avoid a redirect due to the above option during the context setup.

		$this->frontend = ( new PLL_Context_Frontend( array( 'options' => $options ) ) )->get();
	}

	public function test_hreflang() {
		$posts = self::factory()->post->create_translated(
			array( 'lang' => 'us' ),
			array( 'lang' => 'uk' ),
			array( 'lang' => 'fr' )
		);

		// A Post.
		$this->go_to( get_permalink( $posts['fr'] ) );

		ob_start();
		$this->frontend->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'hreflang="en-GB"' ) );
		$this->assertNotFalse( strpos( $out, 'hreflang="en-US"' ) );
		$this->assertNotFalse( strpos( $out, 'hreflang="fr"' ) );
		$this->assertFalse( strpos( $out, 'x-default' ) );

		// The home page with x-default.
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
		$posts = self::factory()->post->create_translated(
			array( 'post_content' => 'en1<!--nextpage-->en2', 'lang' => 'us' ),
			array( 'post_content' => 'en1<!--nextpage-->en2', 'lang' => 'uk' )
		);

		// Page 1.
		$this->go_to( get_permalink( $posts['uk'] ) );

		ob_start();
		$this->frontend->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'hreflang="en-GB"' ) );

		// Page 2.
		$this->go_to( add_query_arg( 'page', 2, get_permalink( $posts['uk'] ) ) );

		ob_start();
		$this->frontend->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertEmpty( $out );
	}

	public function test_paged_archive() {
		update_option( 'posts_per_page', 2 ); // to avoid creating too many posts.

		for ( $i = 0; $i < 3; $i++ ) {
			self::factory()->post->create_translated(
				array( 'lang' => 'us' ),
				array( 'lang' => 'uk' )
			);
		}

		// Page 1.
		$this->go_to( home_url( '?lang=us' ) );

		ob_start();
		$this->frontend->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'hreflang="en-GB"' ) );

		// Page 2.
		$this->go_to( home_url( '?lang=us&paged=2' ) );

		ob_start();
		$this->frontend->filters_links->wp_head();
		$out = ob_get_clean();

		$this->assertEmpty( $out );
	}
}
