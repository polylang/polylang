<?php

/**
 * Note: Some tests require posts or terms without language.
 * As Polylang automatically assigns the default language to all objects,
 * we need to instantiate `PLL_Context_Admin` after these objects are created.
 * This prevents us to do it in `set_up()`.
 */
class Columns_Test extends PLL_UnitTestCase {
	protected static $editor;
	protected static $translator_fr;
	protected static $translator_es;

	/**
	 * @param PLL_UnitTest_Factory $factory
	 * @return void
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );

		self::$editor        = self::factory()->user->create_and_get( array( 'role' => 'editor' ) );
		self::$translator_fr = self::factory()->user->create_and_get( array( 'role' => 'editor' ) );
		self::$translator_es = self::factory()->user->create_and_get( array( 'role' => 'editor' ) );
		self::$translator_fr->add_cap( 'translate_fr' );
		self::$translator_es->add_cap( 'translate_es' );
	}

	public static function wpTearDownAfterClass() {
		self::delete_user( self::$editor->ID );
		self::delete_user( self::$translator_fr->ID );
		self::delete_user( self::$translator_es->ID );
		parent::wpTearDownAfterClass();
	}

	public function set_up() {
		parent::set_up();

		// Sets a user to pass current_user_can tests.
		wp_set_current_user( self::$editor->ID );
	}

	/**
	 * This must be the first test due to the static variable in get_culumn_headers().
	 */
	public function test_no_screen_options_in_term_screen() {
		new PLL_Context_Admin();
		set_current_screen( 'term.php' );
		$this->assertEmpty( get_column_headers( get_current_screen() ) );
	}

	public function test_post_with_no_language() {
		$post_id = self::factory()->post->create();

		$pll_admin = ( new PLL_Context_Admin() )->get();

		ob_start();
		$pll_admin->filters_columns->post_column( 'language_en', $post_id );
		$pll_admin->filters_columns->post_column( 'language_fr', $post_id );
		$this->assertEmpty( ob_get_clean() );
	}

	/**
	 * @testWith [""]
	 *           ["translator_fr"]
	 *           ["translator_es"]
	 *
	 * @param string $user The user to define as current.
	 * @return void
	 */
	public function test_post_language( string $user ) {
		$en = self::factory()->post->create( array( 'lang' => 'en' ) );

		if ( ! empty( $user ) ) {
			wp_set_current_user( self::$$user->ID );
		}

		$pll_admin = ( new PLL_Context_Admin() )->get();

		ob_start();
		$pll_admin->filters_columns->post_column( 'language_en', $en );
		$column = ob_get_clean();

		$this->assertNotFalse( strpos( $column, 'pll_column_flag' ) );

		if ( empty( $user ) ) {
			$this->assertNotFalse( strpos( $column, 'href="' ), 'A non-translator should be able to edit a post.' );
		} elseif ( 'translator_fr' === $user ) {
			$this->assertFalse( strpos( $column, 'href="' ), 'A French translator should not be able to edit a post in English.' );
			$this->assertNotFalse( strpos( $column, 'wp-ui-text-icon' ), 'The UI should appear as disabled.' );
		} elseif ( 'translator_es' === $user ) {
			$this->assertFalse( strpos( $column, 'href="' ), 'A Spanish translator should not be able to edit a post in English.' );
			$this->assertNotFalse( strpos( $column, 'wp-ui-text-icon' ), 'The UI should appear as disabled.' );
		}
	}

	/**
	 * @testWith [""]
	 *           ["translator_fr"]
	 *           ["translator_es"]
	 *
	 * @param string $user The user to define as current.
	 * @return void
	 */
	public function test_untranslated_post( string $user ) {
		$en = self::factory()->post->create( array( 'lang' => 'en' ) );

		if ( ! empty( $user ) ) {
			wp_set_current_user( self::$$user->ID );
		}

		$pll_admin = ( new PLL_Context_Admin() )->get();

		ob_start();
		$pll_admin->filters_columns->post_column( 'language_fr', $en );
		$column = ob_get_clean();

		$this->assertNotFalse( strpos( $column, 'pll_icon_add' ) );

		if ( empty( $user ) ) {
			$this->assertNotFalse( strpos( $column, 'href="' ), 'A non-translator should be able to create a translation in French.' );
			$this->assertNotFalse( strpos( $column, 'from_post' ), 'The URL allowing to create a translation should contain the `from_post` query arg.' );
		} elseif ( 'translator_fr' === $user ) {
			$this->assertNotFalse( strpos( $column, 'href="' ), 'A French translator should be able to create a translation in French.' );
			$this->assertNotFalse( strpos( $column, 'from_post' ), 'The URL allowing to create a translation should contain the `from_post` query arg.' );
		} elseif ( 'translator_es' === $user ) {
			$this->assertFalse( strpos( $column, 'href="' ), 'A Spanish translator should not be able to create a translation in French.' );
			$this->assertNotFalse( strpos( $column, 'wp-ui-text-icon' ), 'The UI should appear as disabled.' );
		}
	}

