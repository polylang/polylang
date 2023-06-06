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

		$this->assertEquals( self::$model->term->get_translation( $en, 'en' ), $en );
		$this->assertEquals( self::$model->term->get_translation( $fr, 'fr' ), $fr );
		$this->assertEquals( self::$model->term->get_translation( $fr, 'en' ), $en );
		$this->assertEquals( self::$model->term->get_translation( $en, 'fr' ), $fr );
		$this->assertEquals( self::$model->term->get_translation( $de, 'fr' ), $fr );
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

		$this->assertEquals( self::$model->term->get_translation( $fr, 'fr' ), $fr );
		$this->assertEquals( self::$model->term->get_translation( $en, 'de' ), $de );
		$this->assertEquals( self::$model->term->get_translation( $de, 'en' ), $en );

		$this->assertFalse( self::$model->term->get_translation( $en, 'fr' ) );
		$this->assertFalse( self::$model->term->get_translation( $fr, 'en' ) );
		$this->assertFalse( self::$model->term->get_translation( $fr, 'de' ) );
		$this->assertFalse( self::$model->term->get_translation( $de, 'fr' ) );
	}

	public function test_translation_group_after_term_translation_deletion() {
		$en = self::factory()->term->create();
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->term->create();
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$this->assertEquals( self::$model->term->get_translation( $en, 'fr' ), $fr );
		$this->assertEquals( self::$model->term->get_translation( $fr, 'en' ), $en );

		self::$model->term->save_translations( $en, compact( 'en' ) );

		$this->assertFalse( self::$model->term->get_translation( $en, 'fr' ), $fr );
		$this->assertFalse( self::$model->term->get_translation( $fr, 'en' ), $en );

		$translations_fr = self::$model->term->get_translations( $fr );
		$translation_group_fr = wp_get_object_terms( $translations_fr, 'term_translations' );
		$this->assertNotEmpty( $translation_group_fr );

		$translations_en = self::$model->term->get_translations( $en );
		$translation_group_en = wp_get_object_terms( $translations_en, 'term_translations' );
		$this->assertNotEmpty( $translation_group_en );

		$this->assertNotSame( $translation_group_fr, $translation_group_en );
	}

	public function test_dont_save_translations_with_incorrect_language() {
		$options = array_merge( PLL_Install::get_default_options(), array( 'default_lang' => 'en' ) );
		$model = new PLL_Model( $options );
		$model->term = new PLL_Translated_Term( $model );

		$this->dont_save_translations_with_incorrect_language( $model->term );
	}

	/**
	 * @ticket #1698 see {https://github.com/polylang/polylang-pro/issues/1698}.
	 * @covers PLL_Translated_Term::get_db_infos()
	 */
	public function test_get_db_infos() {
		$options = array_merge( PLL_Install::get_default_options(), array( 'default_lang' => 'en' ) );
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
}
