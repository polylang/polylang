<?php

class Translated_Term_Test extends PLL_Translated_Object_UnitTestCase {

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
	}

	public function tear_down() {
		parent::tear_down();

		if ( is_multisite() ) {
			restore_current_blog();
		}
	}

	public function test_term_language() {
		$term_id = self::factory()->term->create();
		self::$model->term->set_language( $term_id, 'fr' );

		$this->assertEquals( 'fr', self::$model->term->get_language( $term_id )->slug );
		$this->assertCount( 2, get_terms( array( 'taxonomy' => 'term_translations' ) ) ); // 1 translation group per term + 1 for default categories
	}

	public function test_term_translation() {
		$en = self::factory()->term->create();
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->term->create();
		self::$model->term->set_language( $fr, 'fr' );

		$de = self::factory()->term->create();
		self::$model->term->set_language( $de, 'de' );

		self::$model->term->save_translations( $en, compact( 'en', 'fr', 'de' ) );

		$this->assertSame( $en, self::$model->term->get_translation( $en, 'en' ) );
		$this->assertSame( $fr, self::$model->term->get_translation( $fr, 'fr' ) );
		$this->assertSame( $de, self::$model->term->get_translation( $fr, 'de' ) );

		$this->assertSame( $en, self::$model->term->get_translation( $fr, 'en' ) );
		$this->assertSame( $de, self::$model->term->get_translation( $fr, 'de' ) );

		$this->assertSame( $fr, self::$model->term->get_translation( $en, 'fr' ) );
		$this->assertSame( $de, self::$model->term->get_translation( $en, 'de' ) );

		$this->assertSame( $en, self::$model->term->get_translation( $de, 'en' ) );
		$this->assertSame( $fr, self::$model->term->get_translation( $de, 'fr' ) );
	}

	public function test_delete_term_translation() {
		$en = self::factory()->term->create();
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->term->create();
		self::$model->term->set_language( $fr, 'fr' );

		$de = self::factory()->term->create();
		self::$model->term->set_language( $de, 'de' );

		self::$model->term->save_translations( $en, compact( 'en', 'fr', 'de' ) );
		self::$model->term->delete_translation( $fr );

		$this->assertSame( $fr, self::$model->term->get_translation( $fr, 'fr' ) );
		$this->assertSame( $de, self::$model->term->get_translation( $en, 'de' ) );
		$this->assertSame( $en, self::$model->term->get_translation( $de, 'en' ) );

		$this->assertSame( 0, self::$model->term->get_translation( $en, 'fr' ) );
		$this->assertSame( 0, self::$model->term->get_translation( $fr, 'en' ) );
		$this->assertSame( 0, self::$model->term->get_translation( $fr, 'de' ) );
		$this->assertSame( 0, self::$model->term->get_translation( $de, 'fr' ) );
	}

	public function test_translation_group_after_term_translation_deletion() {
		$en = self::factory()->term->create();
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->term->create();
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$this->assertSame( $fr, self::$model->term->get_translation( $en, 'fr' ) );
		$this->assertSame( $en, self::$model->term->get_translation( $fr, 'en' ) );

		self::$model->term->save_translations( $en, compact( 'en' ) );

		$this->assertSame( 0, self::$model->term->get_translation( $en, 'fr' ), $fr );
		$this->assertSame( 0, self::$model->term->get_translation( $fr, 'en' ), $en );

		$translations_fr = self::$model->term->get_translations( $fr );
		$translation_group_fr = wp_get_object_terms( $translations_fr, 'term_translations' );
		$this->assertNotEmpty( $translation_group_fr );

		$translations_en = self::$model->term->get_translations( $en );
		$translation_group_en = wp_get_object_terms( $translations_en, 'term_translations' );
		$this->assertNotEmpty( $translation_group_en );

		$this->assertNotSame( $translation_group_fr, $translation_group_en );
	}

	public function test_dont_save_translations_with_incorrect_language() {
		$options = self::create_options(
			array(
				'default_lang' => 'en',
			)
		);
		$model = new PLL_Model( $options );

		$this->dont_save_translations_with_incorrect_language( $model->term );
	}

	/**
	 * @ticket #1698 see {https://github.com/polylang/polylang-pro/issues/1698}.
	 * @covers PLL_Translated_Term::get_db_infos()
	 */
	public function test_get_db_infos() {
		$options = self::create_options(
			array(
				'default_lang' => 'en',
			)
		);
		$model = new PLL_Model( $options );

		$ref = new ReflectionMethod( $model->term, 'get_db_infos' );
		$ref->setAccessible( true );
		$db_infos = $ref->invoke( $model->term );

		$this->assertSame( $GLOBALS['wpdb']->term_taxonomy, $db_infos['table'], 'get_db_infos() does not return the right table name.' );

		if ( ! is_multisite() ) {
			return;
		}

		$site_id = self::factory()->blog->create();

		switch_to_blog( $site_id );
		$multi_db_infos = $ref->invoke( $model->term );

		$this->assertSame( $GLOBALS['wpdb']->term_taxonomy, $multi_db_infos['table'], 'get_db_infos() does not return the right table name.' );
		$this->assertNotSame( $db_infos['table'], $multi_db_infos['table'], 'The table name should be different between blogs.' );
	}

	/**
	 * Checks that the translations group are correctly updated when linking several translations together.
	 *
	 * @ticket #2717 see {https://github.com/polylang/polylang-pro/issues/2717}.
	 */
	public function test_save_translations() {
		$terms = self::factory()->term->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' ),
			array( 'lang' => 'de' )
		);

		$translations_terms = wp_get_object_terms( $terms, 'term_translations' );
		$this->assertCount( 1, $translations_terms );

		$this->assertSame( $terms['en'], self::$model->term->get_translation( $terms['en'], 'en' ) );
		$this->assertSame( $terms['fr'], self::$model->term->get_translation( $terms['fr'], 'fr' ) );
		$this->assertSame( $terms['de'], self::$model->term->get_translation( $terms['de'], 'de' ) );

		$this->assertSame( $terms['fr'], self::$model->term->get_translation( $terms['en'], 'fr' ) );
		$this->assertSame( $terms['de'], self::$model->term->get_translation( $terms['en'], 'de' ) );

		$this->assertSame( $terms['en'], self::$model->term->get_translation( $terms['fr'], 'en' ) );
		$this->assertSame( $terms['de'], self::$model->term->get_translation( $terms['fr'], 'de' ) );

		$this->assertSame( $terms['fr'], self::$model->term->get_translation( $terms['de'], 'fr' ) );
		$this->assertSame( $terms['en'], self::$model->term->get_translation( $terms['de'], 'en' ) );

		// Removes the translations from the group by updating the German term.
		self::$model->term->save_translations( $terms['de'], array() );

		$translations_terms = wp_get_object_terms( $terms, 'term_translations' );

		$this->assertCount( 3, $terms );

		$this->assertSame( 0, self::$model->term->get_translation( $terms['en'], 'fr' ) );
		$this->assertSame( 0, self::$model->term->get_translation( $terms['en'], 'de' ) );

		$this->assertSame( 0, self::$model->term->get_translation( $terms['fr'], 'en' ) );
		$this->assertSame( 0, self::$model->term->get_translation( $terms['fr'], 'de' ) );

		$this->assertSame( 0, self::$model->term->get_translation( $terms['de'], 'fr' ) );
		$this->assertSame( 0, self::$model->term->get_translation( $terms['de'], 'en' ) );

		// Links again the French and English terms.
		self::$model->term->save_translations( $terms['fr'], array( 'fr' => $terms['fr'], 'en' => $terms['en'] ) );

		$translations_terms = wp_get_object_terms( $terms, 'term_translations' );
		$this->assertCount( 2, $translations_terms ); // Is correct at this step because the German term isn't translated into either English or French.

		// Links again the German and English terms but not with the French one.
		self::$model->term->save_translations( $terms['de'], array( 'de' => $terms['de'], 'en' => $terms['en'] ) );

		$translations_terms = wp_get_object_terms( $terms, 'term_translations' );
		$this->assertCount( 2, $translations_terms ); // Is correct because each term has a translations group even if it isn't translated.

		$this->assertSame( $terms['en'], self::$model->term->get_translation( $terms['de'], 'en' ) );
		$this->assertSame( $terms['de'], self::$model->term->get_translation( $terms['en'], 'de' ) );

		// The French term is no longer in the translations group.
		$this->assertSame( 0, self::$model->term->get_translation( $terms['fr'], 'en' ) );
		$this->assertSame( 0, self::$model->term->get_translation( $terms['fr'], 'de' ) );
	}

	/**
	 * Checks that updating a term translations group is done only once when we unlink all translations.
	 */
	public function test_should_not_update_translations_group_when_removing_all_translations() {
		$terms = self::factory()->term->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);

		$saved_term_count = did_action( 'saved_term_translations' );

		$translations_terms = wp_get_object_terms( $terms, 'term_translations' );
		$this->assertCount( 1, $translations_terms );

		// Removes the translations from the group by updating the English term.
		self::$model->term->save_translations( $terms['en'], array() );

		/**
		 * Checks we updated translations group only once when removing all the translations.
		 * Because removing a term translations group creates a new one for the term being removed,
		 * see PLL_Translated_Term::delete_translation(): https://github.com/polylang/polylang/blob/3.7.3/include/translated-term.php#L180,
		 * `saved_term_translations` action is triggered twice.
		 */
		$this->assertSame( 2, did_action( 'saved_term_translations' ) - $saved_term_count );

		$this->assertSame( self::$model->post->get_translation( $terms['en'], 'fr' ), 0 );
		$this->assertSame( self::$model->post->get_translation( $terms['fr'], 'en' ), 0 );
	}
}