	/**
	 * Special case for media.
	 *
	 * @testWith [""]
	 *           ["translator_fr"]
	 *           ["translator_es"]
	 *
	 * @param string $user The user to define as current.
	 * @return void
	 */
	public function test_untranslated_media( string $user ) {
		$en = self::factory()->attachment->create_object( 'image.jpg' );
		self::$model->post->set_language( $en, 'en' );

		if ( ! empty( $user ) ) {
			wp_set_current_user( self::$$user->ID );
		}

		$pll_admin = ( new PLL_Context_Admin() )->get();

		ob_start();
		$pll_admin->filters_columns->post_column( 'language_fr', $en );
		$column = ob_get_clean();

		$this->assertNotFalse( strpos( $column, 'pll_icon_add' ) );

		if ( empty( $user ) ) {
			$this->assertNotFalse( strpos( $column, 'href="' ), 'A non-translator should be able to create a translation in French.' );
			$this->assertNotFalse( strpos( $column, 'from_media' ), 'The URL allowing to create a translation should contain the `from_media` query arg.' );
		} elseif ( 'translator_fr' === $user ) {
			$this->assertNotFalse( strpos( $column, 'href="' ), 'A French translator should be able to create a translation in French.' );
			$this->assertNotFalse( strpos( $column, 'from_media' ), 'The URL allowing to create a translation should contain the `from_media` query arg.' );
		} elseif ( 'translator_es' === $user ) {
			$this->assertFalse( strpos( $column, 'href="' ), 'A Spanish translator should not be able to create a translation in French.' );
			$this->assertNotFalse( strpos( $column, 'wp-ui-text-icon' ), 'The UI should appear as disabled.' );
		}
	}

	/**
	 * @testWith [""]
	 *           ["translator_fr"]
	 *           ["translator_es"]
	 *
	 * @param string $user The user to define as current.
	 * @return void
	 */
	public function test_translated_post( string $user ) {
		$posts = self::factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);

		if ( ! empty( $user ) ) {
			wp_set_current_user( self::$$user->ID );
		}

		$pll_admin = ( new PLL_Context_Admin() )->get();

		ob_start();
		$pll_admin->filters_columns->post_column( 'language_fr', $posts['en'] );
		$column = ob_get_clean();

		$this->assertNotFalse( strpos( $column, 'pll_icon_edit' ) );

