<?php

class Sync_Test extends PLL_UnitTestCase {
	static $editor, $author;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
		self::$author = $factory->user->create( array( 'role' => 'author' ) );
	}

	function setUp() {
		parent::setUp();

		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests
	}

	function test_copy_taxonomies() {
		$tag_en = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'slug' => 'tag_en' ) );
		self::$polylang->model->term->set_language( $tag_en, 'en' );

		$tag_fr = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'slug' => 'tag_fr' ) );
		self::$polylang->model->term->set_language( $tag_fr, 'fr' );

		self::$polylang->model->term->save_translations( $tag_en, array( 'en' => $tag_en, 'fr' => $tag_fr ) );

		$untranslated = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $untranslated, 'en' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'fr' ) );

		$from = $this->factory->post->create();
		self::$polylang->model->post->set_language( $from, 'en' );
		wp_set_post_terms( $from, array( 'tag_en' ), 'post_tag' ); // Assigned by slug
		wp_set_post_terms( $from, array( $untranslated, $en ), 'category' ); // Assigned by term_id
		set_post_format( $from, 'aside' );

		$to = $this->factory->post->create();
		self::$polylang->model->post->set_language( $to, 'fr' );

		self::$polylang->model->post->save_translations( $from, array( 'fr' => $to ) );

		// copy
		$sync = new PLL_Admin_Sync( self::$polylang );
		$sync->taxonomies->copy( $from, $to, 'fr' ); // copy

		$this->assertEquals( array( $tag_fr ), wp_get_post_terms( $to, 'post_tag', array( 'fields' => 'ids' ) ) );
		$this->assertEquals( array( $fr ), wp_get_post_terms( $to, 'category', array( 'fields' => 'ids' ) ) );
		$this->assertEquals( 'aside', get_post_format( $to ) );

		// sync
		self::$polylang->options['sync'] = array( 'taxonomies' );
		wp_set_post_terms( $from, array( 'tag_en' ), 'post_tag' );
		wp_set_post_terms( $from, array( $untranslated, $en ), 'category' );

		$this->assertEquals( array( $tag_en ), wp_get_post_terms( $from, 'post_tag', array( 'fields' => 'ids' ) ) );
		$this->assertEqualSets( array( $untranslated, $en ), wp_get_post_terms( $from, 'category', array( 'fields' => 'ids' ) ) );
		$this->assertEquals( 'aside', get_post_format( $from ) );

		// remove taxonomies and post format and sync taxonomies
		wp_set_post_terms( $to, array(), 'post_tag' );
		wp_set_post_terms( $to, array(), 'category' );
		set_post_format( $to, '' );

		$this->assertEquals( array(), wp_get_post_terms( $from, 'post_tag', array( 'fields' => 'ids' ) ) );
		$this->assertEquals( array( $untranslated ), wp_get_post_terms( $from, 'category', array( 'fields' => 'ids' ) ) );
		$this->assertEquals( 'aside', get_post_format( $from ) );

		// sync post format
		self::$polylang->options['sync'] = array( 'post_format' );
		set_post_format( $to, '' );
		$this->assertFalse( get_post_format( $from ) );
	}

	function test_copy_custom_fields() {
		$from = $this->factory->post->create();
		self::$polylang->model->post->set_language( $from, 'en' );
		add_post_meta( $from, 'key', 'value' );

		$to = $this->factory->post->create();
		self::$polylang->model->post->set_language( $to, 'fr' );

		self::$polylang->model->post->save_translations( $from, array( 'fr' => $to ) );

		// copy
		$sync = new PLL_Admin_Sync( self::$polylang );
		$sync->post_metas->copy( $from, $to, 'fr' ); // copy
		$this->assertEquals( 'value', get_post_meta( $to, 'key', true ) );

		// sync
		self::$polylang->options['sync'] = array( 'post_meta' );
		$this->assertTrue( update_post_meta( $to, 'key', 'new_value' ) );
		$this->assertEquals( 'new_value', get_post_meta( $from, 'key', true ) );

		// remove custom field and sync
		$this->assertTrue( delete_post_meta( $to, 'key' ) );
		$this->assertEmpty( get_post_meta( $from, 'key', true ) );
	}

	function test_sync_multiple_custom_fields() {
		self::$polylang->options['sync'] = array( 'post_meta' );
		$sync = new PLL_Admin_Sync( self::$polylang );

		$from = $this->factory->post->create();
		self::$polylang->model->post->set_language( $from, 'en' );

		$to = $this->factory->post->create();
		self::$polylang->model->post->set_language( $to, 'fr' );

		// Add
		add_post_meta( $from, 'key', 'value1' );
		add_post_meta( $from, 'key', 'value2' );
		add_post_meta( $from, 'key', 'value3' );

		$sync->post_metas->copy( $from, $to, 'fr', true );
		$this->assertEqualSets( array( 'value1', 'value2', 'value3' ), get_post_meta( $to, 'key' ) );

		self::$polylang->model->post->save_translations( $from, array( 'fr' => $to ) );

		// Delete
		$this->assertTrue( delete_post_meta( $from, 'key', 'value3' ) );
		$this->assertEqualSets( array( 'value1', 'value2' ), get_post_meta( $to, 'key' ) );

		// Update
		$this->assertTrue( update_post_meta( $from, 'key', 'value4', 'value2' ) );
		$this->assertEqualSets( array( 'value1', 'value4' ), get_post_meta( $to, 'key' ) );

		// Add
		$mid = add_post_meta( $from, 'key', 'value5' );
		$this->assertEqualSets( array( 'value1', 'value4', 'value5' ), get_post_meta( $to, 'key' ) );

		// update_metadata_by_mid
		$this->assertTrue( update_meta( $mid, 'key', 'value6' ) );
		$this->assertEqualSets( array( 'value1', 'value4', 'value6' ), get_post_meta( $to, 'key' ) );

		// delete_metadata_by_mid
		$this->assertTrue( delete_meta( $mid ) );
		$this->assertEqualSets( array( 'value1', 'value4' ), get_post_meta( $to, 'key' ) );
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
		self::$polylang->posts = new PLL_CRUD_Posts( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );

		$_REQUEST = $_GET = array(
			'from_post' => $from,
			'new_lang'  => 'fr',
			'_wpnonce'  => wp_create_nonce( 'new-post-translation' ),
		);

		$to = $this->factory->post->create();

		$GLOBALS['pagenow'] = 'post-new.php';
		$GLOBALS['post'] = get_post( $to );

		do_action( 'add_meta_boxes', 'post', $GLOBALS['post'] ); // fires the copy
		$this->assertEquals( 'fr', self::$polylang->model->post->get_language( $to )->slug );
		$this->assertEquals( array( get_category( $fr ) ), get_the_category( $to ) );
		$this->assertEquals( 'value', get_post_meta( $to, 'key', true ) );
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

		self::$polylang->posts = new PLL_CRUD_Posts( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );

		$_REQUEST = $_GET = array(
			'from_post' => $from,
			'new_lang'  => 'fr',
			'post_type' => 'page',
			'_wpnonce'  => wp_create_nonce( 'new-post-translation' ),
		);

		$to = $this->factory->post->create( array( 'post_type' => 'page' ) );

		$GLOBALS['pagenow'] = 'post-new.php';
		$GLOBALS['post'] = get_post( $to );

		do_action( 'add_meta_boxes', 'page', $GLOBALS['post'] ); // fires the copy

		$this->assertEquals( 'fr', self::$polylang->model->post->get_language( $to )->slug );
		$this->assertEquals( $fr, wp_get_post_parent_id( $to ) );
		$this->assertEquals( 12, $GLOBALS['post']->menu_order );
		$this->assertEquals( 'full-width.php', get_page_template_slug( $to ) );
	}

	function test_save_post_with_sync() {
		self::$polylang->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// Attachment for thumbnail
		$filename = dirname( __FILE__ ) . '/../data/image.jpg';
		$thumbnail_id = $this->factory->attachment->create_upload_object( $filename );

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

		$key = add_post_meta( $from, 'key', 'value' );
		$metas[ $key ] = array( 'key' => 'key', 'value' => 'value' );

		self::$polylang->posts = new PLL_CRUD_Posts( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests
		$_REQUEST['sticky'] = 'sticky'; // sticky posts not managed by wp_insert_post
		add_post_meta( $from, '_thumbnail_id', $thumbnail_id );

		edit_post(
			array(
				'post_ID'       => $from,
				'post_format'   => 'aside',
				'meta'          => $metas,
				'_thumbnail_id' => $thumbnail_id, // Since WP 4.6
			)
		); // fires the sync
		stick_post( $from );

		$this->assertEquals( 'fr', self::$polylang->model->post->get_language( $to )->slug );
		$this->assertEqualSetsWithIndex( array( 'en' => $from, 'fr' => $to ), self::$polylang->model->post->get_translations( $from ) );
		$this->assertEquals( array( get_category( $fr ) ), get_the_category( $to ) );
		$this->assertEquals( '2007-09-04', get_the_date( 'Y-m-d', $to ) );
		$this->assertEquals( array( 'value' ), get_post_meta( $to, 'key' ) );
		$this->assertEquals( array( 'value' ), get_post_meta( $from, 'key' ) ); // Test reverse sync
		$this->assertEquals( $thumbnail_id, get_post_thumbnail_id( $to ) );
		$this->assertEquals( 'aside', get_post_format( $to ) );
		$this->assertTrue( is_sticky( $to ) );
	}

	function filter_theme_page_templates() {
		return array( 'templates/test.php' => 'Test Template Page' );
	}

	function test_save_page_with_sync() {
		$GLOBALS['post_type'] = 'page';
		add_filter( 'theme_page_templates', array( $this, 'filter_theme_page_templates' ) ); // Allow to test templates with themes without templates

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

		self::$polylang->posts = new PLL_CRUD_Posts( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		edit_post(
			array(
				'post_ID'       => $from,
				'page_template' => 'templates/test.php',
			)
		); // fires the sync

		$page = get_post( $to );

		$this->assertEquals( 'fr', self::$polylang->model->post->get_language( $to )->slug );
		$this->assertEqualSetsWithIndex( array( 'en' => $from, 'fr' => $to ), self::$polylang->model->post->get_translations( $from ) );
		$this->assertEquals( $fr, wp_get_post_parent_id( $to ) );
		$this->assertEquals( 12, $page->menu_order );
		$this->assertEquals( 'templates/test.php', get_page_template_slug( $to ) );
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
		self::$polylang->terms = new PLL_CRUD_Terms( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		$_REQUEST = $_POST = array(
			'action'           => 'add-tag',
			'term_lang_choice' => 'fr',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
			'term_tr_lang'     => array( 'en' => $from ),
		);

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );

		$to = $this->factory->term->create( array( 'taxonomy' => 'category' ) );

		$this->assertEquals( 'fr', self::$polylang->model->term->get_language( $to )->slug );
		$this->assertEqualSetsWithIndex( array( 'en' => $from, 'fr' => $to ), self::$polylang->model->term->get_translations( $from ) );
		$this->assertTrue( is_object_in_term( $fr, 'category', $to ) );
	}

	function test_save_term_with_parent_sync() {
		// Parents
		$en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'fr' ) );

		// child
		$from = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $from, 'en' );

		self::$polylang->filters_term = new PLL_Admin_Filters_Term( self::$polylang );
		self::$polylang->terms = new PLL_CRUD_Terms( self::$polylang );
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

	/**
	 * Test the child sync if we edit (delete) the translated term parent
	 * Bug fixed in 2.6.4
	 */
	function test_child_sync_if_delete_translated_term_parent() {
		// Children.
		$child_en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $child_en, 'en' );

		$child_fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $child_fr, 'fr' );

		self::$polylang->model->term->save_translations( $child_en, array( 'fr' => $child_fr ) );

		// Parents.
		$parent_en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $parent_en, 'en' );

		wp_update_term( $child_en, 'category', array( 'parent' => $parent_en ) );

		$parent_fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $parent_fr, 'fr' );

		self::$polylang->model->term->save_translations( $parent_en, array( 'fr' => $parent_fr ) );

		self::$polylang->terms = new PLL_CRUD_Terms( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		wp_update_term( $child_fr, 'category', array( 'parent' => $parent_fr ) );

		wp_update_term( $child_fr, 'category', array( 'parent' => 0 ) );

		$this->assertEquals( get_term( $child_en )->parent, 0 );
	}

	function test_create_post_translation_with_sync_post_date() {
		// source post
		$from = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$polylang->model->post->set_language( $from, 'en' );

		self::$polylang->posts = new PLL_CRUD_Posts( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		self::$polylang->options['sync'] = array( 'post_date' ); // Sync publish date

		$GLOBALS['pagenow'] = 'post-new.php';
		$_REQUEST = $_GET = array(
			'from_post' => $from,
			'new_lang'  => 'fr',
			'_wpnonce'  => wp_create_nonce( 'new-post-translation' ),
		);

		$to = $this->factory->post->create();
		clean_post_cache( $to ); // Necessary before calling get_post() below otherwise we don't get the synchronized date

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

		self::$polylang->posts = new PLL_CRUD_Posts( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		wp_update_post( array( 'ID' => $from ) ); // fires the sync
		$page = get_post( $to );

		$this->assertEquals( $fr, wp_get_post_parent_id( $to ) );
	}

	function test_create_post_translation_with_sync_date() {
		self::$polylang->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// source post
		$from = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$polylang->model->post->set_language( $from, 'en' );

		self::$polylang->posts = new PLL_CRUD_Posts( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );

		$_REQUEST = $_GET = array(
			'from_post' => $from,
			'new_lang'  => 'fr',
			'_wpnonce'  => wp_create_nonce( 'new-post-translation' ),
		);

		$to = $this->factory->post->create();

		$GLOBALS['pagenow'] = 'post-new.php';
		$GLOBALS['post'] = get_post( $to );

		do_action( 'add_meta_boxes', 'post', $GLOBALS['post'] ); // fires the copy
		clean_post_cache( $to ); // Usually WordPress will do it for us when the post will be saved

		$this->assertEquals( 'fr', self::$polylang->model->post->get_language( $to )->slug );
		$this->assertEquals( '2007-09-04 00:00:00', get_post( $to )->post_date );
	}

	function _add_term_meta_to_copy() {
		return array( 'key' );
	}

	function test_copy_term_metas() {
		$from = $this->factory->term->create();
		self::$polylang->model->term->set_language( $from, 'en' );
		add_term_meta( $from, 'key', 'value' );

		$to = $this->factory->term->create();
		self::$polylang->model->term->set_language( $to, 'fr' );
		self::$polylang->model->term->save_translations( $from, array( 'fr' => $to ) );

		add_filter( 'pll_copy_term_metas', array( $this, '_add_term_meta_to_copy' ) );

		// copy
		$sync = new PLL_Admin_Sync( self::$polylang );
		$sync->term_metas->copy( $from, $to, 'fr' ); // copy
		$this->assertEquals( 'value', get_term_meta( $to, 'key', true ) );

		// sync
		$this->assertTrue( update_term_meta( $to, 'key', 'new_value' ) );
		$this->assertEquals( 'new_value', get_term_meta( $from, 'key', true ) );

		// remove custom field and sync
		$this->assertTrue( delete_term_meta( $to, 'key' ) );
		$this->assertEmpty( get_term_meta( $from, 'key', true ) );
	}

	function test_sync_multiple_term_metas() {
		$sync = new PLL_Admin_Sync( self::$polylang );

		$from = $this->factory->term->create();
		self::$polylang->model->term->set_language( $from, 'en' );

		$to = $this->factory->term->create();
		self::$polylang->model->term->set_language( $to, 'fr' );

		self::$polylang->model->term->save_translations( $from, array( 'fr' => $to ) );

		add_filter( 'pll_copy_term_metas', array( $this, '_add_term_meta_to_copy' ) );

		// Add
		add_term_meta( $from, 'key', 'value1' );
		add_term_meta( $from, 'key', 'value2' );
		add_term_meta( $from, 'key', 'value3' );
		$this->assertEqualSets( array( 'value1', 'value2', 'value3' ), get_term_meta( $to, 'key' ) );

		// Delete
		$this->assertTrue( delete_term_meta( $from, 'key', 'value3' ) );
		$this->assertEqualSets( array( 'value1', 'value2' ), get_term_meta( $to, 'key' ) );

		// Update
		$this->assertTrue( update_term_meta( $from, 'key', 'value4', 'value2' ) );
		$this->assertEqualSets( array( 'value1', 'value4' ), get_term_meta( $to, 'key' ) );
	}

	function test_sync_post_with_metas_to_remove() {
		self::$polylang->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// Posts
		$to = $this->factory->post->create();
		self::$polylang->model->post->set_language( $to, 'fr' );

		$from = $this->factory->post->create();
		self::$polylang->model->post->set_language( $from, 'en' );

		self::$polylang->model->post->save_translations( $from, array( 'fr' => $to ) );

		add_post_meta( $to, 'key1', 'value' );
		add_post_meta( $to, 'key2', 'value1' );
		add_post_meta( $to, 'key2', 'value2' );
		$key = add_post_meta( $from, 'key2', 'value1' );
		$metas[ $key ] = array( 'key' => 'key2', 'value' => 'value1' );

		self::$polylang->posts = new PLL_CRUD_Posts( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		edit_post(
			array(
				'post_ID' => $from,
				'meta'    => $metas,
			)
		); // Fires the sync

		$this->assertEmpty( get_post_meta( $to, 'key1' ) );
		$this->assertEmpty( get_post_meta( $from, 'key1' ) );
		$this->assertEqualSets( array( 'value1' ), get_post_meta( $to, 'key2' ) );
		$this->assertEqualSets( array( 'value1' ), get_post_meta( $from, 'key2' ) );
	}

	function test_source_post_was_sticky_before_sync_was_active() {
		self::$polylang->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// Posts
		$to = $this->factory->post->create();
		self::$polylang->model->post->set_language( $to, 'fr' );

		$from = $this->factory->post->create();
		self::$polylang->model->post->set_language( $from, 'en' );

		self::$polylang->model->post->save_translations( $from, array( 'fr' => $to ) );

		stick_post( $from );

		self::$polylang->posts = new PLL_CRUD_Posts( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		$_REQUEST['sticky'] = 'sticky'; // sticky posts not managed by wp_insert_post
		edit_post(
			array(
				'post_ID' => $from,
				'sticky'  => 'sticky',
			)
		); // Fires the sync

		$this->assertTrue( is_sticky( $to ) );
	}

	function test_target_post_was_sticky_before_sync_was_active() {
		self::$polylang->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// Posts
		$to = $this->factory->post->create();
		self::$polylang->model->post->set_language( $to, 'fr' );

		$from = $this->factory->post->create();
		self::$polylang->model->post->set_language( $from, 'en' );

		self::$polylang->model->post->save_translations( $from, array( 'fr' => $to ) );

		stick_post( $to );

		self::$polylang->posts = new PLL_CRUD_Posts( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		edit_post( array( 'post_ID' => $from ) ); // Fires the sync

		$this->assertfalse( is_sticky( $to ) );
	}

	// Bug fixed in 2.3.2
	function test_delete_term() {
		self::$polylang->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// Categories
		$en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'fr' ) );

		// Posts
		$post_fr = $this->factory->post->create( array( 'post_category' => array( $fr ) ) );
		self::$polylang->model->post->set_language( $post_fr, 'fr' );

		$post_en = $this->factory->post->create( array( 'post_category' => array( $en ) ) );
		self::$polylang->model->post->set_language( $post_en, 'en' );

		self::$polylang->model->post->save_translations( $post_en, array( 'fr' => $post_fr ) );

		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );

		wp_delete_category( $fr );

		$this->assertEquals( array( $en ), wp_get_post_categories( $post_en ) );
	}

	// Bug fixed in 2.3.11
	function test_category_hierarchy() {
		// Categories
		$child_en = $en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$child_fr = $fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'fr' ) );

		$parent_en = $en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		wp_update_term( $child_en, 'category', array( 'parent' => $parent_en ) );

		$parent_fr = $fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'fr' ) );

		self::$polylang->terms = new PLL_CRUD_Terms( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		wp_update_term( $child_fr, 'category', array( 'parent' => $parent_fr ) );

		$term = get_term( $child_fr );
		$this->assertEquals( $parent_fr, $term->parent );

		// The bug fixed
		$term = get_term( $child_en );
		$this->assertEquals( $parent_en, $term->parent );
	}

	// Bug fixed in 2.5.2
	function test_sync_category_parent_modification() {
		// Parent 1
		$p1en = $en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$p1fr = $fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'fr' ) );

		// Parent 2
		$p2en = $en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$p2fr = $fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'fr' ) );

		// Child
		$child_en = $en = $this->factory->term->create( array( 'taxonomy' => 'category', 'parent' => $p1en ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$child_fr = $fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'parent' => $p1fr ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'fr' ) );

		self::$polylang->terms = new PLL_CRUD_Terms( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );
		wp_update_term( $child_fr, 'category', array( 'parent' => $p2fr ) );

		$term = get_term( $child_fr );
		$this->assertEquals( $p2fr, $term->parent );

		// The bug fixed
		$term = get_term( $child_en );
		$this->assertEquals( $p2en, $term->parent );
	}

	function test_if_cannot_synchronize() {
		add_filter( 'pll_pre_current_user_can_synchronize_post', '__return_null' ); // Enable capability check
		self::$polylang->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// Post format
		$this->factory->term->create( array( 'taxonomy' => 'post_format', 'name' => 'post-format-aside' ) ); // shouldn't WP do that ?

		// Attachment for thumbnail
		$filename = dirname( __FILE__ ) . '/../data/image.jpg';
		$thumbnail_id = $this->factory->attachment->create_upload_object( $filename );

		// Categories
		$en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'fr' ) );

		self::$polylang->posts = new PLL_CRUD_Posts( self::$polylang );
		self::$polylang->sync = new PLL_Admin_Sync( self::$polylang );

		// Posts
		wp_set_current_user( self::$editor );
		$to = $this->factory->post->create();
		self::$polylang->model->post->set_language( $to, 'fr' );

		wp_set_current_user( self::$author );
		$from = $this->factory->post->create();
		self::$polylang->model->post->set_language( $from, 'en' );
		self::$polylang->model->post->save_translations( $from, array( 'fr' => $to ) );

		// The author cannot override synchronized data
		wp_update_post( array( 'ID' => $from, 'post_category' => array( $en ), 'post_date' => '2007-09-04 00:00:00' ) );
		add_post_meta( $from, '_thumbnail_id', $thumbnail_id );
		set_post_format( $from, 'aside' );

		$this->assertNotEquals( array( get_category( $fr ) ), get_the_category( $to ) );
		$this->assertNotEquals( array( get_category( $en ) ), get_the_category( $from ) );
		$this->assertNotEquals( '2007-09-04', get_the_date( 'Y-m-d', $to ) );
		$this->assertNotEquals( '2007-09-04', get_the_date( 'Y-m-d', $from ) );
		$this->assertNotEquals( $thumbnail_id, get_post_thumbnail_id( $to ) );
		$this->assertNotEquals( $thumbnail_id, get_post_thumbnail_id( $from ) );
		$this->assertNotEquals( 'aside', get_post_format( $to ) );
		$this->assertNotEquals( 'aside', get_post_format( $from ) );

		// The editor can override synchronized data
		wp_set_current_user( self::$editor );

		wp_update_post( array( 'ID' => $from, 'post_category' => array( $en ), 'post_date' => '2007-09-04 00:00:00' ) );
		add_post_meta( $from, '_thumbnail_id', $thumbnail_id );
		set_post_format( $from, 'aside' );

		$this->assertEquals( array( get_category( $fr ) ), get_the_category( $to ) );
		$this->assertEquals( array( get_category( $en ) ), get_the_category( $from ) );
		$this->assertEquals( '2007-09-04', get_the_date( 'Y-m-d', $to ) );
		$this->assertEquals( '2007-09-04', get_the_date( 'Y-m-d', $from ) );
		$this->assertEquals( $thumbnail_id, get_post_thumbnail_id( $to ) );
		$this->assertEquals( $thumbnail_id, get_post_thumbnail_id( $from ) );
		$this->assertEquals( 'aside', get_post_format( $to ) );
		$this->assertEquals( 'aside', get_post_format( $from ) );
	}

	function test_slashes() {
		self::$polylang->options['sync'] = array( 'post_meta' );
		$sync = new PLL_Admin_Sync( self::$polylang );

		$key = '\_key';
		$slash_key = wp_slash( $key );

		$slash_2 = '\\\\';
		$slash_4 = '\\\\\\\\';

		// Create posts.
		$to = $this->factory->post->create();
		self::$polylang->model->post->set_language( $to, 'fr' );

		$from = $this->factory->post->create();
		self::$polylang->model->post->set_language( $from, 'en' );

		// Test copy().
		add_post_meta( $from, $slash_key, $slash_2 );
		$sync->post_metas->copy( $from, $to, 'fr' );
		$this->assertEquals( wp_unslash( $slash_2 ), get_post_meta( $to, $key, true ) );

		update_post_meta( $from, $slash_key, $slash_4 );
		$sync->post_metas->copy( $from, $to, 'fr' );
		$this->assertEquals( wp_unslash( $slash_4 ), get_post_meta( $to, $key, true ) );

		delete_post_meta( $from, $slash_key );
		delete_post_meta( $to, $slash_key );

		self::$polylang->model->post->save_translations( $from, array( 'fr' => $to ) );

		// Test add, update, delete.
		add_post_meta( $from, $slash_key, $slash_2 );
		$this->assertEquals( wp_unslash( $slash_2 ), get_post_meta( $to, $key, true ) );

		update_post_meta( $from, $slash_key, $slash_4 );
		$this->assertEquals( wp_unslash( $slash_4 ), get_post_meta( $to, $key, true ) );

		delete_post_meta( $from, $slash_key, $slash_4 );
		$this->assertEmpty( get_post_meta( $to, $key, true ) );
	}
}
