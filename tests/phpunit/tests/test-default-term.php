<?php


class Default_Term_Test extends PLL_UnitTestCase {
	protected static $editor;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
	}

	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		$links_model     = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );

		$this->pll_admin->filters_term = new PLL_Admin_Filters_Term( $this->pll_admin );
		$this->pll_admin->default_term = new PLL_Admin_Default_Term( $this->pll_admin );
		$this->pll_admin->default_term->add_hooks();
		$this->pll_admin->filters_columns = new PLL_Admin_Filters_Columns( $this->pll_admin );
		$this->pll_admin->links           = new PLL_Admin_Links( $this->pll_admin );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE_formal' );
		self::create_language( 'es_ES' );
	}

	public function tear_down() {
		// Forces language deletion since they're created in `set_up()` and not `wpSetUpBeforeClass()` as usual.
		self::delete_all_languages();
		parent::tear_down();
	}

	protected function get_edit_term_form( $tag_ID, $taxonomy ) {
		// Prepare all needed info before loading the entire form
		$GLOBALS['post_type'] = 'post';
		$tax                  = get_taxonomy( $taxonomy );
		$_GET['taxonomy']     = $taxonomy;
		$_GET['tag_ID']       = $tag_ID;
		$_REQUEST['tag_ID']   = $tag_ID;
		$tag                  = get_term( $tag_ID, $taxonomy, OBJECT, 'edit' );
		$wp_http_referer      = home_url( '/wp-admin/edit-tags.php?taxonomy=category' );
		$message              = '';
		set_current_screen( 'edit-tags' );
		$GLOBALS['pagenow'] = 'term.php'; // WP 4.5+
		$this->pll_admin->set_current_language();

		ob_start();
		include ABSPATH . 'wp-admin/edit-tag-form.php';

		return ob_get_clean();
	}

	public function test_default_category_in_edit_tags() {
		$this->pll_admin->links = new PLL_Admin_Links( $this->pll_admin );

		$default = self::$model->term->get( get_option( 'default_category' ), 'de' );
		$de      = self::$model->get_language( 'de' );
		$form    = $this->get_edit_term_form( $default, 'category' );
		$doc     = new DomDocument();
		$doc->loadHTML( '<?xml encoding="UTF-8">' . $form );
		$xpath = new DOMXpath( $doc );

		$option = $xpath->query( '//select[@name="term_lang_choice"]' );
		$this->assertEquals( 'disabled', $option->item( 0 )->getAttribute( 'disabled' ) );

		$option = $xpath->query( '//select[@name="term_lang_choice"]/option[.="' . $de->name . '"]' );
		$this->assertEquals( 'selected', $option->item( 0 )->getAttribute( 'selected' ) );

		$input = $xpath->query( '//input[@id="tr_lang_fr"]' );
		$this->assertEquals( 'disabled', $input->item( 0 )->getAttribute( 'disabled' ) );
	}

	public function test_term_language_with_default_category() {

		$GLOBALS['post_type'] = 'post';
		$GLOBALS['taxonomy']  = 'category';

		$default = (int) get_option( 'default_category' );

		// with capability
		$column = $this->pll_admin->filters_columns->term_column( '', 'language_en', $default );
		$this->assertNotFalse( strpos( $column, 'default_cat' ) );
	}

	public function test_add_and_delete_language() {

		$args = array(
			'name'       => 'العربية',
			'slug'       => 'ar',
			'locale'     => 'ar',
			'rtl'        => 1,
			'flag'       => 'arab',
			'term_group' => 1,
		);

		$this->assertTrue( $this->pll_admin->model->add_language( $args ) );

		// check default category
		$default_cat_lang = self::$model->term->get_language( get_option( 'default_category' ) );
		$this->assertEquals( 'en', $default_cat_lang->slug );
	}

	public function test_new_default_category() {

		$term_id = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'new-default' ) );
		update_option( 'default_category', $term_id );

		$this->assertEquals( $term_id, get_option( 'default_category' ) );
		$translations = self::$model->term->get_translations( $term_id );
		$this->assertEqualSets( array( 'en', 'fr', 'de', 'es' ), array_keys( $translations ) );
	}

	/**
	 * Bug introduced by WP 4.3 and fixed in v1.8.2.
	 */
	public function test_default_category_in_list_table() {

		$id = self::factory()->term->create( array( 'taxonomy' => 'category' ) ); // a non default category
		$default = get_option( 'default_category' );
		$en = self::$model->term->get( $default, 'en' );
		$fr = self::$model->term->get( $default, 'fr' );

		// WP_Screen tests $_REQUEST, Polylang tests $_GET.
		$_GET['taxonomy']       = 'category';
		$_REQUEST['taxonomy']   = 'category';
		$GLOBALS['taxnow']      = 'category';
		$GLOBALS['hook_suffix'] = 'edit-tags.php';
		set_current_screen();
		$wp_list_table = _get_list_table( 'WP_Terms_List_Table' );

		ob_start();
		$wp_list_table->prepare_items();
		$wp_list_table->display();
		$list = ob_get_clean();

		// checkbox only for non default category
		$this->assertFalse( strpos( $list, '"cb-select-' . $en . '"' ) );
		$this->assertFalse( strpos( $list, '"cb-select-' . $fr . '"' ) );
		$this->assertNotFalse( strpos( $list, '"cb-select-' . $id . '"' ) );

		// delete link only for non default category
		$this->assertFalse( strpos( $list, 'edit-tags.php?action=delete&amp;taxonomy=category&amp;tag_ID=' . $en . '&amp;' ) );
		$this->assertFalse( strpos( $list, 'edit-tags.php?action=delete&amp;taxonomy=category&amp;tag_ID=' . $fr . '&amp;' ) );
		$this->assertNotFalse( strpos( $list, 'edit-tags.php?action=delete&amp;taxonomy=category&amp;tag_ID=' . $id . '&amp;' ) );
	}

	public function test_get_option_default_category() {
		$option = get_option( 'default_category' );
		$option_lang = self::$model->term->get_language( $option );

		$this->assertEquals( 'en', $option_lang->slug );

		$this->pll_admin->curlang = self::$model->get_language( 'es' );

		$option = get_option( 'default_category' );
		$option_lang = self::$model->term->get_language( $option );

		$this->assertEquals( 'es', $option_lang->slug );
	}

	public function test_update_term_when_updating_default_language() {
		$option = get_option( 'default_category' );
		$option_lang = self::$model->term->get_language( $option );
		$es_option = self::$model->term->get_translation( $option, 'es' );

		$this->assertEquals( 'en', $option_lang->slug );

		self::$model->update_default_lang( 'es' );

		$option = get_option( 'default_category' );
		$option_lang = self::$model->term->get_language( $option );

		$this->assertEquals( 'es', $option_lang->slug );
		$this->assertEquals( $option, $es_option );
	}

	public function test_custom_term_column_for_default_category() {
		$GLOBALS['taxonomy']     = 'category';
		$GLOBALS['post_type']    = 'post';
		$out                     = '';
		$column                  = 'language_en';
		$default_cat_id          = get_option( 'default_category' );
		self::$model->term->set_language( $default_cat_id, 'en' );

		$column = apply_filters( 'manage_category_custom_column', $out, $column, $default_cat_id );

		$this->assertNotEmpty( $column, 'The generated language column should not be empty.' );

		$doc = new DomDocument();
		$doc->loadHTML( $column );
		$xpath = new DOMXpath( $doc );

		$def_cat = $xpath->query( "//div[@id=\"default_cat_{$default_cat_id}\"]" );
		$this->assertSame( 1, $def_cat->length, 'Only one element with the default category ID should be rendered.' );
		$def_cat_class_attr = $def_cat->item( 0 )->getAttribute( 'class' );
		$this->assertSame( 'hidden', $def_cat_class_attr, 'The element of the default category should be hidden.' );
	}
}
