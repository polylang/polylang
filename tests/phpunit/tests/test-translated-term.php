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

	public function test_dont_save_translations_with_incorrect_language() {
		global $wp_object_cache;

		$options                   = array_merge( PLL_Install::get_default_options(), array( 'default_lang' => 'en' ) );
		$model                     = new PLL_Model( $options );
		$translatable_object_cache = new PLL_Translatable_WP_Object_Cache( $wp_object_cache );
		$model->term               = new PLL_Translated_Term( $model, $translatable_object_cache );

		$this->dont_save_translations_with_incorrect_language( $model->term );
	}
}
