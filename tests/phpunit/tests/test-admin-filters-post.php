<?php

class Admin_Filters_Post_Test extends PLL_UnitTestCase {
	protected static $editor;

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
		self::create_language( 'de_DE_formal' );
		self::create_language( 'es_ES' );

		self::require_api();
		
		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
	}

	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$editor ); // Set a user to pass current_user_can tests

		$links_model = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );

		$this->pll_admin->links          = new PLL_Admin_Links( $this->pll_admin );
		$this->pll_admin->filters_post   = new PLL_Admin_Filters_Post( $this->pll_admin );
		$this->pll_admin->classic_editor = new PLL_Admin_Classic_Editor( $this->pll_admin );
		$this->pll_admin->posts          = new PLL_CRUD_Posts( $this->pll_admin );
		$GLOBALS['polylang']             = $this->pll_admin;
	}

	public function test_default_language() {
		// User preferred language
		$this->pll_admin->pref_lang = self::$model->get_language( 'fr' );
		$post_id = self::factory()->post->create();
		$this->assertEquals( 'fr', self::$model->post->get_language( $post_id )->slug );

		// Language set from parent
		$parent = self::factory()->post->create();
		self::$model->post->set_language( $parent, 'de' );
		$post_id = self::factory()->post->create( array( 'post_parent' => $parent ) );
		$this->assertEquals( 'de', self::$model->post->get_language( $post_id )->slug );

		// Language set when adding a new translation
		$_GET['new_lang'] = 'es';
		$post_id = self::factory()->post->create();
		$this->assertEquals( 'es', self::$model->post->get_language( $post_id )->slug );
	}

	public function test_save_post_from_metabox() {
		$GLOBALS['post_type'] = 'post';

		$_POST = array(
			'post_lang_choice' => 'en',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
			'post_ID'          => $en = self::factory()->post->create(),
		);
		$_REQUEST = $_POST;
		do_action( 'load-post.php' );
		edit_post();

		$this->assertEquals( 'en', self::$model->post->get_language( $en )->slug );

		// Set the language and translations.
		$_POST = array(
			'post_lang_choice' => 'fr',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
			'post_tr_lang'     => array( 'en' => $en ),
			'post_ID'          => $fr = self::factory()->post->create(),
		);
		$_REQUEST = $_POST;
		do_action( 'load-post.php' );
		edit_post();

		$this->assertEquals( 'fr', self::$model->post->get_language( $fr )->slug );
		$this->assertEqualSets( compact( 'en', 'fr' ), self::$model->post->get_translations( $en ) );
	}

	public function test_save_post_from_bulk_edit() {
		$posts = self::factory()->post->create_many( 2 );
		self::$model->post->set_language( $posts[0], 'en' );
		self::$model->post->set_language( $posts[1], 'fr' );

		// First do not modify any language.
		$_GET = array(
			'inline_lang_choice' => -1,
			'_wpnonce'           => wp_create_nonce( 'bulk-posts' ),
			'bulk_edit'          => 'Update',
			'post'               => $posts,
			'_status'            => 'publish',
		);
		$_REQUEST = $_GET;

		do_action( 'load-edit.php' );
		bulk_edit_posts( $_REQUEST );
		$this->assertEquals( 'en', self::$model->post->get_language( $posts[0] )->slug );
		$this->assertEquals( 'fr', self::$model->post->get_language( $posts[1] )->slug );

		// Second modify all languages.
		$_GET['inline_lang_choice']     = 'fr';
		$_REQUEST['inline_lang_choice'] = 'fr';
		do_action( 'load-edit.php' );
		bulk_edit_posts( $_REQUEST );
		$this->assertEquals( 'fr', self::$model->post->get_language( $posts[0] )->slug );
		$this->assertEquals( 'fr', self::$model->post->get_language( $posts[1] )->slug );
	}

	public function test_quickdraft() {
		$_REQUEST = array(
			'action'   => 'post-quickdraft-save',
			'_wpnonce' => wp_create_nonce( 'add-post' ),
		);

		$this->pll_admin->pref_lang = self::$model->get_language( 'fr' );
		$post_id = self::factory()->post->create();
		$this->assertEquals( 'fr', self::$model->post->get_language( $post_id )->slug );
	}

	public function test_save_post_with_categories() {
		$en = self::factory()->category->create();
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->category->create();
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$en2 = self::factory()->category->create();
		self::$model->term->set_language( $en2, 'en' );

		$fr2 = self::factory()->category->create();
		self::$model->term->set_language( $fr2, 'fr' );

		$_POST = array(
			'post_lang_choice' => 'fr',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
			'post_category'    => array( $en, $en2, $fr2 ),
			'post_ID'          => $post_id = self::factory()->post->create(),
		);
		$_REQUEST = $_POST;
		do_action( 'load-post.php' );
		edit_post();

		$this->assertFalse( is_object_in_term( $post_id, 'category', $en ) );
		$this->assertTrue( is_object_in_term( $post_id, 'category', $fr ) );
		$this->assertFalse( is_object_in_term( $post_id, 'category', $en2 ) );
		$this->assertTrue( is_object_in_term( $post_id, 'category', $fr2 ) );
	}

	public function test_save_post_with_tags() {
		$this->pll_admin->filters_term = new PLL_Admin_Filters_Term( $this->pll_admin );

		$en = self::factory()->tag->create( array( 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->tag->create( array( 'name' => 'test', 'slug' => 'test-fr' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$_POST = array(
			'post_lang_choice' => 'fr',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
			'tax_input'        => array( 'post_tag' => array( 'test', 'new' ) ),
			'post_ID'          => $post_id = self::factory()->post->create(),
		);
		$_REQUEST = $_POST;
		do_action( 'load-post.php' );
		edit_post();

		$this->assertFalse( is_object_in_term( $post_id, 'post_tag', $en ) );
		$this->assertTrue( is_object_in_term( $post_id, 'post_tag', $fr ) );

		$new = get_term_by( 'name', 'new', 'post_tag' );
		$this->assertTrue( is_object_in_term( $post_id, 'post_tag', $new ) );
		$this->assertEquals( 'fr', self::$model->term->get_language( $new->term_id )->slug );
	}

	public function test_delete_post() {
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$de = self::factory()->post->create();
		self::$model->post->set_language( $de, 'de' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr', 'de' ) );

		wp_delete_post( $en, true ); // Forces delete
		$this->assertEqualSetsWithIndex( compact( 'fr', 'de' ), self::$model->post->get_translations( $fr ) );

		$this->assertEmpty( self::$model->post->get_object_term( $en, 'post_translations' ) ); // Relationship deleted
		$group = self::$model->post->get_object_term( $fr, 'post_translations' );
		$this->assertEquals( 2, $group->count ); // Count updated
	}

	public function test_page_attributes_meta_box() {
		$en = self::factory()->post->create( array( 'post_title' => 'test', 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_title' => 'essai', 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		$page = self::factory()->post->create_and_get( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $page->ID, 'fr' );

		$this->pll_admin->filters = new PLL_Admin_Filters( $this->pll_admin ); // We need the get_pages filter
		$GLOBALS['hook_suffix'] = 'post.php';
		set_current_screen( 'page' );
		require_once ABSPATH . 'wp-admin/includes/meta-boxes.php';

		ob_start();
		page_attributes_meta_box( $page );
		$out = ob_get_clean();

		$this->assertFalse( strpos( $out, 'test' ) );
		$this->assertNotFalse( strpos( $out, 'essai' ) );

		$_POST['lang'] = 'en'; // Prevails on the post language (ajax response to language change)
		ob_start();
		page_attributes_meta_box( $page );
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'test' ) );
		$this->assertFalse( strpos( $out, 'essai' ) );
	}

	public function test_languages_meta_box_for_new_post() {
		global $post_ID;

		$lang = self::$model->get_language( 'en' );
		$this->pll_admin->pref_lang = $lang;
		$this->pll_admin->links = new PLL_Admin_Links( $this->pll_admin );
		$post_ID = self::factory()->post->create();
		wp_set_object_terms( $post_ID, array(), 'language' ); // Intentionally remove the language

		ob_start();
		$this->pll_admin->classic_editor->post_language();
		$form = ob_get_clean();
		$doc = new DOMDocument();
		$doc->loadHTML( $form );
		$xpath = new DOMXpath( $doc );

		$option = $xpath->query( '//div/select/option[.="' . $lang->name . '"]' );
		$this->assertEquals( 'selected', $option->item( 0 )->getAttribute( 'selected' ) );
	}

	public function test_languages_meta_box_for_new_translation() {
		global $post_ID;

		$this->pll_admin->links = new PLL_Admin_Links( $this->pll_admin );
		$post_ID = self::factory()->post->create();
		wp_set_object_terms( $post_ID, array(), 'language' ); // Intentionally remove the language

		$en = self::factory()->post->create( array( 'post_title' => 'test' ) );
		self::$model->post->set_language( $en, 'en' );
		$lang = self::$model->get_language( 'fr' );

		$_GET = array(
			'post_type' => 'post',
			'from_post' => $en,
			'new_lang'  => 'fr',
			'_wpnonce'  => wp_create_nonce( 'new-post-translation' ),
		);

		$_REQUEST = $_GET;

		$GLOBALS['pagenow']   = 'post-new.php';
		$GLOBALS['post_type'] = 'post';
		$GLOBALS['post']      = get_post( $en );

		ob_start();
		$this->pll_admin->classic_editor->post_language();
		$form = ob_get_clean();
		$doc = new DomDocument();
		$doc->loadHTML( '<?xml encoding="UTF-8">' . $form );
		$xpath = new DOMXpath( $doc );

		$option = $xpath->query( '//div/select/option[.="' . $lang->name . '"]' );
		$this->assertEquals( 'selected', $option->item( 0 )->getAttribute( 'selected' ) );

		$input = $xpath->query( '//input[@name="post_tr_lang[en]"]' );
		$this->assertEquals( $en, $input->item( 0 )->getAttribute( 'value' ) );

		$input = $xpath->query( '//input[@id="tr_lang_en"]' );
		$this->assertEquals( 'test', $input->item( 0 )->getAttribute( 'value' ) );
	}

	public function test_languages_meta_box_for_existing_post_with_translations() {
		$this->pll_admin->links = new PLL_Admin_Links( $this->pll_admin );

		$en = self::factory()->post->create( array( 'post_title' => 'test' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_title' => 'essai' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$GLOBALS['post_ID'] = $fr;

		$lang = self::$model->get_language( 'fr' );

		ob_start();
		$this->pll_admin->classic_editor->post_language();
		$form = ob_get_clean();
		$doc = new DomDocument();
		$doc->loadHTML( '<?xml encoding="UTF-8">' . $form );
		$xpath = new DOMXpath( $doc );

		// Language is French.
		$option = $xpath->query( '//div/select/option[.="' . $lang->name . '"]' );
		$this->assertEquals( 'selected', $option->item( 0 )->getAttribute( 'selected' ) );

		// Link to the English post.
		$input = $xpath->query( '//input[@name="post_tr_lang[en]"]' );
		$this->assertEquals( $en, $input->item( 0 )->getAttribute( 'value' ) );

		$input = $xpath->query( '//input[@id="tr_lang_en"]' );
		$this->assertEquals( 'test', $input->item( 0 )->getAttribute( 'value' ) );

		// No self link.
		$this->assertEmpty( $xpath->query( '//input[@name="post_tr_lang[fr]"]' )->length );
		$this->assertEmpty( $xpath->query( '//input[@id="tr_lang_fr"]' )->length );

		// Link to empty German post.
		$input = $xpath->query( '//input[@name="post_tr_lang[de]"]' );
		$this->assertEquals( 0, (int) $input->item( 0 )->getAttribute( 'value' ) );

		$input = $xpath->query( '//input[@id="tr_lang_de"]' );
		$this->assertEquals( '', $input->item( 0 )->getAttribute( 'value' ) );
	}

	public function test_languages_meta_box_for_media() {
		global $post_ID;

		$this->pll_admin->options['media_support'] = 1;

		$en = self::factory()->attachment->create_object( 'image0.jpg' );
		self::$model->post->set_language( $en, 'en' );

		$post_ID = $this->pll_admin->model->post->create_media_translation( $en, 'fr' );

		$lang = self::$model->get_language( 'fr' );

		ob_start();
		$this->pll_admin->classic_editor->post_language();
		$form = ob_get_clean();
		$doc = new DomDocument();
		$doc->loadHTML( '<?xml encoding="UTF-8">' . $form );
		$xpath = new DOMXpath( $doc );

		// Language is French
		$option = $xpath->query( '//div/select/option[.="' . $lang->name . '"]' );
		$this->assertEquals( 'selected', $option->item( 0 )->getAttribute( 'selected' ) );

		// Link to English post
		$input = $xpath->query( '//input[@name="media_tr_lang[en]"]' );
		$this->assertEquals( $en, $input->item( 0 )->getAttribute( 'value' ) );
		$this->assertNotFalse( strpos( $form, 'Edit the translation in English' ) );

		// No self link
		$this->assertEmpty( $xpath->query( '//input[@name="media_tr_lang[fr]"]' )->length );

		// Link to empty German post
		$this->assertNotFalse( strpos( $form, 'Add a translation in Deutsch' ) );
	}

	public function test_get_posts_language_filter() {
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$de = self::factory()->post->create();
		self::$model->post->set_language( $de, 'de' );

		$posts = get_posts( array( 'fields' => 'ids', 'lang' => 'fr' ) );
		$this->assertEquals( $fr, reset( $posts ) );

		$posts = get_posts( array( 'fields' => 'ids', 'lang' => 'en,de' ) );
		$this->assertEqualSets( array( $en, $de ), $posts );

		$posts = get_posts( array( 'fields' => 'ids', 'lang' => array( 'de', 'fr' ) ) );
		$this->assertEqualSets( array( $fr, $de ), $posts );

		$posts = get_posts( array( 'fields' => 'ids', 'lang' => '' ) );
		$this->assertEqualSets( array( $en, $fr, $de ), $posts );

		$posts = get_posts( array( 'fields' => 'ids', 'lang' => 'all' ) );
		$this->assertEqualSets( array( $en, $fr, $de ), $posts );
	}

	public function test_get_posts_with_query_var() {
		register_taxonomy( 'trtax', 'post' ); // Translated custom tax

		$this->pll_admin->options['taxonomies'] = array( 'trtax' );

		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$tag = self::factory()->tag->create();
		self::$model->term->set_language( $tag, 'fr' );
		wp_set_post_terms( $fr, array( $tag ), 'post_tag' );

		$tax = self::factory()->term->create( array( 'taxonomy' => 'trtax' ) );
		self::$model->term->set_language( $tax, 'fr' );
		wp_set_post_terms( $fr, array( $tax ), 'trtax' );

		$this->pll_admin->curlang = self::$model->get_language( 'en' );

		$posts = get_posts( array( 'fields' => 'ids' ) );
		$this->assertEquals( $en, reset( $posts ) );

		$posts = get_posts( array( 'fields' => 'ids', 'post__in' => array( $fr ) ) );
		$this->assertEmpty( $posts );

		$posts = get_posts( array( 'fields' => 'ids', 'post__in' => array( $en, $fr ) ) );
		$this->assertEquals( array( $en ), $posts );

		$posts = get_posts( array( 'fields' => 'ids', 'tag__in' => array( $tag ) ) );
		$this->assertEmpty( $posts );

		$tax_query = array(
			array(
				'taxonomy' => 'post_tag',
				'field'    => 'term_id',
				'terms'    => $tag,
				'operator' => 'IN',
			),
		);

		$posts = get_posts( array( 'fields' => 'ids', 'tax_query' => $tax_query ) );
		$this->assertEquals( $fr, reset( $posts ) );

		// Custom tax
		$tax = get_term( $tax );
		$posts = get_posts( array( 'fields' => 'ids', 'trtax' => $tax->slug ) );
		$this->assertEquals( $fr, reset( $posts ) );
	}

	public function test_categories_script_data_in_footer() {
		$GLOBALS['hook_suffix'] = 'edit.php';
		set_current_screen( 'edit' );
		$GLOBALS['wp_scripts'] = new WP_Scripts();
		wp_default_scripts( $GLOBALS['wp_scripts'] );

		$term_id = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $term_id, 'fr' );

		do_action( 'admin_enqueue_scripts' );

		ob_start();
		do_action( 'admin_print_footer_scripts' );
		$footer = ob_get_clean();

		$this->assertEquals( 1, preg_match( '/var pll_term_languages = {"fr":{"category":\[(\d+),\d+\]}/', $footer, $matches ) );
		$this->assertEquals( $term_id, $matches[1] );
	}

	public function test_parent_pages_script_data_in_footer() {
		$en = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		$GLOBALS['hook_suffix'] = 'edit.php';
		$_REQUEST['post_type']  = 'page';
		set_current_screen();
		$GLOBALS['wp_scripts'] = new WP_Scripts();
		wp_default_scripts( $GLOBALS['wp_scripts'] );
		do_action( 'admin_enqueue_scripts' );

		ob_start();
		do_action( 'admin_print_footer_scripts' );
		$footer = ob_get_clean();

		$pages = array( 'en' => array( $en ), 'fr' => array( $fr ) );

		$this->assertNotFalse( strpos( $footer, 'var pll_page_languages = ' . wp_json_encode( $pages ) ) );
	}
}
