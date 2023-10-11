<?php

class Columns_Test extends PLL_UnitTestCase {
	protected static $editor;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
	}

	public function set_up() {
		parent::set_up();

		// set a user to pass current_user_can tests
		wp_set_current_user( self::$editor );

		$links_model     = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );

		$this->pll_admin->links        = new PLL_Admin_Links( $this->pll_admin );
		$this->pll_admin->default_term = new PLL_Admin_Default_Term( $this->pll_admin );
		$this->pll_admin->default_term->add_hooks();
		$this->pll_admin->filters_columns = new PLL_Admin_Filters_Columns( $this->pll_admin );
	}

	public function tear_down() {
		unset( $this->pll_admin->filter_lang );

		parent::tear_down();
	}

	/**
	 * This must be the first test due to the static variable in get_culumn_headers().
	 */
	public function test_no_screen_options_in_term_screen() {
		set_current_screen( 'term.php' );
		$this->assertEmpty( get_column_headers( get_current_screen() ) );
	}

	public function test_post_with_no_language() {
		$post_id = self::factory()->post->create();

		ob_start();
		$this->pll_admin->filters_columns->post_column( 'language_en', $post_id );
		$this->pll_admin->filters_columns->post_column( 'language_fr', $post_id );
		$this->assertEmpty( ob_get_clean() );
	}

	public function test_post_language() {
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		// with capability
		ob_start();
		$this->pll_admin->filters_columns->post_column( 'language_en', $en );
		$column = ob_get_clean();
		$this->assertNotFalse( strpos( $column, 'pll_column_flag' ) && strpos( $column, 'href' ) );

		// without capability
		wp_set_current_user( 0 );
		ob_start();
		$this->pll_admin->filters_columns->post_column( 'language_en', $en );
		$column = ob_get_clean();
		$this->assertNotFalse( strpos( $column, 'pll_column_flag' ) );
		$this->assertFalse( strpos( $column, 'href' ) );
	}

	public function test_untranslated_post() {
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		// with capability
		ob_start();
		$this->pll_admin->filters_columns->post_column( 'language_fr', $en );
		$column = ob_get_clean();
		$this->assertNotFalse( strpos( $column, 'pll_icon_add' ) && strpos( $column, 'from_post' ) );

		// without capability
		wp_set_current_user( 0 );
		ob_start();
		$this->pll_admin->filters_columns->post_column( 'language_fr', $en );
		$this->assertEmpty( ob_get_clean() );
	}

	/**
	 * Special case for media.
	 */
	public function test_untranslated_media() {
		$en = self::factory()->attachment->create_object( 'image.jpg' );
		self::$model->post->set_language( $en, 'en' );

		// with capability
		ob_start();
		$this->pll_admin->filters_columns->post_column( 'language_fr', $en );
		$column = ob_get_clean();
		$this->assertNotFalse( strpos( $column, 'pll_icon_add' ) && strpos( $column, 'from_media' ) );

		// without capability
		wp_set_current_user( 0 );
		ob_start();
		$this->pll_admin->filters_columns->post_column( 'language_fr', $en );
		$this->assertEmpty( ob_get_clean() );
	}

	public function test_translated_post() {
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		// with capability
		ob_start();
		$this->pll_admin->filters_columns->post_column( 'language_fr', $en );
		$this->assertNotFalse( strpos( ob_get_clean(), 'pll_icon_edit' ) );

		// without capability
		wp_set_current_user( 0 );
		ob_start();
		$this->pll_admin->filters_columns->post_column( 'language_fr', $en );
		$this->assertEmpty( ob_get_clean() );
	}

	public function test_term_with_no_language() {
		$GLOBALS['post_type'] = 'post';
		$GLOBALS['taxonomy'] = 'category';

		$term_id = self::factory()->category->create();

		$column_en = $this->pll_admin->filters_columns->term_column( '', 'language_en', $term_id );
		$column_fr = $this->pll_admin->filters_columns->term_column( '', 'language_fr', $term_id );
		$this->assertEmpty( $column_en );
		$this->assertEmpty( $column_fr );
	}

	public function test_term_language() {
		$GLOBALS['post_type'] = 'post';
		$GLOBALS['taxonomy'] = 'category';

		$en = self::factory()->category->create();
		self::$model->term->set_language( $en, 'en' );

		// with capability
		$column = $this->pll_admin->filters_columns->term_column( '', 'language_en', $en );
		$this->assertNotFalse( strpos( $column, 'pll_column_flag' ) && strpos( $column, 'href' ) );

		// without capability
		wp_set_current_user( 0 );
		$column = $this->pll_admin->filters_columns->term_column( '', 'language_en', $en );
		$this->assertNotFalse( strpos( $column, 'pll_column_flag' ) );
		$this->assertFalse( strpos( $column, 'href' ) );
	}

	public function test_untranslated_term() {
		$GLOBALS['post_type'] = 'post';
		$GLOBALS['taxonomy'] = 'category';

		$en = self::factory()->category->create();
		self::$model->term->set_language( $en, 'en' );

		// with capability
		$column = $this->pll_admin->filters_columns->term_column( '', 'language_fr', $en );
		$this->assertNotFalse( strpos( $column, 'pll_icon_add' ) );

		// without capability
		wp_set_current_user( 0 );
		ob_start();
		$this->pll_admin->filters_columns->term_column( '', 'language_fr', $en );
		$this->assertEmpty( ob_get_clean() );
	}

	public function test_translated_term() {
		$GLOBALS['post_type'] = 'post';
		$GLOBALS['taxonomy'] = 'category';

		$en = self::factory()->category->create();
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->category->create();
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		// with capability
		$column = $this->pll_admin->filters_columns->term_column( '', 'language_fr', $en );
		$this->assertNotFalse( strpos( $column, 'pll_icon_edit' ) );

		// without capability
		wp_set_current_user( 0 );
		ob_start();
		$this->pll_admin->filters_columns->term_column( '', 'language_fr', $en );
		$this->assertEmpty( ob_get_clean() );
	}

	public function test_add_post_column() {
		// We need to call directly the filter "manage_{$screen->id}_columns" due to the static var in get_column_headers()
		$list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => 'edit.php' ) );
		$columns = $list_table->get_column_info()[0];
		$columns = array_intersect_key( $columns, array_flip( array( 'comments' ) ) ); // Keep only the Comments column
		$columns = apply_filters( 'manage_edit-post_columns', $columns );
		$columns = array_keys( $columns );
		$en = array_search( 'language_en', $columns );

		$this->assertNotFalse( $en );
		$this->assertEquals( 'language_fr', $columns[ $en + 1 ] );
		$this->assertEquals( 'comments', $columns[ $en + 2 ] );
	}

	public function test_add_post_column_with_filter() {
		$this->pll_admin->filter_lang = self::$model->get_language( 'en' );
		$list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => 'edit.php' ) );
		$hidden = $list_table->get_column_info()[1];
		$this->assertNotFalse( array_search( 'language_en', $hidden ) );
		$this->assertFalse( array_search( 'language_fr', $hidden ) );
	}

	public function test_add_term_column() {
		set_current_screen( 'edit-tags.php' );
		// We need to call directly the filter "manage_{$screen->id}_columns" due to the static var in get_column_headers()
		$list_table = _get_list_table( 'WP_Terms_List_Table', array( 'screen' => 'edit-tags.php' ) );
		$columns = $list_table->get_column_info()[0];
		$columns = array_intersect_key( $columns, array_flip( array( 'posts' ) ) ); // Keep only the Count column
		$columns = apply_filters( 'manage_edit-post_tag_columns', $columns );
		$columns = array_keys( $columns );
		$en = array_search( 'language_en', $columns );

		$this->assertNotFalse( $en );
		$this->assertEquals( 'language_fr', $columns[ $en + 1 ] );
		$this->assertEquals( 'posts', $columns[ $en + 2 ] );
	}

	public function test_add_term_column_with_filter() {
		set_current_screen( 'edit-tags.php' );
		$this->pll_admin->filter_lang = self::$model->get_language( 'fr' );
		$list_table = _get_list_table( 'WP_Terms_List_Table', array( 'screen' => 'edit-tags.php' ) );
		$hidden = $list_table->get_column_info()[1];
		$this->assertNotFalse( array_search( 'language_fr', $hidden ) );
		$this->assertFalse( array_search( 'language_en', $hidden ) );
	}

	public function test_post_inline_edit() {
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => 'edit.php' ) );
		$list_table->prepare_items();
		$GLOBALS['post'] = $GLOBALS['wp_the_query']->post; // Needed by touch_time

		ob_start();
		$list_table->inline_edit();
		$form = ob_get_clean();
		$doc  = new DomDocument();
		$doc->loadHTML( $form );
		$xpath = new DOMXpath( $doc );

		// Quick Edit
		$tr      = $doc->getElementById( 'inline-edit' );
		$options = $xpath->query( './/select[@name="inline_lang_choice"]/option', $tr );
		$this->assertSame( 2, $options->length, 'Expected the <select> tag to contain 2 <option> tags.' );
		$this->assertSameSets(
			array( 'en', 'fr' ),
			array(
				$options->item( 0 )->getAttribute( 'value' ),
				$options->item( 1 )->getAttribute( 'value' ),
			),
			"'Expected the <select> tag to contain a 'en' and a 'fr' choices."
		);

		// Bulk Edit
		$tr      = $doc->getElementById( 'bulk-edit' );
		$options = $xpath->query( './/select[@name="inline_lang_choice"]/option', $tr );
		$this->assertSame( 3, $options->length, 'Expected the <select> tag to contain 3 <option> tags.' );
		$this->assertSameSets(
			array( '-1', 'en', 'fr' ),
			array(
				$options->item( 0 )->getAttribute( 'value' ),
				$options->item( 1 )->getAttribute( 'value' ),
				$options->item( 2 )->getAttribute( 'value' ),
			),
			"'Expected the <select> tag to contain a '-1', a 'en', and a 'fr' choices."
		);
	}
}
