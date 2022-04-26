<?php

class Admin_Static_Pages_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	public function set_up() {
		parent::set_up();

		$links_model = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );

		$this->pll_admin->links = new PLL_Admin_Links( $this->pll_admin );
		$this->pll_admin->static_pages = new PLL_Admin_Static_Pages( $this->pll_admin );
	}

	public function tear_down() {
		parent::tear_down();

		add_post_type_support( 'page', 'editor' );
	}

	public function test_deactivate_editor_for_page_for_posts() {
		$en = $this->factory->post->create( array( 'post_type' => 'page', 'post_content' => '' ) );
		self::$model->post->set_language( $en, 'en' );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', $en );

		$fr = $this->factory->post->create( array( 'post_type' => 'page', 'post_content' => '' ) ); // Content must be empty to deactivate editor.
		self::$model->post->set_language( $fr, 'fr' );
		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		self::$model->clean_languages_cache();

		$this->pll_admin->curlang = self::$model->get_language( 'fr' );
		do_action( 'pll_language_defined', $this->pll_admin->curlang->slug, $this->pll_admin->curlang );

		do_action( 'add_meta_boxes', 'page', get_post( $fr ) );

		ob_start();
		do_action( 'edit_form_after_title', get_post( $fr ) );
		$after_title = ob_get_clean();

		if ( $this->is_wp_58() ) {
			$this->assertTrue( post_type_supports( 'page', 'editor' ) );
			$this->assertStringNotContainsString( 'You are currently editing the page that shows your latest posts.', $after_title );
		} else {
			$this->assertFalse( post_type_supports( 'page', 'editor' ) );
			$this->assertStringContainsString( 'You are currently editing the page that shows your latest posts.', $after_title );
		}
	}

	/**
	 * Bug introduced in 2.2.2 and fixed in 2.2.3.
	 */
	public function test_editor_on_page() {
		$en = $this->factory->post->create( array( 'post_type' => 'page', 'post_content' => '' ) );
		self::$model->post->set_language( $en, 'en' );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', $en );

		$fr = $this->factory->post->create( array( 'post_type' => 'page', 'post_content' => '' ) ); // Content must be empty to deactivate editor.
		self::$model->post->set_language( $fr, 'fr' );
		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		self::$model->clean_languages_cache();

		$fr = $this->factory->post->create( array( 'post_type' => 'page', 'post_content' => '' ) ); // Content must be empty to deactivate editor.
		self::$model->post->set_language( $fr, 'fr' );

		$this->pll_admin->curlang = self::$model->get_language( 'fr' );
		do_action( 'add_meta_boxes', 'page', get_post( $fr ) );

		ob_start();
		do_action( 'edit_form_after_title', get_post( $fr ) );

		$this->assertTrue( post_type_supports( 'page', 'editor' ) );
		$this->assertStringNotContainsString( 'You are currently editing the page that shows your latest posts.', ob_get_clean() );
	}

	public function test_use_block_editor_for_post() {
		$en = $this->factory->post->create( array( 'post_type' => 'page', 'post_content' => '' ) );
		self::$model->post->set_language( $en, 'en' );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', $en );

		$fr = $this->factory->post->create( array( 'post_type' => 'page', 'post_content' => '' ) ); // Content must be empty to deactivate editor.
		self::$model->post->set_language( $fr, 'fr' );
		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		self::$model->clean_languages_cache();

		$this->pll_admin->curlang = self::$model->get_language( 'en' );
		do_action( 'pll_language_defined', $this->pll_admin->curlang->slug, $this->pll_admin->curlang );

		$this->assertSame( $this->is_wp_58(), use_block_editor_for_post( $en ) );

		$this->pll_admin->curlang = self::$model->get_language( 'fr' );
		do_action( 'pll_language_defined', $this->pll_admin->curlang->slug, $this->pll_admin->curlang );

		$this->assertSame( $this->is_wp_58(), use_block_editor_for_post( $fr ) );

		$page_id = $this->factory->post->create( array( 'post_type' => 'page', 'post_content' => '' ) );
		self::$model->post->set_language( $page_id, 'fr' );
		$this->assertTrue( use_block_editor_for_post( $page_id ) );

		$post_id = $this->factory->post->create( array( 'post_content' => '' ) );
		self::$model->post->set_language( $post_id, 'fr' );
		$this->assertTrue( use_block_editor_for_post( $post_id ) );
	}

	private function is_wp_58() {
		return version_compare( $GLOBALS['wp_version'], '5.8' ) >= 0;
	}
}
