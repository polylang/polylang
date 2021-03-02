<?php


class Default_Term_Test extends PLL_UnitTestCase {

	function setUp() {
		parent::setUp();

		$links_model     = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );

		$this->pll_admin->filters_term    = new PLL_Admin_Filters_Term( $this->pll_admin );
		$this->pll_admin->filters_columns = new PLL_Admin_Filters_Columns( $this->pll_admin );
	}

	function tearDown() {
		self::delete_all_languages();

		parent::tearDown();
	}

	function get_edit_term_form( $tag_ID, $taxonomy ) {
		// Prepare all needed info before loading the entire form
		$GLOBALS['post_type'] = 'post';
		$tax                  = get_taxonomy( $taxonomy );
		$_GET['taxonomy']     = $taxonomy;
		$_REQUEST['tag_ID']   = $_GET['tag_ID'] = $tag_ID;
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

	function init_languages_and_default_term() {
		$this->pll_admin->default_term = new PLL_Admin_Default_Term( $this->pll_admin );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE_formal' );
		self::create_language( 'es_ES' );
	}

	function test_default_category_in_edit_tags() {
		$this->pll_admin->links = new PLL_Admin_Links( $this->pll_admin );

		$this->init_languages_and_default_term();

		$default = self::$model->term->get( get_option( 'default_category' ), 'de' );
		$de      = self::$model->get_language( 'de' );
		$form    = $this->get_edit_term_form( $default, 'category' );
		$form    = mb_convert_encoding( $form, 'HTML-ENTITIES', 'UTF-8' ); // Due to "Français"
		$doc     = new DomDocument();
		$doc->loadHTML( $form );
		$xpath = new DOMXpath( $doc );

		$option = $xpath->query( '//select[@name="term_lang_choice"]' );
		$this->assertEquals( 'disabled', $option->item( 0 )->getAttribute( 'disabled' ) );

		$option = $xpath->query( '//select[@name="term_lang_choice"]/option[.="' . $de->name . '"]' );
		$this->assertEquals( 'selected', $option->item( 0 )->getAttribute( 'selected' ) );

		$input = $xpath->query( '//input[@id="tr_lang_fr"]' );
		$this->assertEquals( 'disabled', $input->item( 0 )->getAttribute( 'disabled' ) );
	}

	function test_term_language_with_default_category() {
		$this->init_languages_and_default_term();

		$GLOBALS['post_type'] = 'post';
		$GLOBALS['taxonomy']  = 'category';

		$default = (int) get_option( 'default_category' );

		// with capability
		$column = $this->pll_admin->default_term->term_column( '', 'language_en', $default );
		$this->assertNotFalse( strpos( $column, 'default_cat' ) );
	}

	function test_add_and_delete_language() {
		$this->init_languages_and_default_term();

		// check default category
		$default_cat_lang = self::$model->term->get_language( get_option( 'default_category' ) );
		$this->assertEquals( 'en', $default_cat_lang->slug );
	}

	function test_new_default_category() {
		$this->init_languages_and_default_term();

		$term_id = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'new-default' ) );
		update_option( 'default_category', $term_id );

		$this->assertEquals( $term_id, get_option( 'default_category' ) );
		$translations = self::$model->term->get_translations( $term_id );
		$this->assertEqualSets( array( 'en', 'fr', 'de', 'es' ), array_keys( $translations ) );
	}
}