		if ( empty( $user ) ) {
			$this->assertNotFalse( strpos( $column, 'href="' ), 'A non-translator should be able to edit a translation.' );
		} elseif ( 'translator_fr' === $user ) {
			$this->assertNotFalse( strpos( $column, 'href="' ), 'A French translator should be able to edit a translation.' );
		} elseif ( 'translator_es' === $user ) {
			$this->assertFalse( strpos( $column, 'href="' ), 'A Spanish translator should not be able to edit a translation in English.' );
			$this->assertNotFalse( strpos( $column, 'wp-ui-text-icon' ), 'The UI should appear as disabled.' );
		}
	}

	public function test_term_with_no_language() {
		$GLOBALS['post_type'] = 'post';
		$GLOBALS['taxonomy'] = 'category';

		$term_id = self::factory()->category->create();

		$pll_admin = ( new PLL_Context_Admin() )->get();

		$column_en = $pll_admin->filters_columns->term_column( '', 'language_en', $term_id );
		$column_fr = $pll_admin->filters_columns->term_column( '', 'language_fr', $term_id );
		$this->assertEmpty( $column_en );
		$this->assertEmpty( $column_fr );
	}

	/**
	 * @testWith [""]
	 *           ["translator_fr"]
	 *           ["translator_es"]
	 *
	 * @param string $user The user to define as current.
	 * @return void
	 */
	public function test_term_language( string $user ) {
		$GLOBALS['post_type'] = 'post';
		$GLOBALS['taxonomy'] = 'category';

		$en = self::factory()->category->create( array( 'lang' => 'en' ) );

		if ( ! empty( $user ) ) {
			wp_set_current_user( self::$$user->ID );
		}

		$pll_admin = ( new PLL_Context_Admin() )->get();
		$column    = $pll_admin->filters_columns->term_column( '', 'language_en', $en );

		$this->assertNotFalse( strpos( $column, 'pll_column_flag' ) );

		if ( empty( $user ) ) {
			$this->assertNotFalse( strpos( $column, 'href="' ), 'A non-translator should be able to edit a term.' );
		} elseif ( 'translator_fr' === $user ) {
			$this->assertFalse( strpos( $column, 'href="' ), 'A French translator should not be able to edit a term in English.' );
			$this->assertNotFalse( strpos( $column, 'wp-ui-text-icon' ), 'The UI should appear as disabled.' );
		} elseif ( 'translator_es' === $user ) {
			$this->assertFalse( strpos( $column, 'href="' ), 'A Spanish translator should not be able to edit a term in English.' );
			$this->assertNotFalse( strpos( $column, 'wp-ui-text-icon' ), 'The UI should appear as disabled.' );
		}
	}

	/**
	 * @testWith [""]
	 *           ["translator_fr"]
	 *           ["translator_es"]
	 *
	 * @param string $user The user to define as current.
	 * @return void
	 */
	public function test_untranslated_term( string $user ) {
		$GLOBALS['post_type'] = 'post';
		$GLOBALS['taxonomy'] = 'category';

		$en = self::factory()->category->create( array( 'lang' => 'en' ) );

		if ( ! empty( $user ) ) {
			wp_set_current_user( self::$$user->ID );
		}

		$pll_admin = ( new PLL_Context_Admin() )->get();
		$column    = $pll_admin->filters_columns->term_column( '', 'language_fr', $en );

		$this->assertNotFalse( strpos( $column, 'pll_icon_add' ) );

		if ( empty( $user ) ) {
			$this->assertNotFalse( strpos( $column, 'href="' ), 'A non-translator should be able to create a translation in French.' );
			$this->assertNotFalse( strpos( $column, 'from_tag' ), 'The URL allowing to create a translation should contain the `from_tag` query arg.' );
		} elseif ( 'translator_fr' === $user ) {
			$this->assertNotFalse( strpos( $column, 'href="' ), 'A French translator should be able to create a translation in French.' );
			$this->assertNotFalse( strpos( $column, 'from_tag' ), 'The URL allowing to create a translation should contain the `from_tag` query arg.' );
		} elseif ( 'translator_es' === $user ) {
			$this->assertFalse( strpos( $column, 'href="' ), 'A Spanish translator should not be able to create a translation in French.' );
			$this->assertNotFalse( strpos( $column, 'wp-ui-text-icon' ), 'The UI should appear as disabled.' );
		}
	}

	/**
	 * @testWith [""]
	 *           ["translator_fr"]
	 *           ["translator_es"]
	 *
	 * @param string $user The user to define as current.
	 * @return void
	 */
	public function test_translated_term( string $user ) {
		$GLOBALS['post_type'] = 'post';
		$GLOBALS['taxonomy'] = 'category';

		$cats = self::factory()->category->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);

		if ( ! empty( $user ) ) {
			wp_set_current_user( self::$$user->ID );
		}

		$pll_admin = ( new PLL_Context_Admin() )->get();
		$column    = $pll_admin->filters_columns->term_column( '', 'language_fr', $cats['en'] );

		$this->assertNotFalse( strpos( $column, 'pll_icon_edit' ) );

		if ( empty( $user ) ) {
			$this->assertNotFalse( strpos( $column, 'href="' ), 'A non-translator should be able to edit a translation.' );
		} elseif ( 'translator_fr' === $user ) {
			$this->assertNotFalse( strpos( $column, 'href="' ), 'A French translator should be able to edit a translation.' );
		} elseif ( 'translator_es' === $user ) {
			$this->assertFalse( strpos( $column, 'href="' ), 'A Spanish translator should not be able to edit a translation in English.' );
			$this->assertNotFalse( strpos( $column, 'wp-ui-text-icon' ), 'The UI should appear as disabled.' );
		}
	}

	public function test_add_post_column() {
		new PLL_Context_Admin();

		// We need to call directly the filter "manage_{$screen->id}_columns" due to the static var in get_column_headers().
		$list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => 'edit.php' ) );
		$columns = $list_table->get_column_info()[0];
		$columns = array_intersect_key( $columns, array_flip( array( 'comments' ) ) ); // Keep only the Comments column.
		$columns = apply_filters( 'manage_edit-post_columns', $columns );
		$columns = array_keys( $columns );
		$en = array_search( 'language_en', $columns );

		$this->assertNotFalse( $en );
		$this->assertEquals( 'language_fr', $columns[ $en + 1 ] );
		$this->assertEquals( 'comments', $columns[ $en + 2 ] );
	}

	public function test_add_post_column_with_filter() {
		$pll_admin = ( new PLL_Context_Admin() )->get();
		$pll_admin->filter_lang = $pll_admin->model->get_language( 'en' );

		$list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => 'edit.php' ) );
		$hidden = $list_table->get_column_info()[1];
		$this->assertNotFalse( array_search( 'language_en', $hidden ) );
		$this->assertFalse( array_search( 'language_fr', $hidden ) );
	}

	public function test_add_term_column() {
		new PLL_Context_Admin();
		set_current_screen( 'edit-tags.php' );

		// We need to call directly the filter "manage_{$screen->id}_columns" due to the static var in get_column_headers().
		$list_table = _get_list_table( 'WP_Terms_List_Table', array( 'screen' => 'edit-tags.php' ) );
		$columns = $list_table->get_column_info()[0];
		$columns = array_intersect_key( $columns, array_flip( array( 'posts' ) ) ); // Keep only the Count column.
		$columns = apply_filters( 'manage_edit-post_tag_columns', $columns );
		$columns = array_keys( $columns );
		$en = array_search( 'language_en', $columns );

		$this->assertNotFalse( $en );
		$this->assertEquals( 'language_fr', $columns[ $en + 1 ] );
		$this->assertEquals( 'posts', $columns[ $en + 2 ] );
	}

	public function test_add_term_column_with_filter() {
		$pll_admin = ( new PLL_Context_Admin() )->get();
		$pll_admin->filter_lang = self::$model->get_language( 'fr' );
		set_current_screen( 'edit-tags.php' );

		$list_table = _get_list_table( 'WP_Terms_List_Table', array( 'screen' => 'edit-tags.php' ) );
		$hidden = $list_table->get_column_info()[1];
		$this->assertNotFalse( array_search( 'language_fr', $hidden ) );
		$this->assertFalse( array_search( 'language_en', $hidden ) );
	}

	public function test_post_inline_edit() {
		self::factory()->post->create( array( 'lang' => 'en' ) );

		new PLL_Context_Admin();

		$list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => 'edit.php' ) );
		$list_table->prepare_items();
		$GLOBALS['post'] = $GLOBALS['wp_the_query']->post; // Needed by touch_time.

		ob_start();
		$list_table->inline_edit();
		$form = ob_get_clean();
		$doc  = new DomDocument();
		$doc->loadHTML( $form );
		$xpath = new DOMXpath( $doc );

		// Quick Edit.
		$tr      = $doc->getElementById( 'inline-edit' );
		$options = $xpath->query( './/select[@name="inline_lang_choice"]/option', $tr );
		$this->assertSame( 2, $options->length, 'Expected the <select> tag to contain 2 <option> tags.' );
		$this->assertSameSets(
			array( 'en', 'fr' ),
			array(
				$options->item( 0 )->getAttribute( 'value' ),
				$options->item( 1 )->getAttribute( 'value' ),
			),
			"Expected the <select> tag to contain a 'en' and a 'fr' choices."
		);

		// Bulk Edit.
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
			"Expected the <select> tag to contain a '-1', a 'en', and a 'fr' choices."
		);
	}
}
