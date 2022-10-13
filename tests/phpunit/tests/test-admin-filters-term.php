<?php

class Admin_Filters_Term_Test extends PLL_UnitTestCase {
	protected static $editor;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE_formal' );
		self::create_language( 'es_ES' );

		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
	}

	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$editor ); // Set a user to pass current_user_can tests

		$links_model     = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );

		$this->pll_admin->filters      = new PLL_Admin_Filters( $this->pll_admin ); // To activate the fix_delete_default_category() filter
		$this->pll_admin->terms        = new PLL_CRUD_Terms( $this->pll_admin );
		$this->pll_admin->filters_term = new PLL_Admin_Filters_Term( $this->pll_admin );
		$this->pll_admin->posts        = new PLL_CRUD_Posts( $this->pll_admin );
		$this->pll_admin->filters_post = new PLL_Admin_Filters_Post( $this->pll_admin );
	}

	public function test_default_language() {
		// User preferred language
		$this->pll_admin->pref_lang = self::$model->get_language( 'fr' );
		$term_id = self::factory()->category->create();
		$this->assertEquals( 'fr', self::$model->term->get_language( $term_id )->slug );

		// Language set from parent
		$parent = self::factory()->category->create();
		self::$model->term->set_language( $parent, 'de' );
		$term_id = self::factory()->category->create( array( 'parent' => $parent ) );
		$this->assertEquals( 'de', self::$model->term->get_language( $term_id )->slug );
	}

	public function test_save_term_from_edit_tags() {
		$_REQUEST = $_POST = array(
			'action'           => 'add-tag',
			'term_lang_choice' => 'en',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
		);
		$en = self::factory()->category->create();
		$this->assertEquals( 'en', self::$model->term->get_language( $en )->slug );

		// Set the language and translations
		$_REQUEST = $_POST = array(
			'action'           => 'add-tag',
			'post_lang_choice' => 'fr',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
			'term_tr_lang'     => array( 'en' => $en ),
		);

		$fr = self::factory()->category->create();
		$this->assertEquals( 'fr', self::$model->term->get_language( $fr )->slug );
		$this->assertEqualSets( compact( 'en', 'fr' ), self::$model->term->get_translations( $en ) );
	}

	public function test_create_term_from_categories_metabox() {
		$_REQUEST = $_POST = array(
			'action'                   => 'add-category',
			'term_lang_choice'         => 'fr',
			'_ajax_nonce-add-category' => wp_create_nonce( 'add-category' ),
		);

		$fr = self::factory()->category->create();
		$this->assertEquals( 'fr', self::$model->term->get_language( $fr )->slug );
	}

	public function test_save_term_from_quick_edit() {
		$term_id = $en = self::factory()->category->create();
		self::$model->term->set_language( $en, 'en' );

		$de = self::factory()->category->create();
		self::$model->term->set_language( $de, 'de' );

		$es = self::factory()->category->create();
		self::$model->term->set_language( $es, 'es' );

		self::$model->term->save_translations( $en, compact( 'en', 'de', 'es' ) );

		// Post quick edit
		// + the language is free in the translation group
		$_REQUEST = $_POST = array(
			'action'             => 'inline-save',
			'inline_lang_choice' => 'fr',
			'_inline_edit'       => wp_create_nonce( 'inlineeditnonce' ),
		);

		wp_update_term( $fr = $term_id, 'category' );
		$this->assertEquals( 'fr', self::$model->term->get_language( $term_id )->slug );
		$this->assertEqualSetsWithIndex( compact( 'fr', 'de', 'es' ), self::$model->term->get_translations( $es ) );

		// edit-tags quick edit
		// + the language is *not* free in the translation group
		$_REQUEST = $_POST = array(
			'action'             => 'inline-save-tax',
			'inline_lang_choice' => 'de',
			'_inline_edit'       => wp_create_nonce( 'taxinlineeditnonce' ),
		);

		wp_update_term( $term_id, 'category' );
		$this->assertEquals( 'de', self::$model->term->get_language( $term_id )->slug );
		$this->assertEqualSetsWithIndex( compact( 'de', 'es' ), self::$model->term->get_translations( $es ) );
		$this->assertEquals( array( 'de' => $term_id ), self::$model->term->get_translations( $term_id ) );
	}

	public function test_create_term_from_post_bulk_edit() {
		$this->pll_admin->filters_post = new PLL_Admin_Filters_Post( $this->pll_admin ); // We need this too
		$this->pll_admin->posts = new PLL_CRUD_Posts( $this->pll_admin );

		$posts = self::factory()->post->create_many( 2 );
		self::$model->post->set_language( $posts[0], 'en' );
		self::$model->post->set_language( $posts[1], 'fr' );

		$test_tag = self::factory()->tag->create( array( 'name' => 'test_tag' ) );
		self::$model->term->set_language( $test_tag, 'fr' );

		// First do not modify any language
		$_REQUEST = $_GET = array(
			'inline_lang_choice' => -1,
			'_wpnonce'           => wp_create_nonce( 'bulk-posts' ),
			'bulk_edit'          => 'Update',
			'post'               => $posts,
			'_status'            => 'publish',
			'tax_input'          => array( 'post_tag' => 'new_tag,test_tag' ),
		);
		do_action( 'load-edit.php' );
		$done = bulk_edit_posts( $_REQUEST );

		$tags_en = wp_get_post_tags( $posts[0] );
		$new_en = wp_filter_object_list( $tags_en, array( 'name' => 'new_tag' ), 'AND', 'term_id' );
		$new_en = reset( $new_en );
		$test_en = wp_filter_object_list( $tags_en, array( 'name' => 'test_tag' ), 'AND', 'term_id' );
		$test_en = reset( $test_en );

		$tags_fr = wp_get_post_tags( $posts[1] );
		$new_fr = wp_filter_object_list( $tags_fr, array( 'name' => 'new_tag' ), 'AND', 'term_id' );
		$new_fr = reset( $new_fr );
		$test_fr = wp_filter_object_list( $tags_fr, array( 'name' => 'test_tag' ), 'AND', 'term_id' );
		$test_fr = reset( $test_fr );

		$this->assertEquals( $test_tag, $test_fr );
		$this->assertEquals( 'fr', self::$model->term->get_language( $new_fr )->slug );
		$this->assertEquals( 'fr', self::$model->term->get_language( $test_fr )->slug );
		$this->assertEquals( 'en', self::$model->term->get_language( $new_en )->slug );
		$this->assertEquals( 'en', self::$model->term->get_language( $test_en )->slug );
		$this->assertEqualSetsWithIndex( array( 'en' => $new_en, 'fr' => $new_fr ), self::$model->term->get_translations( $new_en ) );
		$this->assertEqualSetsWithIndex( array( 'en' => $test_en, 'fr' => $test_fr ), self::$model->term->get_translations( $test_en ) );

		// Second modify all languages
		$_GET['inline_lang_choice'] = $_REQUEST['inline_lang_choice'] = 'fr';
		$_GET['tax_input']  = $_REQUEST['tax_input'] = array( 'post_tag' => 'third_tag' );
		do_action( 'load-edit.php' );
		$done = bulk_edit_posts( $_REQUEST );

		$tags = wp_get_post_tags( $posts[0] );
		$third = wp_filter_object_list( $tags, array( 'name' => 'third_tag' ), 'AND', 'term_id' );
		$third = reset( $third );
		$this->assertEquals( 'fr', self::$model->term->get_language( $third )->slug );

		$tags = wp_list_pluck( $tags, 'term_id' );
		$this->assertEqualSets( array( $new_fr, $test_fr, $third ), $tags );

		$tags = wp_get_post_tags( $posts[1] );
		$tags = wp_list_pluck( $tags, 'term_id' );
		$this->assertEqualSets( array( $new_fr, $test_fr, $third ), $tags );
	}

	public function test_delete_term() {
		$en = self::factory()->category->create();
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->category->create();
		self::$model->term->set_language( $fr, 'fr' );

		$de = self::factory()->category->create();
		self::$model->term->set_language( $de, 'de' );

		self::$model->term->save_translations( $en, compact( 'en', 'fr', 'de' ) );

		wp_delete_term( $en, 'category' ); // Forces delete
		$this->assertEqualSetsWithIndex( compact( 'fr', 'de' ), self::$model->term->get_translations( $fr ) );

		// Bug fixed in 2.2
		$this->assertEmpty( self::$model->term->get_object_term( $en, 'term_translations' ) ); // Relationship deleted
		$group = self::$model->term->get_object_term( $fr, 'term_translations' );
		$this->assertEquals( 2, $group->count ); // Count updated
	}

	protected function get_edit_term_form( $tag_ID, $taxonomy ) {
		// Prepare all needed info before loading the entire form
		$GLOBALS['post_type'] = 'post';
		$tax = get_taxonomy( $taxonomy );
		$_REQUEST['taxonomy'] = $taxonomy;
		$_REQUEST['tag_ID'] = $tag_ID;
		$tag = get_term( $tag_ID, $taxonomy, OBJECT, 'edit' );
		$wp_http_referer = home_url( '/wp-admin/edit-tags.php?taxonomy=category' );
		$message = '';
		set_current_screen( 'edit-tags' );
		$GLOBALS['pagenow'] = 'term.php'; // WP 4.5+
		$this->pll_admin->set_current_language();

		ob_start();
		include ABSPATH . 'wp-admin/edit-tag-form.php';
		return ob_get_clean();
	}

	public function test_parent_dropdown_in_edit_tags() {
		$this->pll_admin->default_term = new PLL_Admin_Default_Term( $this->pll_admin );

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );

		$tag_ID = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $tag_ID, 'fr' );

		$this->pll_admin->links = new PLL_Admin_Links( $this->pll_admin );

		$form = $this->get_edit_term_form( $tag_ID, 'category' );
		$this->assertFalse( strpos( $form, 'test' ) );
		$this->assertNotFalse( strpos( $form, 'essai' ) );

		// The admin language filter must have no impact
		$this->pll_admin->pref_lang = $this->pll_admin->filter_lang = self::$model->get_language( 'en' );
		$form = $this->get_edit_term_form( $tag_ID, 'category' );
		$this->assertFalse( strpos( $form, 'test' ) );
		$this->assertNotFalse( strpos( $form, 'essai' ) );
		$this->pll_admin->filter_lang = false;

		// Even when we just activated the admin language filter
		$_REQUEST['lang'] = $_GET['lang'] = 'en';
		$form = $this->get_edit_term_form( $tag_ID, 'category' );
		$this->assertFalse( strpos( $form, 'test' ) );
		$this->assertNotFalse( strpos( $form, 'essai' ) );
	}

	public function test_language_dropdown_and_translations_in_edit_tags() {
		$this->pll_admin->default_term = new PLL_Admin_Default_Term( $this->pll_admin );

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );

		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$this->pll_admin->links = new PLL_Admin_Links( $this->pll_admin );

		$lang = self::$model->get_language( 'fr' );
		$form = $this->get_edit_term_form( $fr, 'category' );
		$form = mb_convert_encoding( $form, 'HTML-ENTITIES', 'UTF-8' ); // Due to "Français"
		$doc = new DomDocument();
		$doc->loadHTML( $form );
		$xpath = new DOMXpath( $doc );

		$option = $xpath->query( '//select[@name="term_lang_choice"]/option[.="' . $lang->name . '"]' );
		$this->assertEquals( 'selected', $option->item( 0 )->getAttribute( 'selected' ) );

		$input = $xpath->query( '//input[@id="tr_lang_en"]' );
		$this->assertEquals( 'test', $input->item( 0 )->getAttribute( 'value' ) );

		$input = $xpath->query( '//input[@id="tr_lang_de"]' );
		$this->assertEquals( '', $input->item( 0 )->getAttribute( 'value' ) ); // No translation in German
	}

	protected function get_parent_dropdown_in_new_term_form( $taxonomy ) {
		// NB: impossible to load edit-tags.php entirely as it would attempt to load a second instance of WP
		// which is impossible due to constant definitions such as ABSPATH

		set_current_screen( 'edit-tags.php' );

		// So let's copy paste WP 4.4 code:
		ob_start();
		$dropdown_args = array(
			'hide_empty'       => 0,
			'hide_if_empty'    => false,
			'taxonomy'         => $taxonomy,
			'name'             => 'parent',
			'orderby'          => 'name',
			'hierarchical'     => true,
			'show_option_none' => __( 'None' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
		);

		// FIXME this filter is available since WP 4.2 and is worth looking at ( could simplify get_queried_language? )
		$dropdown_args = apply_filters( 'taxonomy_parent_dropdown_args', $dropdown_args, $taxonomy, 'new' );
		wp_dropdown_categories( $dropdown_args );
		return ob_get_clean();
	}

	public function test_parent_dropdown_in_new_tag() {
		$this->pll_admin->pref_lang = self::$model->get_language( 'en' );
		$_REQUEST['taxonomy'] = 'category';

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );

		$child = self::factory()->term->create( array( 'taxonomy' => 'category', 'parent' => $en ) );

		$GLOBALS['pagenow'] = 'edit-tags.php';
		$this->pll_admin->set_current_language();
		$dropdown = $this->get_parent_dropdown_in_new_term_form( 'category' );

		$this->assertNotFalse( strpos( $dropdown, '<option class="level-0" value="' . $en . '">test</option>' ) );
		$this->assertFalse( strpos( $dropdown, '<option class="level-0" value="' . $fr . '">essai</option>' ) );
		$this->assertFalse( strpos( $dropdown, 'selected' ) );

		$_GET = array(
			'taxonomy' => 'category',
			'new_lang' => 'fr',
			'from_tag' => $child,
		);
		$this->pll_admin->set_current_language();
		$dropdown = $this->get_parent_dropdown_in_new_term_form( 'category' );

		$this->assertFalse( strpos( $dropdown, '<option class="level-0" value="' . $en . '">test</option>' ) );
		$this->assertNotFalse( strpos( $dropdown, '<option class="level-0" value="' . $fr . '">essai</option>' ) );
		$this->assertFalse( strpos( $dropdown, 'selected' ) );

		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );
		$dropdown = $this->get_parent_dropdown_in_new_term_form( 'category' );

		$this->assertNotFalse( strpos( $dropdown, '<option class="level-0" value="' . $fr . '" selected="selected">essai</option>' ) );
	}

	public function test_language_dropdown_and_translations_in_new_tags() {
		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );

		$this->pll_admin->links = new PLL_Admin_Links( $this->pll_admin );

		$_GET['taxonomy'] = 'category';
		$GLOBALS['post_type'] = 'post';
		$lang = $this->pll_admin->pref_lang = self::$model->get_language( 'en' );
		$this->pll_admin->set_current_language();

		ob_start();
		do_action( 'category_add_form_fields' );
		$form = ob_get_clean();
		$form = mb_convert_encoding( $form, 'HTML-ENTITIES', 'UTF-8' ); // Due to "Français"
		$doc = new DomDocument();
		$doc->loadHTML( $form );
		$xpath = new DOMXpath( $doc );

		$option = $xpath->query( '//select[@name="term_lang_choice"]/option[.="' . $lang->name . '"]' );
		$this->assertEquals( 'selected', $option->item( 0 )->getAttribute( 'selected' ) );

		$this->assertEmpty( $xpath->query( '//input[@id="tr_lang_en"]' )->length );

		$input = $xpath->query( '//input[@id="tr_lang_fr"]' );
		$this->assertEquals( '', $input->item( 0 )->getAttribute( 'value' ) ); // No translation in French

		$_GET['from_tag'] = $en;
		$_GET['new_lang'] = 'fr';
		$this->pll_admin->set_current_language();
		$lang = self::$model->get_language( 'fr' );

		ob_start();
		do_action( 'category_add_form_fields' );
		$form = ob_get_clean();
		$form = mb_convert_encoding( $form, 'HTML-ENTITIES', 'UTF-8' ); // Due to "Français"
		$doc = new DomDocument();
		$doc->loadHTML( $form );
		$xpath = new DOMXpath( $doc );

		$option = $xpath->query( '//select[@name="term_lang_choice"]/option[.="' . $lang->name . '"]' );
		$this->assertEquals( 'selected', $option->item( 0 )->getAttribute( 'selected' ) );

		$this->assertEmpty( $xpath->query( '//input[@id="tr_lang_fr"]' )->length );

		$input = $xpath->query( '//input[@id="tr_lang_en"]' );
		$this->assertEquals( 'test', $input->item( 0 )->getAttribute( 'value' ) );

		$input = $xpath->query( '//input[@id="tr_lang_de"]' );
		$this->assertEquals( '', $input->item( 0 )->getAttribute( 'value' ) ); // No translation in German
	}

	public function test_post_categories_meta_box() {
		$fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );

		$post = self::factory()->post->create_and_get();
		self::$model->post->set_language( $post->ID, 'fr' );

		$_GET['post'] = $post->ID;
		$GLOBALS['pagenow'] = 'post.php';
		$this->pll_admin->set_current_language();
		require_once ABSPATH . 'wp-admin/includes/meta-boxes.php';

		ob_start();
		post_categories_meta_box( $post, array() );
		$out = ob_get_clean();

		$this->assertFalse( strpos( $out, 'test' ) );
		$this->assertNotFalse( strpos( $out, 'essai' ) );
	}

	public function test_nav_menu_item_taxonomy_meta_box() {
		$fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );

		require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
		$taxonomy['args'] = get_taxonomy( 'category' );
		$this->pll_admin->set_current_language();

		ob_start();
		wp_nav_menu_item_taxonomy_meta_box( null, $taxonomy );
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'test' ) );
		$this->assertNotFalse( strpos( $out, 'essai' ) );

		// The admin language filter is active
		$this->pll_admin->pref_lang = $this->pll_admin->filter_lang = self::$model->get_language( 'en' );
		$this->pll_admin->set_current_language();

		ob_start();
		wp_nav_menu_item_taxonomy_meta_box( null, $taxonomy );
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'test' ) );
		$this->assertFalse( strpos( $out, 'essai' ) );
	}

	public function test_get_terms_language_filter() {
		$fr = self::factory()->term->create( array( 'taxonomy' => 'post_tag' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$en = self::factory()->term->create( array( 'taxonomy' => 'post_tag' ) );
		self::$model->term->set_language( $en, 'en' );

		$es = self::factory()->term->create( array( 'taxonomy' => 'post_tag' ) );
		self::$model->term->set_language( $es, 'es' );

		$terms = get_terms( 'post_tag', array( 'fields' => 'ids', 'hide_empty' => false, 'lang' => 'en' ) );
		$this->assertEqualSets( array( $en ), $terms );

		$terms = get_terms( 'post_tag', array( 'fields' => 'ids', 'hide_empty' => false, 'lang' => array( 'en', 'fr' ) ) );
		$this->assertEqualSets( array( $fr, $en ), $terms );

		$terms = get_terms( 'post_tag', array( 'fields' => 'ids', 'hide_empty' => false, 'lang' => 0 ) );
		$this->assertEqualSets( array( $en, $fr, $es ), $terms );

		$terms = get_terms( 'post_tag', array( 'fields' => 'ids', 'hide_empty' => false, 'lang' => 'en,fr' ) );
		$this->assertEqualSets( array( $en, $fr ), $terms );
	}

	public function test_create_terms_with_same_name() {
		$_REQUEST = $_POST = array(
			'action'           => 'add-tag',
			'term_lang_choice' => 'en',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
		);

		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		$this->assertEquals( 'en', self::$model->term->get_language( $en )->slug );

		// Second category in English with the same name.
		$error = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );

		$this->assertWPError( $error );

		$_POST['term_lang_choice'] = 'fr';
		$fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );

		$term = get_term( $fr, 'category' );
		$this->assertEquals( 'test-fr', $term->slug );
		$this->assertEquals( 'fr', self::$model->term->get_language( $fr )->slug );

		// Second category in French with the same name.
		$error = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );

		$this->assertWPError( $error );
	}

	public function test_get_translations_from_term_id() {
		// With 3 posts.
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$de = self::factory()->post->create();
		self::$model->post->set_language( $de, 'de' );

		$es = self::factory()->post->create();
		self::$model->post->set_language( $es, 'es' );

		$expected = compact( 'en', 'de', 'es' );

		self::$model->post->save_translations( $en, $expected );

		$term = wp_get_object_terms( $en, 'post_translations' );

		$this->assertIsArray( $term, 'The list of translation terms should be an array.' );
		$this->assertCount( 1, $term, 'The list of translation terms should contain the term we just created, and only it.' );

		$term         = reset( $term );
		$translations = self::$model->post->get_translations_from_term_id( $term->term_id );

		$this->assertSameSets( $expected, $translations, 'The list of translation terms should match the one we just created.' );

		// With only 1 post.
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$expected = compact( 'en' );

		self::$model->post->save_translations( $en, array() );

		$term = wp_get_object_terms( $en, 'post_translations' );

		$this->assertIsArray( $term, 'The list of translation terms should be an array.' );
		$this->assertEmpty( $term, 'The list of translation terms should be empty.' );
	}

	public function test_current_language_when_saving_term() {
		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$model->term->set_language( $en, 'en' );

		$GLOBALS['pagenow'] = 'term.php';

		$_REQUEST = $_POST = array(
			'taxonomy' => 'category',
			'tag_ID'   => $en,
		);
		$this->pll_admin->set_current_language();

		$this->assertEquals( 'en', $this->pll_admin->curlang->slug );
	}

	public function test_change_language_bulk_edit_translated_post_with_same_named_categories() {
		// Create a category and its translations.
		$en_cat = self::factory()->category->create( array( 'name' => 'test', 'slug' => 'test' ) );
		self::$model->term->set_language( $en_cat, 'en' );

		$de_cat = self::factory()->category->create( array( 'name' => 'test', 'slug' => 'test-de' ) );
		self::$model->term->set_language( $de_cat, 'de' );

		$es_cat = self::factory()->category->create( array( 'name' => 'test', 'slug' => 'test-es' ) );
		self::$model->term->set_language( $es_cat, 'es' );

		$expected_cats_translations = array(
			'en' => $en_cat,
			'de' => $de_cat,
			'es' => $es_cat,
		);
		self::$model->term->save_translations(
			$en_cat,
			$expected_cats_translations
		);

		// Create some posts.
		$en_post = self::factory()->post->create( array( 'post_category' => array( $en_cat ) ) );
		self::$model->post->set_language( $en_post, 'en' );

		$de_post = self::factory()->post->create( array( 'post_category' => array( $de_cat ) ) );
		self::$model->post->set_language( $de_post, 'de' );

		$es_post = self::factory()->post->create( array( 'post_category' => array( $es_cat ) ) );
		self::$model->post->set_language( $es_post, 'es' );

		self::$model->post->save_translations(
			$en_post,
			array(
				'en' => $en_post,
				'de' => $de_post,
				'es' => $es_post,
			)
		);

		// Set globals like a language change in bluk edit and update a category.
		$_REQUEST = $_GET = array(
			'inline_lang_choice' => 'fr',
			'_wpnonce'           => wp_create_nonce( 'bulk-posts' ),
			'bulk_edit'          => 'Update',
			'post'               => $en_post,
			'_status'            => 'publish',
		);

		do_action( 'load-edit.php' );
		bulk_edit_posts( $_REQUEST );

		$fr_object = wp_get_object_terms( $en_post, 'category' );
		$this->assertIsArray( $fr_object, 'Expected wp_get_object_terms() to return an array of categories.' );
		$this->assertCount( 1, $fr_object, 'Expected the post to have one category, and only one.' );
		$fr_object = reset( $fr_object );

		$this->assertSame( 'test-fr', $fr_object->slug, 'The slug should be suffixed with the french language.' );
		$this->assertSame( 'fr', self::$model->term->get_language( $fr_object->term_id )->slug, 'The category language should be French.' );
		$this->assertSameSetsWithIndex( $expected_cats_translations, self::$model->term->get_translations( $en_cat ), 'The original translation group should not have been updated.' );
		$this->assertSameSetsWithIndex( array( 'fr' => $fr_object->term_id ), self::$model->term->get_translations( $fr_object->term_id ), 'The translation group of the new term should contain only this term.' );

		// Clean Up.
		unset( $_REQUEST, $_GET );
	}

	public function test_child_categories_with_same_name() {
		// Create parent categories.
		$en_parent = self::factory()->category->create( array( 'name' => 'parent', 'slug' => 'parent' ) );
		self::$model->term->set_language( $en_parent, 'en' );

		$de_parent = self::factory()->category->create( array( 'name' => 'parent', 'slug' => 'parent-de' ) );
		self::$model->term->set_language( $de_parent, 'de' );

		self::$model->term->save_translations(
			$en_parent,
			array(
				'en' => $en_parent,
				'de' => $de_parent,
			)
		);

		// Create only english child category for the moment.
		$en_child = self::factory()->category->create( array( 'name' => 'child', 'slug' => 'child', 'parent' => $en_parent ) );
		self::$model->term->set_language( $en_child, 'en' );

		$_REQUEST = $_POST = array(
			'parent'           => $de_parent,
			'term_lang_choice' => 'de',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
			'term_tr_lang'     => array( 'en' => $en_child ),
		);
		$de_child     = wp_insert_term( 'child', 'category', array( 'parent' => $de_parent ) );
		$de_child_obj = get_term( $de_child['term_id'], 'category' );

		$this->assertIsInt( $de_child['term_id'], 'German category should be created.' );
		$this->assertSame( 'de', self::$model->term->get_language( $de_child['term_id'] )->slug, 'German child category should has its language set.' );
		$this->assertSameSetsWithIndex( array( 'en' => $en_child, 'de' => $de_child['term_id'] ), self::$model->term->get_translations( $de_child['term_id'], 'German category has no translations group.' ) );
		$this->assertSame( 'child-de', $de_child_obj->slug, 'German category slug should be suffixed with the language.' );

		// Clean Up.
		unset( $_REQUEST, $_POST );
	}

	public function test_filter_language_for_terms_with_same_slug() {
		$fr_lang = self::$model->get_language( 'fr' );

		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );

		$en_lang = self::$model->term->get_language( $en );

		$this->assertInstanceOf( PLL_Language::class, $en_lang, 'Expected the English term to have a language.' );
		$this->assertSame( 'en', $en_lang->slug, 'English term has not the right language set.' );

		// Filter the language for the newt inserted term. Do not set any globals!
		add_filter(
			'pll_inserted_term_language',
			function ( $found_language ) use ( $fr_lang ) {
				if ( $found_language instanceof PLL_Language ) {
					return $found_language;
				}

				return $fr_lang;
			}
		);

		// Let's create a translated term with the same name.
		$fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$term    = get_term( $fr, 'category' );
		$fr_lang = self::$model->term->get_language( $fr );

		$this->assertInstanceOf( WP_Term::class, $term, 'Expected the French term to have a category.' );
		$this->assertSame( 'test-fr', $term->slug, 'French term slug is not suffixed with language.' );
		$this->assertInstanceOf( PLL_Language::class, $fr_lang, 'Expected the French term to have a language.' );
		$this->assertSame( 'fr', $fr_lang->slug, 'French term has not the right language set.' );

		// Let's create a third term with the same name.
		$error = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );

		$this->assertWPError( $error, 'Third term with the same slug shouldn\'t be created.' );
	}

	public function test_update_term_name() {
		$original_name = 'Test Me';
		$new_name      = 'Well Tested';

		$cat_en = $this->factory()->category->create_and_get(
			array(
				'name' => $original_name,
			)
		);
		self::$model->term->set_language( $cat_en->term_id, 'en' );

		$this->assertSame( sanitize_title( $original_name ), $cat_en->slug, 'The category slug is well created.' );

		// Add globals like an admin request.
		$_REQUEST = $_POST = array(
			'term_lang_choice' => 'en',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
		);

		// Now update the category with a new name.
		$updated_cat = wp_update_term(
			$cat_en->term_id,
			'category',
			array(
				'name' => $new_name,
			)
		);
		$updated_cat_obj = get_term( $updated_cat['term_id'] );

		$this->assertSame( $new_name, $updated_cat_obj->name, 'The category name should have been modified.' );
		$this->assertSame( $cat_en->slug, $updated_cat_obj->slug, 'The category slug should remain the same.' );

		unset( $_REQUEST, $_POST );
	}

	public function test_update_term_slug() {
		$original_name = 'Test Me';
		$new_slug      = 'well-tested';

		$cat_en = $this->factory()->category->create_and_get(
			array(
				'name' => $original_name,
			)
		);
		self::$model->term->set_language( $cat_en->term_id, 'en' );

		$this->assertSame( sanitize_title( $original_name ), $cat_en->slug, 'The category slug is not well created.' );

		// Add globals like an admin request.
		$_REQUEST = $_POST = array(
			'term_lang_choice' => 'en',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
		);

		// Now update the category with a new slug.
		$updated_cat = wp_update_term(
			$cat_en->term_id,
			'category',
			array(
				'slug' => $new_slug,
			)
		);
		$this->assertIsArray( $updated_cat );
		$updated_cat_obj = get_term( $updated_cat['term_id'] );

		$this->assertSame( $new_slug, $updated_cat_obj->slug, 'The category slug should have been modified.' );

		unset( $_REQUEST, $_POST );
	}
}
