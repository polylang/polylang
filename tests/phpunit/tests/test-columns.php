<?php

class Columns_Test extends PLL_UnitTestCase {
	static $editor;

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

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
		parent::tearDown();

		$_REQUEST = $_GET = $_POST = array();
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
		$this->assertNotFalse( strpos( $column, 'pll_icon_tick' ) && strpos( $column, 'href' ) );

		// without capability
		wp_set_current_user( 0 );
		ob_start();
		self::$polylang->filters_columns->post_column( 'language_en', $en );
		$column = ob_get_clean();
		$this->assertNotFalse( strpos( $column, 'pll_icon_tick' ) );
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
		$this->assertNotFalse( strpos( $column, 'pll_icon_tick' ) && strpos( $column, 'href' ) );

		// without capability
		wp_set_current_user( 0 );
		ob_start();
		self::$polylang->filters_columns->term_column( '', 'language_en', $en );
		$column = ob_get_clean();
		$this->assertNotFalse( strpos( $column, 'pll_icon_tick' ) );
		$this->assertFalse( strpos( $column, 'href' ) );
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
		$list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => 'edit.php' ) );
		list( $columns, $hidden, $sortable, $primary ) = $list_table->get_column_info();
		$columns = array_keys( $columns );
		$en = array_search( 'language_en', $columns );

		$this->assertNotFalse( $en );
		$this->assertEquals( 'language_fr', $columns[ $en + 1 ] );
		$this->assertEquals( 'comments', $columns[ $en + 2 ] );
	}

	function test_add_post_column_with_filter() {
		$this->markTestSkipped(); // FIXME for some reason $this->curlang is empty if admin_filters_columns
		$list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => 'edit.php' ) );
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );
		list( $columns, $hidden, $sortable, $primary ) = $list_table->get_column_info();
		$columns = array_keys( $columns );
		$this->assertFalse( array_search( 'language_en', $columns ) );
		$this->assertNotFalse( array_search( 'language_fr', $columns ) );
	}

	function test_add_term_column() {
		$list_table = _get_list_table( 'WP_Terms_List_Table', array( 'screen' => 'edit-tags.php' ) );
		list( $columns, $hidden, $sortable, $primary ) = $list_table->get_column_info();
		$columns = array_keys( $columns );
		$en = array_search( 'language_en', $columns );

		$this->assertNotFalse( $en );
		$this->assertEquals( 'language_fr', $columns[ $en + 1 ] );
		$this->assertEquals( 'posts', $columns[ $en + 2 ] );
	}

	function test_add_term_column_with_filter() {
		$this->markTestSkipped(); // FIXME
		$list_table = _get_list_table( 'WP_Terms_List_Table', array( 'screen' => 'edit-tags.php' ) );
		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		list( $columns, $hidden, $sortable, $primary ) = $list_table->get_column_info();
		$columns = array_keys( $columns );
		$this->assertFalse( array_search( 'language_fr', $columns ) );
		$this->assertNotFalse( array_search( 'language_en', $columns ) );
	}
}
