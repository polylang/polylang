<?php

/**
 * Test class for Polylang public API.
 */
class API_Test extends PLL_UnitTestCase {
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		$factory->language->create_many( 3 );
	}

	public function set_up() {
		parent::set_up();

		$this->pll_env       = ( new PLL_Context_Frontend() )->get();
		$GLOBALS['polylang'] = $this->pll_env;
	}

	/**
	 * @covers ::pll_get_post
	 */
	public function test_pll_get_post() {
		$posts = $this->factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);
		$this->pll_env->curlang = $this->pll_env->model->get_language( 'fr' );

		$this->assertSame( $posts['en'], pll_get_post( $posts['en'], 'en' ) );
		$this->assertSame( $posts['fr'], pll_get_post( $posts['en'], 'fr' ) );
		$this->assertSame( 0, pll_get_post( $posts['en'], 'chti' ) );
		$this->assertSame( 0, pll_get_post( $posts['en'], 'de' ) );
		$this->assertSame( $posts['fr'], pll_get_post( $posts['en'] ) );
	}

	/**
	 * @covers ::pll_get_term
	 */
	public function test_pll_get_term() {
		$terms = $this->factory()->term->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);
		$this->pll_env->curlang = $this->pll_env->model->get_language( 'fr' );

		$this->assertSame( $terms['en'], pll_get_term( $terms['en'], 'en' ) );
		$this->assertSame( $terms['fr'], pll_get_term( $terms['en'], 'fr' ) );
		$this->assertSame( 0, pll_get_term( $terms['en'], 'chti' ) );
		$this->assertSame( 0, pll_get_term( $terms['en'], 'de' ) );
		$this->assertSame( $terms['fr'], pll_get_term( $terms['en'] ) );
	}

	/**
	 * @testWith ["post"]
	 *           ["term"]
	 *
	 * @covers PLL_Translated_Object::get_translation
	 *
	 * @param string $type Type of object.
	 */
	public function test_translated_objects_get_translation( $type ) {
		$objects = $this->factory()->$type->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);

		$this->assertSame( $objects['fr'], PLL()->model->$type->get_translation( $objects['en'], 'fr' ) );
		$this->assertSame( 0, PLL()->model->$type->get_translation( $objects['en'], 'de' ) );
		$this->assertSame( 0, PLL()->model->$type->get_translation( $objects['en'], 'chti' ) );
	}
}
