<?php

class Term_Translations_Group_Test extends PLL_Translated_Object_UnitTestCase {

	/**
	 * @param PLL_UnitTest_Factory $factory
	 * @return void
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
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

		$this->assertSame( $terms['en'], self::$model->term->get_translation( $terms['en'], 'en' ) );
		$this->assertSame( $terms['fr'], self::$model->term->get_translation( $terms['fr'], 'fr' ) );

		$this->assertSame( $terms['fr'], self::$model->term->get_translation( $terms['en'], 'fr' ) );

		$this->assertSame( $terms['en'], self::$model->term->get_translation( $terms['fr'], 'en' ) );

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
