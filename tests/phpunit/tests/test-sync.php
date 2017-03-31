<?php

class Sync_Test extends PLL_UnitTestCase {
	static $editor;

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
	}

	function setUp() {
		parent::setUp();

		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests
	}

	function tearDown() {
		parent::tearDown();

		$_REQUEST = $_GET = $_POST = array();
	}

	function test_copy_taxonomies() {
		$untranslated = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $untranslated, 'en' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'fr' ) );

		$from = $this->factory->post->create();
		self::$polylang->model->post->set_language( $from, 'en' );
		wp_set_post_terms( $from, array( $untranslated, $en ), 'category' );
		set_post_format( $from, 'aside' );

		$to = $this->factory->post->create();
		self::$polylang->model->post->set_language( $to, 'fr' );

		// copy
		$sync = new PLL_Admin_Sync( self::$polylang );
		$sync->copy_taxonomies( $from, $to, 'fr' ); // copy

		$this->assertEquals( array( $fr ), wp_get_post_terms( $to, 'category', array( 'fields' => 'ids' ) ) );
		$this->assertEquals( 'aside', get_post_format( $to ) );

		// sync
		self::$polylang->options['sync'] = array( 'taxonomies' );
		$sync->copy_taxonomies( $to, $from, 'en', true );

		$this->assertEquals( array( $untranslated, $en ), wp_get_post_terms( $from, 'category', array( 'fields' => 'ids' ) ) );
		$this->assertEquals( 'aside', get_post_format( $from ) );

		// remove taxonomies and post format and sync taxonomies
		wp_set_post_terms( $to, array(), 'category' );
		set_post_format( $to, '' );
		$sync->copy_taxonomies( $to, $from, 'en', true );

		$this->assertEquals( array( $untranslated ), wp_get_post_terms( $from, 'category', array( 'fields' => 'ids' ) ) );
		$this->assertEquals( 'aside', get_post_format( $from ) );

		// sync post format
		self::$polylang->options['sync'] = array( 'post_format' );
		$sync->copy_taxonomies( $to, $from, 'en', true );
		$this->assertFalse( get_post_format( $from ) );
	}

	function test_copy_custom_fields() {
		$from = $this->factory->post->create();
		self::$polylang->model->post->set_language( $from, 'en' );
		add_post_meta( $from, 'key', 'value' );

		$to = $this->factory->post->create();
		self::$polylang->model->post->set_language( $to, 'fr' );

		// copy
		$sync = new PLL_Admin_Sync( self::$polylang );
		$sync->copy_post_metas( $from, $to, 'fr' ); // copy

		$this->assertEquals( 'value', get_post_meta( $to, 'key', true ) );

		// sync
		update_post_meta( $to, 'key', 'new_value' );
		self::$polylang->options['sync'] = array( 'post_meta' );
		$sync->copy_post_metas( $to, $from, 'en', true );

		$this->assertEquals( 'new_value', get_post_meta( $from, 'key', true ) );

		// remove custom field and sync
		delete_post_meta( $to, 'key' );
		$sync->copy_post_metas( $to, $from, 'en', true );

		$this->assertEmpty( get_post_meta( $from, 'key', true ) );
	}

	function test_create_post_translation() {
		// categories
		$en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'fr' ) );

		// source post
		$from = $this->factory->post->create( array( 'post_category' => array( $en ) ) );
		self::$polylang->model->post->set_language( $from, 'en' );
		add_post_meta( $from, 'key', 'value' );
		add_post_meta( $from, '_thumbnail_id', 1234 );
		set_post_format( $from, 'aside' );
		stick_post( $from );

		self::$polylang->filters_post = new PLL_Admin_Filters_Post( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );

		$GLOBALS['pagenow'] = 'post-new.php';
		$_GET = array(
			'from_post' => $from,
			'new_lang'  => 'fr',
		);

		$to = $this->factory->post->create();
		do_action( 'add_meta_boxes', 'post', get_post( $to ) ); // fires the copy

		$this->assertEquals( 'fr', self::$polylang->model->post->get_language( $to )->slug );
		$this->assertEquals( array( get_category( $fr ) ), get_the_category( $to ) );
		$this->assertEquals( 'value' , get_post_meta( $to, 'key', true ) );
		$this->assertEquals( 1234, get_post_thumbnail_id( $to ) );
		$this->assertEquals( 'aside', get_post_format( $to ) );
		$this->assertTrue( is_sticky( $to ) );
	}

	function test_create_page_translation() {
		// parent pages
		$en = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'fr' ) );

		// source page
		$from = $this->factory->post->create( array( 'post_type' => 'page', 'menu_order' => 12, 'post_parent' => $en ) );
		self::$polylang->model->post->set_language( $from, 'en' );
		add_post_meta( $from, '_wp_page_template', 'full-width.php' );

		self::$polylang->filters_post = new PLL_Admin_Filters_Post( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );

		$GLOBALS['pagenow'] = 'post-new.php';
		$_GET = array(
			'from_post' => $from,
			'new_lang'  => 'fr',
			'post_type' => 'page',
		);

		$to = $this->factory->post->create( array( 'post_type' => 'page' ) );
		do_action( 'add_meta_boxes', 'page', $page = get_post( $to ) ); // fires the copy

		$this->assertEquals( 'fr', self::$polylang->model->post->get_language( $to )->slug );
		$this->assertEquals( $fr, wp_get_post_parent_id( $to ) );
		$this->assertEquals( 12, $page->menu_order );
		$this->assertEquals( 'full-width.php', get_page_template_slug( $to ) );
	}

	function test_save_post_with_sync() {
		self::$polylang->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// categories
		$en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'fr' ) );

		// posts
		$to = $this->factory->post->create();
		self::$polylang->model->post->set_language( $to, 'fr' );

		$from = $this->factory->post->create( array( 'post_category' => array( $en ), 'post_date' => '2007-09-04 00:00:00' ) );
		self::$polylang->model->post->set_language( $from, 'en' );

		self::$polylang->model->post->save_translations( $from, array( 'fr' => $to ) );

		add_post_meta( $from, 'key', 'value' );
		add_post_meta( $from, '_thumbnail_id', 1234 );
		set_post_format( $from, 'aside' );
		stick_post( $from );

		self::$polylang->filters_post = new PLL_Admin_Filters_Post( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests
		$_REQUEST['sticky'] = 'sticky'; // sticky posts not managed by wp_insert_post

		wp_update_post( array( 'ID' => $from ) ); // fires the sync

		$this->assertEquals( 'fr', self::$polylang->model->post->get_language( $to )->slug );
		$this->assertEqualSetsWithIndex( array( 'en' => $from, 'fr' => $to ), self::$polylang->model->post->get_translations( $from ) );
		$this->assertEquals( array( get_category( $fr ) ), get_the_category( $to ) );
		$this->assertEquals( '2007-09-04', get_the_date( 'Y-m-d', $to ) );
		$this->assertEquals( 'value' , get_post_meta( $to, 'key', true ) );
		$this->assertEquals( 1234, get_post_thumbnail_id( $to ) );
		$this->assertEquals( 'aside', get_post_format( $to ) );
		$this->assertTrue( is_sticky( $to ) );
	}

	function test_save_page_with_sync() {
		$GLOBALS['post_type'] = 'page';
		$stylesheet = get_option( 'stylesheet' ); // save default theme
		switch_theme( 'twentyfourteen' ); // Twenty Fifteen and Twenty Sixteen have no template
		if ( $templates = get_page_templates() ) {
			$template = reset( $templates );
		}

		self::$polylang->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// parent pages
		$en = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'fr' ) );

		// pages page
		$to = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $to, 'fr' );

		$from = $this->factory->post->create( array( 'post_type' => 'page', 'menu_order' => 12, 'post_parent' => $en ) );
		self::$polylang->model->post->set_language( $from, 'en' );

		self::$polylang->model->post->save_translations( $from, array( 'fr' => $to ) );

		if ( isset( $template ) ) {
			add_post_meta( $from, '_wp_page_template', $template );
		}

		self::$polylang->filters_post = new PLL_Admin_Filters_Post( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		wp_update_post( array( 'ID' => $from ) ); // fires the sync
		$page = get_post( $to );

		$this->assertEquals( 'fr', self::$polylang->model->post->get_language( $to )->slug );
		$this->assertEqualSetsWithIndex( array( 'en' => $from, 'fr' => $to ), self::$polylang->model->post->get_translations( $from ) );
		$this->assertEquals( $fr, wp_get_post_parent_id( $to ) );
		$this->assertEquals( 12, $page->menu_order );

		if ( isset( $template ) ) {
			$this->assertEquals( $template, get_page_template_slug( $to ) );
		} else {
			$this->markTestIncomplete();
		}

		unset( $GLOBALS['post_type'] );
		switch_theme( $stylesheet );
	}

	function test_save_term_with_sync_in_post() {
		self::$polylang->options['sync'] = array( 'taxonomies' );

		$from = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $from, 'en' );

		// posts
		$en = $this->factory->post->create( array( 'post_category' => array( $from ) ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		self::$polylang->filters_term = new PLL_Admin_Filters_Term( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		$_REQUEST = $_POST = array(
			'action'           => 'add-tag',
			'post_lang_choice' => 'fr',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
			'term_tr_lang'     => array( 'en' => $from ),
		);

		$to = $this->factory->term->create( array( 'taxonomy' => 'category' ) );

		$this->assertEquals( 'fr', self::$polylang->model->term->get_language( $to )->slug );
		$this->assertEqualSetsWithIndex( array( 'en' => $from, 'fr' => $to ), self::$polylang->model->term->get_translations( $from ) );
		$this->assertTrue( is_object_in_term( $fr, 'category' , $to ) );
	}

	function test_save_term_with_parent_sync() {
		self::$polylang->options['sync'] = array( 'taxonomies' );

		//parents
		$en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'fr' ) );

		// child
		$from = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $from, 'en' );

		self::$polylang->filters_term = new PLL_Admin_Filters_Term( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		$_REQUEST = $_POST = array(
			'action'           => 'add-tag',
			'post_lang_choice' => 'fr',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
			'term_tr_lang'     => array( 'en' => $from ),
			'parent'           => $fr,
		);

		$to = $this->factory->term->create( array( 'taxonomy' => 'category', 'parent' => $fr ) );
		$this->assertEquals( 'fr', self::$polylang->model->term->get_language( $to )->slug );
		$this->assertEqualSetsWithIndex( array( 'en' => $from, 'fr' => $to ), self::$polylang->model->term->get_translations( $from ) );
		$this->assertEquals( $fr, get_category( $to )->parent );
		$this->assertEquals( $en, get_category( $from )->parent );
	}

	function test_create_post_translation_with_sync_post_date() {
		$this->markTestSkipped(); // FIXME database error when updating date in PLL_Admin_Sync::save_post

		// source post
		$from = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$polylang->model->post->set_language( $from, 'en' );

		self::$polylang->filters_post = new PLL_Admin_Filters_Post( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		self::$polylang->options['sync'] = array( 'post_date' ); // Sync publish date

		$GLOBALS['pagenow'] = 'post-new.php';
		$_GET = array(
			'from_post' => $from,
			'new_lang'  => 'fr',
		);

		$to = $this->factory->post->create();

		$this->assertEquals( get_post( $from )->post_date, get_post( $to )->post_date );
		$this->assertEquals( get_post( $from )->post_date_gmt, get_post( $to )->post_date_gmt );
	}

	// Bug introduced in 2.0.8 and fixed in 2.1
	function test_quick_edit_with_sync_page_parent() {
		$_REQUEST['post_type'] = 'page';

		self::$polylang->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// parent pages
		$en = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'fr' ) );

		// pages page
		$to = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $to, 'fr' );

		$from = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => $en ) );
		self::$polylang->model->post->set_language( $from, 'en' );

		self::$polylang->model->post->save_translations( $from, array( 'fr' => $to ) );

		self::$polylang->filters_post = new PLL_Admin_Filters_Post( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		wp_update_post( array( 'ID' => $from ) ); // fires the sync
		$page = get_post( $to );

		$this->assertEquals( $fr, wp_get_post_parent_id( $to ) );

		unset( $_REQUEST['post_type'] );
	}

	function test_create_post_translation_with_sync_date() {
		self::$polylang->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// source post
		$from = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$polylang->model->post->set_language( $from, 'en' );

		self::$polylang->filters_post = new PLL_Admin_Filters_Post( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );

		$GLOBALS['pagenow'] = 'post-new.php';
		$_GET = array(
			'from_post' => $from,
			'new_lang'  => 'fr',
		);

		$to = $this->factory->post->create();
		do_action( 'add_meta_boxes', 'post', get_post( $to ) ); // fires the copy
		clean_post_cache( $to ); // Usually WordPress will do it for us when the post will be saved

		$this->assertEquals( 'fr', self::$polylang->model->post->get_language( $to )->slug );
		$this->assertEquals( '2007-09-04 00:00:00', get_post( $to )->post_date );
	}
}
