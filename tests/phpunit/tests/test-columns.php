<?php

class Columns_Test extends PLL_UnitTestCase {
	static $editor;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
	}

	function setUp() {
		parent::setUp();

		// set a user to pass current_user_can tests
		wp_set_current_user( self::$editor );

		self::$polylang->links = new PLL_Admin_Links( self::$polylang );
		self::$polylang->filters_columns = new PLL_Admin_Filters_Columns( self::$polylang );
	}

	function tearDown() {
		unset( self::$polylang->filter_lang );

		parent::tearDown();
	}

	function test_post_with_no_language() {
		$post_id = $this->factory->post->create();

		ob_start();
		self::$polylang->filters_columns->post_column( 'language_en', $post_id );
		self::$polylang->filters_columns->post_column( 'language_fr', $post_id );
		$this->assertEmpty( ob_get_clean() );
	}

	function test_post_language() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		// with capability
		ob_start();
		self::$polylang->filters_columns->post_column( 'language_en', $en );
		$column = ob_get_clean();
		$this->assertNotFalse( strpos( $column, 'pll_column_flag' ) && strpos( $column, 'href' ) );

		// without capability
		wp_set_current_user( 0 );
		ob_start();
		self::$polylang->filters_columns->post_column( 'language_en', $en );
		$column = ob_get_clean();
		$this->assertNotFalse( strpos( $column, 'pll_column_flag' ) );
		$this->assertFalse( strpos( $column, 'href' ) );
	}

	function test_untranslated_post() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		// with capability
		ob_start();
		self::$polylang->filters_columns->post_column( 'language_fr', $en );
		$column = ob_get_clean();
		$this->assertNotFalse( strpos( $column, 'pll_icon_add' ) && strpos( $column, 'from_post' ) );

		// without capability
		wp_set_current_user( 0 );
		ob_start();
		self::$polylang->filters_columns->post_column( 'language_fr', $en );
		$this->assertEmpty( ob_get_clean() );
	}

	// special case for media
	function test_untranslated_media() {
		$en = $this->factory->attachment->create_object( 'image.jpg' );
		self::$polylang->model->post->set_language( $en, 'en' );

		// with capability
		ob_start();
		self::$polylang->filters_columns->post_column( 'language_fr', $en );
		$column = ob_get_clean();
		$this->assertNotFalse( strpos( $column, 'pll_icon_add' ) && strpos( $column, 'from_media' ) );

		// without capability
		wp_set_current_user( 0 );
		ob_start();
		self::$polylang->filters_columns->post_column( 'language_fr', $en );
		$this->assertEmpty( ob_get_clean() );
	}

	function test_translated_post() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		// with capability
		ob_start();
		self::$polylang->filters_columns->post_column( 'language_fr', $en );
		$this->assertNotFalse( strpos( ob_get_clean(), 'pll_icon_edit' ) );

		// without capability
		wp_set_current_user( 0 );
		ob_start();
		self::$polylang->filters_columns->post_column( 'language_fr', $en );
		$this->assertEmpty( ob_get_clean() );
	}

	function test_term_with_no_language() {
		$GLOBALS['post_type'] = 'post';
		$GLOBALS['taxonomy'] = 'category';

		$term_id = $this->factory->category->create();

		$column_en = self::$polylang->filters_columns->term_column( '', 'language_en', $term_id );
		$column_fr = self::$polylang->filters_columns->term_column( '', 'language_fr', $term_id );
		$this->assertEmpty( $column_en );
		$this->assertEmpty( $column_fr );
	}

	function test_term_language() {
		$GLOBALS['post_type'] = 'post';
		$GLOBALS['taxonomy'] = 'category';

		$en = $this->factory->category->create();
		self::$polylang->model->term->set_language( $en, 'en' );

		// with capability
		$column = self::$polylang->filters_columns->term_column( '', 'language_en', $en );
		$this->assertNotFalse( strpos( $column, 'pll_column_flag' ) && strpos( $column, 'href' ) );

		// without capability
		wp_set_current_user( 0 );
		$column = self::$polylang->filters_columns->term_column( '', 'language_en', $en );
		$this->assertNotFalse( strpos( $column, 'pll_column_flag' ) );
		$this->assertFalse( strpos( $column, 'href' ) );
	}

	function test_term_language_with_default_category() {
		$GLOBALS['post_type'] = 'post';
		$GLOBALS['taxonomy'] = 'category';

		$default = (int) get_option( 'default_category' );

		// with capability
		$column = self::$polylang->filters_columns->term_column( '', 'language_en', $default );
		$this->assertNotFalse( strpos( $column, 'default_cat' ) );
	}

	function test_untranslated_term() {
		$GLOBALS['post_type'] = 'post';
		$GLOBALS['taxonomy'] = 'category';

		$en = $this->factory->category->create();
		self::$polylang->model->term->set_language( $en, 'en' );

		// with capability
		$column = self::$polylang->filters_columns->term_column( '', 'language_fr', $en );
		$this->assertNotFalse( strpos( $column, 'pll_icon_add' ) );

		// without capability
		wp_set_current_user( 0 );
		ob_start();
		self::$polylang->filters_columns->term_column( '', 'language_fr', $en );
		$this->assertEmpty( ob_get_clean() );
	}

	function test_translated_term() {
		$GLOBALS['post_type'] = 'post';
		$GLOBALS['taxonomy'] = 'category';

		$en = $this->factory->category->create();
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->category->create();
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr' ) );

		// with capability
		$column = self::$polylang->filters_columns->term_column( '', 'language_fr', $en );
		$this->assertNotFalse( strpos( $column, 'pll_icon_edit' ) );

		// without capability
		wp_set_current_user( 0 );
		ob_start();
		self::$polylang->filters_columns->term_column( '', 'language_fr', $en );
		$this->assertEmpty( ob_get_clean() );
	}

	function test_add_post_column() {
		// We need to call directly the filter "manage_{$screen->id}_columns" due to the static var in get_column_headers()
		$list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => 'edit.php' ) );
		list( $columns, $hidden, $sortable, $primary ) = $list_table->get_column_info();
		$columns = array_intersect_key( $columns, array_flip( array( 'comments' ) ) ); // Keep only the Comments column
		$columns = apply_filters( 'manage_edit-post_columns', $columns );
		$columns = array_keys( $columns );
		$en = array_search( 'language_en', $columns );

		$this->assertNotFalse( $en );
		$this->assertEquals( 'language_fr', $columns[ $en + 1 ] );
		$this->assertEquals( 'comments', $columns[ $en + 2 ] );
	}

	function test_add_post_column_with_filter() {
		self::$polylang->filter_lang = self::$polylang->model->get_language( 'en' );
		$list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => 'edit.php' ) );
		list( $columns, $hidden, $sortable, $primary ) = $list_table->get_column_info();
		$this->assertNotFalse( array_search( 'language_en', $hidden ) );
		$this->assertFalse( array_search( 'language_fr', $hidden ) );
	}

	function test_add_term_column() {
		// We need to call directly the filter "manage_{$screen->id}_columns" due to the static var in get_column_headers()
		$list_table = _get_list_table( 'WP_Terms_List_Table', array( 'screen' => 'edit-tags.php' ) );
		list( $columns, $hidden, $sortable, $primary ) = $list_table->get_column_info();
		$columns = array_intersect_key( $columns, array_flip( array( 'posts' ) ) ); // Keep only the Count column
		$columns = apply_filters( 'manage_edit-post_tag_columns', $columns );
		$columns = array_keys( $columns );
		$en = array_search( 'language_en', $columns );

		$this->assertNotFalse( $en );
		$this->assertEquals( 'language_fr', $columns[ $en + 1 ] );
		$this->assertEquals( 'posts', $columns[ $en + 2 ] );
	}

	function test_add_term_column_with_filter() {
		self::$polylang->filter_lang = self::$polylang->model->get_language( 'fr' );
		$list_table = _get_list_table( 'WP_Terms_List_Table', array( 'screen' => 'edit-tags.php' ) );
		list( $columns, $hidden, $sortable, $primary ) = $list_table->get_column_info();
		$this->assertNotFalse( array_search( 'language_fr', $hidden ) );
		$this->assertFalse( array_search( 'language_en', $hidden ) );
	}

	function test_post_inline_edit() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		$list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => 'edit.php' ) );
		$list_table->prepare_items();
		$GLOBALS['post'] = $GLOBALS['wp_the_query']->post; // Needed by touch_time

		ob_start();
		$list_table->inline_edit();
		$form = ob_get_clean();
		$doc = new DomDocument();
		$doc->loadHTML( $form );
		$xpath = new DOMXpath( $doc );

		// Quick Edit
		$options = $xpath->query( '//tr[@id="inline-edit"]/td/fieldset/div/label/select[@name="inline_lang_choice"]/option' );
		$this->assertEquals( 2, $options->length );
		$this->assertEquals( 'en', $options->item( 0 )->getAttribute( 'value' ) );
		$this->assertEquals( 'fr', $options->item( 1 )->getAttribute( 'value' ) );

		// Bulk Edit
		$options = $xpath->query( '//tr[@id="bulk-edit"]/td/fieldset/div/label/select[@name="inline_lang_choice"]/option' );
		$this->assertEquals( 3, $options->length );
		$this->assertEquals( '-1', $options->item( 0 )->getAttribute( 'value' ) );
		$this->assertEquals( 'en', $options->item( 1 )->getAttribute( 'value' ) );
		$this->assertEquals( 'fr', $options->item( 2 )->getAttribute( 'value' ) );
	}
}
