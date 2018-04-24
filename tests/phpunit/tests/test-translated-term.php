<?php

class Translated_Term_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE_formal' );
	}

	function test_term_language() {
		$term_id = $this->factory->term->create();
		self::$polylang->model->term->set_language( $term_id, 'fr' );

		$this->assertEquals( 'fr', self::$polylang->model->term->get_language( $term_id )->slug );
		$this->assertCount( 2, get_terms( 'term_translations' ) ); // 1 translation group per term + 1 for default categories
	}

	function test_term_translation() {
		$en = $this->factory->term->create();
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create();
		self::$polylang->model->term->set_language( $fr, 'fr' );

		$de = $this->factory->term->create();
		self::$polylang->model->term->set_language( $de, 'de' );

		self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr', 'de' ) );

		$this->assertEquals( self::$polylang->model->term->get_translation( $en, 'en' ), $en );
		$this->assertEquals( self::$polylang->model->term->get_translation( $fr, 'fr' ), $fr );
		$this->assertEquals( self::$polylang->model->term->get_translation( $fr, 'en' ), $en );
		$this->assertEquals( self::$polylang->model->term->get_translation( $en, 'fr' ), $fr );
		$this->assertEquals( self::$polylang->model->term->get_translation( $de, 'fr' ), $fr );
	}

	function test_delete_term_translation() {
		$en = $this->factory->term->create();
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create();
		self::$polylang->model->term->set_language( $fr, 'fr' );

		$de = $this->factory->term->create();
		self::$polylang->model->term->set_language( $de, 'de' );

		self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr', 'de' ) );
		self::$polylang->model->term->delete_translation( $fr );

		$this->assertEquals( self::$polylang->model->term->get_translation( $fr, 'fr' ), $fr );
		$this->assertEquals( self::$polylang->model->term->get_translation( $en, 'de' ), $de );
		$this->assertEquals( self::$polylang->model->term->get_translation( $de, 'en' ), $en );

		$this->assertFalse( self::$polylang->model->term->get_translation( $en, 'fr' ) );
		$this->assertFalse( self::$polylang->model->term->get_translation( $fr, 'en' ) );
		$this->assertFalse( self::$polylang->model->term->get_translation( $fr, 'de' ) );
		$this->assertFalse( self::$polylang->model->term->get_translation( $de, 'fr' ) );
	}
}
