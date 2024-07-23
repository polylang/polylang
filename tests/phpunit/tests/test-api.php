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

		register_taxonomy( 'tr_custom_tax', 'post' );

		$options             = array(
			'default_lang' => 'en',
			'taxonomies'   => array( 'tr_custom_tax' ),
		);
		$this->pll_env       = ( new PLL_Context_Frontend( array( 'options' => $options ) ) )->get();
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

	/**
	 * @covers ::pll_insert_term
	 *
	 * @testWith ["category", true, true]
	 *           ["category", false, true]
	 *           ["post_tag", false, true]
	 *           ["tr_custom_tax", false, true]
	 *           ["category", false, false]
	 *           ["category", true, false]
	 *           ["post_tag", false, false]
	 *           ["tr_custom_tax", false, false]
	 *
	 * @param string $taxonomy
	 * @return void
	 */
	public function test_pll_insert_term_happy_path( $taxonomy, $with_parent, $with_translations ) {
		$languages    = array( 'en', 'fr', 'de' );
		$translations = array();
		foreach ( $languages as $i => $language ) {
			$args            = array();
			$is_default_lang = $i === 0;

			if ( $with_parent ) {
				$parent = self::factory()->term->create_and_get(
					array(
						'name'     => $is_default_lang ? 'Foo' : "Foo {$language}",
						'taxonomy' => $taxonomy,
						'lang'     => $language,
					)
				);
				$args['parent'] = $parent->term_id;
			}

			if ( $with_translations && ! empty( $translations ) ) {
				$args['translations'] = $translations;
			}

			$result = pll_insert_term( 'Foo', $taxonomy, $language, $args );

			$this->assertIsArray( $result, "The term in {$this->pll_env->model->get_language( $language )->name} could not be created." );
			$this->assertArrayHasKey( 'term_id', $result );
			$this->assertArrayHasKey( 'term_taxonomy_id', $result );

			$translations[ $language ] = $result['term_id'];
			$term                      = get_term( $result['term_id'], $taxonomy );

			/*
			 * @FIXME Polylang doesn't reuse parent slug and add a suffix with language slug instead...
			 */
			$suffix        = $with_parent || ! $is_default_lang ? "-{$language}" : '';
			$expected_slug = "foo{$suffix}";

			$this->assertSame( $expected_slug, $term->slug );
		}
	}

	/**
	 * @covers ::pll_insert_term
	 *
	 * @testWith ["category", "en", "term_exists"]
	 *           ["post_tag", "en", "term_exists"]
	 *           ["tr_custom_tax", "en", "term_exists"]
	 *           ["category", "chti", "invalid_language"]
	 *           ["post_tag", "chti", "invalid_language"]
	 *           ["tr_custom_tax", "chti", "invalid_language"]
	 *
	 * @param string $taxonomy
	 * @return void
	 */
	public function test_pll_insert_term_error_path( $taxonomy, $language, $error_code ) {
		self::factory()->term->create_and_get(
			array(
				'name'     => 'Foo',
				'taxonomy' => $taxonomy,
				'lang'     => 'en',
			)
		);

		$result = pll_insert_term( 'Foo', $taxonomy, $language );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $error_code, $result->get_error_code() );
	}
}
