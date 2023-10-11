<?php

class Sync_Test extends PLL_UnitTestCase {
	protected static $editor;
	protected static $author;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		$links_model     = self::$model->get_links_model();
		$pll_admin = new PLL_Admin( $links_model );
		$admin_default_term = new PLL_Admin_Default_Term( $pll_admin );
		$admin_default_term->add_hooks();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
		self::$author = $factory->user->create( array( 'role' => 'author' ) );
	}

	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		$links_model = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );
	}

	public function test_copy_taxonomies() {
		$tag_en = self::factory()->term->create( array( 'taxonomy' => 'post_tag', 'slug' => 'tag_en' ) );
		self::$model->term->set_language( $tag_en, 'en' );

		$tag_fr = self::factory()->term->create( array( 'taxonomy' => 'post_tag', 'slug' => 'tag_fr' ) );
		self::$model->term->set_language( $tag_fr, 'fr' );

		self::$model->term->save_translations( $tag_en, array( 'en' => $tag_en, 'fr' => $tag_fr ) );

		$untranslated = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $untranslated, 'en' );

		$en = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'fr' ) );

		$from = self::factory()->post->create();
		self::$model->post->set_language( $from, 'en' );
		wp_set_post_terms( $from, array( 'tag_en' ), 'post_tag' ); // Assigned by slug
		wp_set_post_terms( $from, array( $untranslated, $en ), 'category' ); // Assigned by term_id
		set_post_format( $from, 'aside' );

		$to = self::factory()->post->create();
		self::$model->post->set_language( $to, 'fr' );

		self::$model->post->save_translations( $from, array( 'fr' => $to ) );

		// copy
		$sync = new PLL_Admin_Sync( $this->pll_admin );
		$sync->taxonomies->copy( $from, $to, 'fr' ); // copy

		$this->assertEquals( array( $tag_fr ), wp_get_post_terms( $to, 'post_tag', array( 'fields' => 'ids' ) ) );
		$this->assertEquals( array( $fr ), wp_get_post_terms( $to, 'category', array( 'fields' => 'ids' ) ) );
		$this->assertEquals( 'aside', get_post_format( $to ) );

		// sync
		self::$model->options['sync'] = array( 'taxonomies' );
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
		self::$model->options['sync'] = array( 'post_format' );
		set_post_format( $to, '' );
		$this->assertFalse( get_post_format( $from ) );
	}

	public function test_copy_custom_fields() {
		$from = self::factory()->post->create();
		self::$model->post->set_language( $from, 'en' );
		add_post_meta( $from, 'key', 'value' );

		$to = self::factory()->post->create();
		self::$model->post->set_language( $to, 'fr' );

		self::$model->post->save_translations( $from, array( 'fr' => $to ) );

		// copy
		$sync = new PLL_Admin_Sync( $this->pll_admin );
		$sync->post_metas->copy( $from, $to, 'fr' ); // copy
		$this->assertEquals( 'value', get_post_meta( $to, 'key', true ) );

		// sync
		self::$model->options['sync'] = array( 'post_meta' );
		$this->assertTrue( update_post_meta( $to, 'key', 'new_value' ) );
		$this->assertEquals( 'new_value', get_post_meta( $from, 'key', true ) );

		// remove custom field and sync
		$this->assertTrue( delete_post_meta( $to, 'key' ) );
		$this->assertEmpty( get_post_meta( $from, 'key', true ) );
	}

	public function test_sync_multiple_custom_fields() {
		self::$model->options['sync'] = array( 'post_meta' );
		$sync = new PLL_Admin_Sync( $this->pll_admin );

		$from = self::factory()->post->create();
		self::$model->post->set_language( $from, 'en' );

		$to = self::factory()->post->create();
		self::$model->post->set_language( $to, 'fr' );

		// Add
		add_post_meta( $from, 'key', 'value1' );
		add_post_meta( $from, 'key', 'value2' );
		add_post_meta( $from, 'key', 'value3' );

		$sync->post_metas->copy( $from, $to, 'fr', true );
		$this->assertEqualSets( array( 'value1', 'value2', 'value3' ), get_post_meta( $to, 'key' ) );

		self::$model->post->save_translations( $from, array( 'fr' => $to ) );

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

	public function test_create_post_translation() {
		// categories
		$en = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'fr' ) );

		// source post
		$from = self::factory()->post->create( array( 'post_category' => array( $en ) ) );
		self::$model->post->set_language( $from, 'en' );
		add_post_meta( $from, 'key', 'value' );
		add_post_meta( $from, '_thumbnail_id', 1234 );
		set_post_format( $from, 'aside' );
		stick_post( $from );

		$this->pll_admin->filters_post = new PLL_Admin_Filters_Post( $this->pll_admin );
		$this->pll_admin->posts = new PLL_CRUD_Posts( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );

		$_REQUEST = $_GET = array(
			'from_post' => $from,
			'new_lang'  => 'fr',
			'_wpnonce'  => wp_create_nonce( 'new-post-translation' ),
		);

		$to = self::factory()->post->create();

		$GLOBALS['pagenow'] = 'post-new.php';
		$GLOBALS['post'] = get_post( $to );

		apply_filters( 'use_block_editor_for_post', false, $GLOBALS['post'] ); // fires the copy

		$this->assertEquals( 'fr', self::$model->post->get_language( $to )->slug );
		$this->assertEquals( array( get_category( $fr ) ), get_the_category( $to ) );
		$this->assertEquals( 'value', get_post_meta( $to, 'key', true ) );
		$this->assertEquals( 1234, get_post_thumbnail_id( $to ) );
		$this->assertEquals( 'aside', get_post_format( $to ) );
		$this->assertTrue( is_sticky( $to ) );
	}

	public function test_create_page_translation() {
		// parent pages
		$en = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'fr' ) );

		// source page
		$from = self::factory()->post->create( array( 'post_type' => 'page', 'menu_order' => 12, 'post_parent' => $en ) );
		self::$model->post->set_language( $from, 'en' );
		add_post_meta( $from, '_wp_page_template', 'full-width.php' );

		$this->pll_admin->posts = new PLL_CRUD_Posts( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );

		$_REQUEST = $_GET = array(
			'from_post' => $from,
			'new_lang'  => 'fr',
			'post_type' => 'page',
			'_wpnonce'  => wp_create_nonce( 'new-post-translation' ),
		);

		$to = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$GLOBALS['pagenow'] = 'post-new.php';
		$GLOBALS['post'] = get_post( $to );

		apply_filters( 'use_block_editor_for_post', false, $GLOBALS['post'] ); // fires the copy

		$this->assertEquals( 'fr', self::$model->post->get_language( $to )->slug );
		$this->assertEquals( $fr, wp_get_post_parent_id( $to ) );
		$this->assertEquals( 12, $GLOBALS['post']->menu_order );
		$this->assertEquals( 'full-width.php', get_page_template_slug( $to ) );
	}

	public function test_save_post_with_sync() {
		self::$model->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// Attachment for thumbnail
		$filename = __DIR__ . '/../data/image.jpg';
		$thumbnail_id = self::factory()->attachment->create_upload_object( $filename );

		// categories
		$en = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'fr' ) );

		// posts
		$to = self::factory()->post->create();
		self::$model->post->set_language( $to, 'fr' );

		$from = self::factory()->post->create( array( 'post_category' => array( $en ), 'post_date' => '2007-09-04 00:00:00' ) );
		self::$model->post->set_language( $from, 'en' );

		self::$model->post->save_translations( $from, array( 'fr' => $to ) );

		$key = add_post_meta( $from, 'key', 'value' );
		$metas = array(
			$key => array( 'key' => 'key', 'value' => 'value' ),
		);

		$this->pll_admin->posts = new PLL_CRUD_Posts( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );
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

		$this->assertEquals( 'fr', self::$model->post->get_language( $to )->slug );
		$this->assertEqualSetsWithIndex( array( 'en' => $from, 'fr' => $to ), self::$model->post->get_translations( $from ) );
		$this->assertEquals( array( get_category( $fr ) ), get_the_category( $to ) );
		$this->assertEquals( '2007-09-04', get_the_date( 'Y-m-d', $to ) );
		$this->assertEquals( array( 'value' ), get_post_meta( $to, 'key' ) );
		$this->assertEquals( array( 'value' ), get_post_meta( $from, 'key' ) ); // Test reverse sync
		$this->assertEquals( $thumbnail_id, get_post_thumbnail_id( $to ) );
		$this->assertEquals( 'aside', get_post_format( $to ) );
		$this->assertTrue( is_sticky( $to ) );
	}

	public function filter_theme_page_templates() {
		return array( 'templates/test.php' => 'Test Template Page' );
	}

	public function test_save_page_with_sync() {
		$GLOBALS['post_type'] = 'page';
		add_filter( 'theme_page_templates', array( $this, 'filter_theme_page_templates' ) ); // Allow to test templates with themes without templates

		self::$model->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// parent pages
		$en = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'fr' ) );

		// pages page
		$to = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $to, 'fr' );

		$from = self::factory()->post->create( array( 'post_type' => 'page', 'menu_order' => 12, 'post_parent' => $en ) );
		self::$model->post->set_language( $from, 'en' );

		self::$model->post->save_translations( $from, array( 'fr' => $to ) );

		$this->pll_admin->posts = new PLL_CRUD_Posts( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		edit_post(
			array(
				'post_ID'       => $from,
				'page_template' => 'templates/test.php',
			)
		); // fires the sync

		$page = get_post( $to );

		$this->assertEquals( 'fr', self::$model->post->get_language( $to )->slug );
		$this->assertEqualSetsWithIndex( array( 'en' => $from, 'fr' => $to ), self::$model->post->get_translations( $from ) );
		$this->assertEquals( $fr, wp_get_post_parent_id( $to ) );
		$this->assertEquals( 12, $page->menu_order );
		$this->assertEquals( 'templates/test.php', get_page_template_slug( $to ) );
	}

	public function test_save_term_with_sync_in_post() {
		self::$model->options['sync'] = array( 'taxonomies' );

		$from = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $from, 'en' );

		// posts
		$en = self::factory()->post->create( array( 'post_category' => array( $from ) ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->pll_admin->filters_term = new PLL_Admin_Filters_Term( $this->pll_admin );
		$this->pll_admin->terms = new PLL_CRUD_Terms( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		$_REQUEST = $_POST = array(
			'action'           => 'add-tag',
			'term_lang_choice' => 'fr',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
			'term_tr_lang'     => array( 'en' => $from ),
		);

		$this->pll_admin->curlang = self::$model->get_language( 'fr' );

		$to = self::factory()->term->create( array( 'taxonomy' => 'category' ) );

		$this->assertEquals( 'fr', self::$model->term->get_language( $to )->slug );
		$this->assertEqualSetsWithIndex( array( 'en' => $from, 'fr' => $to ), self::$model->term->get_translations( $from ) );
		$this->assertTrue( is_object_in_term( $fr, 'category', $to ) );
	}

	public function test_save_term_with_parent_sync() {
		// Parents
		$en = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'fr' ) );

		// child
		$from = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $from, 'en' );

		$this->pll_admin->filters_term = new PLL_Admin_Filters_Term( $this->pll_admin );
		$this->pll_admin->terms = new PLL_CRUD_Terms( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		$_REQUEST = $_POST = array(
			'action'           => 'add-tag',
			'post_lang_choice' => 'fr',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
			'term_tr_lang'     => array( 'en' => $from ),
			'parent'           => $fr,
		);

		$to = self::factory()->term->create( array( 'taxonomy' => 'category', 'parent' => $fr ) );
		$this->assertEquals( 'fr', self::$model->term->get_language( $to )->slug );
		$this->assertEqualSetsWithIndex( array( 'en' => $from, 'fr' => $to ), self::$model->term->get_translations( $from ) );
		$this->assertEquals( $fr, get_category( $to )->parent );
		$this->assertEquals( $en, get_category( $from )->parent );
	}

	/**
	 * Test the child sync if we edit (delete) the translated term parent
	 * Bug fixed in 2.6.4
	 */
	public function test_child_sync_if_delete_translated_term_parent() {
		// Children.
		$child_en = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $child_en, 'en' );

		$child_fr = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $child_fr, 'fr' );

		self::$model->term->save_translations( $child_en, array( 'fr' => $child_fr ) );

		// Parents.
		$parent_en = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $parent_en, 'en' );

		wp_update_term( $child_en, 'category', array( 'parent' => $parent_en ) );

		$parent_fr = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $parent_fr, 'fr' );

		self::$model->term->save_translations( $parent_en, array( 'fr' => $parent_fr ) );

		$this->pll_admin->terms = new PLL_CRUD_Terms( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );
		wp_update_term( $child_fr, 'category', array( 'parent' => $parent_fr ) );

		wp_update_term( $child_fr, 'category', array( 'parent' => 0 ) );

		$this->assertEquals( get_term( $child_en )->parent, 0 );
	}

	public function test_assign_parents_when_parents_are_not_translated() {
		// Children.
		$child_en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $child_en, 'en' );

		$child_fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $child_fr, 'fr' );

		self::$model->term->save_translations( $child_en, array( 'fr' => $child_fr ) );

		// Parents.
		$parent_en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $parent_en, 'en' );

		$parent_fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $parent_fr, 'fr' );

		$this->pll_admin->terms = new PLL_CRUD_Terms( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );

		wp_update_term( $child_en, 'category', array( 'parent' => $parent_en ) );
		wp_update_term( $child_fr, 'category', array( 'parent' => $parent_fr ) );

		$this->assertEquals( get_term( $child_en )->parent, $parent_en );
		$this->assertEquals( get_term( $child_fr )->parent, $parent_fr );
	}

	public function test_create_post_translation_with_sync_post_date() {
		// source post
		$from = self::factory()->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$model->post->set_language( $from, 'en' );

		$this->pll_admin->posts = new PLL_CRUD_Posts( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );
		self::$model->options['sync'] = array( 'post_date' ); // Sync publish date

		$GLOBALS['pagenow'] = 'post-new.php';
		$_REQUEST = $_GET = array(
			'from_post' => $from,
			'new_lang'  => 'fr',
			'_wpnonce'  => wp_create_nonce( 'new-post-translation' ),
		);

		$to = self::factory()->post->create();
		clean_post_cache( $to ); // Necessary before calling get_post() below otherwise we don't get the synchronized date

		$this->assertEquals( get_post( $from )->post_date, get_post( $to )->post_date );
		$this->assertEquals( get_post( $from )->post_date_gmt, get_post( $to )->post_date_gmt );
	}

	/**
	 * Bug introduced in 2.0.8 and fixed in 2.1.
	 */
	public function test_quick_edit_with_sync_page_parent() {
		$_REQUEST['post_type'] = 'page';

		self::$model->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// parent pages
		$en = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'fr' ) );

		// pages page
		$to = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $to, 'fr' );

		$from = self::factory()->post->create( array( 'post_type' => 'page', 'post_parent' => $en ) );
		self::$model->post->set_language( $from, 'en' );

		self::$model->post->save_translations( $from, array( 'fr' => $to ) );

		$this->pll_admin->posts = new PLL_CRUD_Posts( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		wp_update_post( array( 'ID' => $from ) ); // fires the sync

		$this->assertEquals( $fr, wp_get_post_parent_id( $to ) );
	}

	public function test_create_post_translation_with_sync_date() {
		self::$model->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// source post
		$from = self::factory()->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$model->post->set_language( $from, 'en' );

		$this->pll_admin->posts = new PLL_CRUD_Posts( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );

		$_REQUEST = $_GET = array(
			'from_post' => $from,
			'new_lang'  => 'fr',
			'_wpnonce'  => wp_create_nonce( 'new-post-translation' ),
		);

		$to = self::factory()->post->create();

		$GLOBALS['pagenow'] = 'post-new.php';
		$GLOBALS['post'] = get_post( $to );

		do_action( 'add_meta_boxes', 'post', $GLOBALS['post'] ); // fires the copy
		clean_post_cache( $to ); // Usually WordPress will do it for us when the post will be saved

		$this->assertEquals( 'fr', self::$model->post->get_language( $to )->slug );
		$this->assertEquals( '2007-09-04 00:00:00', get_post( $to )->post_date );
	}

	public function _add_term_meta_to_copy() {
		return array( 'key' );
	}

	public function test_copy_term_metas() {
		$from = self::factory()->term->create();
		self::$model->term->set_language( $from, 'en' );
		add_term_meta( $from, 'key', 'value' );

		$to = self::factory()->term->create();
		self::$model->term->set_language( $to, 'fr' );
		self::$model->term->save_translations( $from, array( 'fr' => $to ) );

		add_filter( 'pll_copy_term_metas', array( $this, '_add_term_meta_to_copy' ) );

		// copy
		$sync = new PLL_Admin_Sync( $this->pll_admin );
		$sync->term_metas->copy( $from, $to, 'fr' ); // copy
		$this->assertEquals( 'value', get_term_meta( $to, 'key', true ) );

		// sync
		$this->assertTrue( update_term_meta( $to, 'key', 'new_value' ) );
		$this->assertEquals( 'new_value', get_term_meta( $from, 'key', true ) );

		// remove custom field and sync
		$this->assertTrue( delete_term_meta( $to, 'key' ) );
		$this->assertEmpty( get_term_meta( $from, 'key', true ) );
	}

	public function test_sync_multiple_term_metas() {
		new PLL_Admin_Sync( $this->pll_admin );

		$from = self::factory()->term->create();
		self::$model->term->set_language( $from, 'en' );

		$to = self::factory()->term->create();
		self::$model->term->set_language( $to, 'fr' );

		self::$model->term->save_translations( $from, array( 'fr' => $to ) );

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

	public function test_sync_post_with_metas_to_remove() {
		self::$model->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// Posts
		$to = self::factory()->post->create();
		self::$model->post->set_language( $to, 'fr' );

		$from = self::factory()->post->create();
		self::$model->post->set_language( $from, 'en' );

		self::$model->post->save_translations( $from, array( 'fr' => $to ) );

		add_post_meta( $to, 'key1', 'value' );
		add_post_meta( $to, 'key2', 'value1' );
		add_post_meta( $to, 'key2', 'value2' );
		$key = add_post_meta( $from, 'key2', 'value1' );
		$metas = array(
			$key => array( 'key' => 'key2', 'value' => 'value1' ),
		);

		$this->pll_admin->posts = new PLL_CRUD_Posts( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );
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

	public function test_source_post_was_sticky_before_sync_was_active() {
		self::$model->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// Posts
		$to = self::factory()->post->create();
		self::$model->post->set_language( $to, 'fr' );

		$from = self::factory()->post->create();
		self::$model->post->set_language( $from, 'en' );

		self::$model->post->save_translations( $from, array( 'fr' => $to ) );

		stick_post( $from );

		$this->pll_admin->posts = new PLL_CRUD_Posts( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );
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

	public function test_target_post_was_sticky_before_sync_was_active() {
		self::$model->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// Posts
		$to = self::factory()->post->create();
		self::$model->post->set_language( $to, 'fr' );

		$from = self::factory()->post->create();
		self::$model->post->set_language( $from, 'en' );

		self::$model->post->save_translations( $from, array( 'fr' => $to ) );

		stick_post( $to );

		$this->pll_admin->posts = new PLL_CRUD_Posts( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );
		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		edit_post( array( 'post_ID' => $from ) ); // Fires the sync

		$this->assertfalse( is_sticky( $to ) );
	}

	/**
	 * Bug fixed in 2.3.2.
	 */
	public function test_delete_term() {
		self::$model->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// Categories
		$en = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'fr' ) );

		// Posts
		$post_fr = self::factory()->post->create( array( 'post_category' => array( $fr ) ) );
		self::$model->post->set_language( $post_fr, 'fr' );

		$post_en = self::factory()->post->create( array( 'post_category' => array( $en ) ) );
		self::$model->post->set_language( $post_en, 'en' );

		self::$model->post->save_translations( $post_en, array( 'fr' => $post_fr ) );

		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );

		wp_delete_category( $fr );

		$this->assertEquals( array( $en ), wp_get_post_categories( $post_en ) );
	}

	/**
	 * Bug fixed in 2.3.11.
	 */
	public function test_category_hierarchy() {
		// Categories
		$child_en = $en = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $en, 'en' );

		$child_fr = $fr = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'fr' ) );

		$parent_en = $en = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $en, 'en' );

		wp_update_term( $child_en, 'category', array( 'parent' => $parent_en ) );

		$parent_fr = $fr = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'fr' ) );

		$this->pll_admin->terms = new PLL_CRUD_Terms( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );
		wp_update_term( $child_fr, 'category', array( 'parent' => $parent_fr ) );

		$term = get_term( $child_fr );
		$this->assertEquals( $parent_fr, $term->parent );

		// The bug fixed
		$term = get_term( $child_en );
		$this->assertEquals( $parent_en, $term->parent );
	}

	/**
	 * Bug fixed in 2.5.2.
	 */
	public function test_sync_category_parent_modification() {
		// Parent 1
		$p1en = $en = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $en, 'en' );

		$p1fr = $fr = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'fr' ) );

		// Parent 2
		$p2en = $en = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $en, 'en' );

		$p2fr = $fr = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'fr' ) );

		// Child
		$child_en = $en = self::factory()->term->create( array( 'taxonomy' => 'category', 'parent' => $p1en ) );
		self::$model->term->set_language( $en, 'en' );

		$child_fr = $fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'parent' => $p1fr ) );
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'fr' ) );

		$this->pll_admin->terms = new PLL_CRUD_Terms( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );
		wp_update_term( $child_fr, 'category', array( 'parent' => $p2fr ) );

		$term = get_term( $child_fr );
		$this->assertEquals( $p2fr, $term->parent );

		// The bug fixed
		$term = get_term( $child_en );
		$this->assertEquals( $p2en, $term->parent );
	}

	public function test_if_cannot_synchronize() {
		add_filter( 'pll_pre_current_user_can_synchronize_post', '__return_null' ); // Enable capability check
		self::$model->options['sync'] = array_keys( PLL_Settings_Sync::list_metas_to_sync() ); // sync everything

		// Post format
		self::factory()->term->create( array( 'taxonomy' => 'post_format', 'name' => 'post-format-aside' ) ); // shouldn't WP do that ?

		// Attachment for thumbnail
		$filename = __DIR__ . '/../data/image.jpg';
		$thumbnail_id = self::factory()->attachment->create_upload_object( $filename );

		// Categories
		$en = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'fr' ) );

		$this->pll_admin->posts = new PLL_CRUD_Posts( $this->pll_admin );
		$this->pll_admin->sync = new PLL_Admin_Sync( $this->pll_admin );

		// Posts
		wp_set_current_user( self::$editor );
		$to = self::factory()->post->create();
		self::$model->post->set_language( $to, 'fr' );

		wp_set_current_user( self::$author );
		$from = self::factory()->post->create();
		self::$model->post->set_language( $from, 'en' );
		self::$model->post->save_translations( $from, array( 'fr' => $to ) );

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

	public function test_slashes() {
		self::$model->options['sync'] = array( 'post_meta' );
		$sync = new PLL_Admin_Sync( $this->pll_admin );

		$key = '\_key';
		$slash_key = wp_slash( $key );

		$slash_2 = '\\\\';
		$slash_4 = '\\\\\\\\';

		// Create posts.
		$to = self::factory()->post->create();
		self::$model->post->set_language( $to, 'fr' );

		$from = self::factory()->post->create();
		self::$model->post->set_language( $from, 'en' );

		// Test copy().
		add_post_meta( $from, $slash_key, $slash_2 );
		$sync->post_metas->copy( $from, $to, 'fr' );
		$this->assertEquals( wp_unslash( $slash_2 ), get_post_meta( $to, $key, true ) );

		update_post_meta( $from, $slash_key, $slash_4 );
		$sync->post_metas->copy( $from, $to, 'fr' );
		$this->assertEquals( wp_unslash( $slash_4 ), get_post_meta( $to, $key, true ) );

		delete_post_meta( $from, $slash_key );
		delete_post_meta( $to, $slash_key );

		self::$model->post->save_translations( $from, array( 'fr' => $to ) );

		// Test add, update, delete.
		add_post_meta( $from, $slash_key, $slash_2 );
		$this->assertEquals( wp_unslash( $slash_2 ), get_post_meta( $to, $key, true ) );

		update_post_meta( $from, $slash_key, $slash_4 );
		$this->assertEquals( wp_unslash( $slash_4 ), get_post_meta( $to, $key, true ) );

		delete_post_meta( $from, $slash_key, $slash_4 );
		$this->assertEmpty( get_post_meta( $to, $key, true ) );
	}
}
