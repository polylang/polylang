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

	public function tear_down() {
		unregister_taxonomy( 'tr_custom_tax' );

		parent::tear_down();
	}

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
	 * @testWith ["category", true, true]
	 *           ["category", false, true]
	 *           ["post_tag", false, true]
	 *           ["tr_custom_tax", false, true]
	 *           ["category", false, false]
	 *           ["category", true, false]
	 *           ["post_tag", false, false]
	 *           ["tr_custom_tax", false, false]
	 *
	 * @param string $taxonomy          Taxonomy name.
	 * @param bool   $with_parent       Whether or not the term has a parent.
	 * @param bool   $with_translations Whether or not the term has translations.
	 * @return void
	 */
	public function test_pll_insert_term_happy_path( $taxonomy, $with_parent, $with_translations ) {
		$languages    = array( 'en', 'fr', 'de' );
		$translations = array();
		foreach ( $languages as $i => $language ) {
			$args            = array();
			$is_default_lang = 0 === $i;

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

			$suffix        = $with_parent || ! $is_default_lang ? "-{$language}" : '';
			$expected_slug = "foo{$suffix}";

			$this->assertSame( $expected_slug, $term->slug );
		}
	}

	/**
	 * @testWith ["category", "en", "term_exists"]
	 *           ["post_tag", "en", "term_exists"]
	 *           ["tr_custom_tax", "en", "term_exists"]
	 *           ["category", "chti", "invalid_language"]
	 *           ["post_tag", "chti", "invalid_language"]
	 *           ["tr_custom_tax", "chti", "invalid_language"]
	 *
	 * @param string $taxonomy   Taxonomy name.
	 * @param string $language   Language slug.
	 * @param string $error_code Error code.
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

	/**
	 * @testWith ["category", true, true, true]
	 *           ["category", false, true, false]
	 *           ["category", true, true, true]
	 *           ["category", false, true, false]
	 *           ["category", false, false, true]
	 *           ["category", true, false, true]
	 *           ["category", false, false, false]
	 *           ["category", true, false, false]
	 *           ["post_tag", false, true, true]
	 *           ["post_tag", false, false, true]
	 *           ["post_tag", false, true, false]
	 *           ["post_tag", false, false, false]
	 *           ["tr_custom_tax", false, true, true]
	 *           ["tr_custom_tax", false, false, true]
	 *           ["tr_custom_tax", false, true, false]
	 *           ["tr_custom_tax", false, false, false]
	 *
	 * @param string $taxonomy          Taxonomy name.
	 * @param bool   $with_parent       Whether or not the term has a parent.
	 * @param bool   $with_language     Whether or not the term language should be updated.
	 * @param bool   $with_translations Whether or not the term translations should be updated.
	 * @return void
	 */
	public function test_pll_update_term_happy_path( $taxonomy, $with_parent, $with_language, $with_translations ) {
		$tr_term_ids = self::factory()->term->create_translated(
			array(
				'name'     => 'The Foo',
				'taxonomy' => $taxonomy,
				'lang'     => 'en',
			),
			array(
				'name'     => 'Le Foo',
				'taxonomy' => $taxonomy,
				'lang'     => 'fr',
			)
		);
		$lonely_term = self::factory()->term->create_and_get( // Used to update translations.
			array(
				'name'     => 'Das Foo',
				'taxonomy' => $taxonomy,
				'lang'     => 'de',
			)
		);
		$spanish = self::factory()->language->create_and_get(
			array(
				'slug'   => 'es',
				'locale' => 'es_ES',
			)
		);

		$en   = get_term( $tr_term_ids['en'] );
		$fr   = get_term( $tr_term_ids['fr'] );
		$args = array();

		if ( $with_parent ) {
			$parent = self::factory()->term->create_and_get(
				array(
					'name'     => 'Le Foo',
					'taxonomy' => $taxonomy,
					'lang'     => 'fr',
				)
			);
			$args['parent'] = $parent->term_id;
		}

		if ( $with_translations ) {
			$args['translations'] = array(
				'de'                                   => $lonely_term->term_id,
				$with_language ? $spanish->slug : 'fr' => $fr->term_id,
			);
		}

		if ( $with_language ) {
			$args['lang'] = $spanish->slug;
		}

		$args['slug'] = $with_translations ? $lonely_term->slug : $en->slug;

		$result = pll_update_term( $fr->term_id, $args );

		$this->assertIsArray( $result, 'The term in French could not be updated.' );
		$this->assertArrayHasKey( 'term_id', $result );
		$this->assertArrayHasKey( 'term_taxonomy_id', $result );

		$updated_term = get_term( $result['term_id'], $taxonomy );
		$term_lang     = $with_language ? $spanish->slug : 'fr';
		$expected_slug = "{$args['slug']}-{$term_lang}";

		$this->assertSame( $expected_slug, $updated_term->slug );

		$expected_language = $with_language ? $spanish->slug : 'fr';

		$this->assertSame( $expected_language, $this->pll_env->model->term->get_language( $updated_term->term_id )->slug, 'The term language is not the right one.' );

		$expected_translations = $with_translations
			? $args['translations']
			: array(
				'en'                                   => $en->term_id,
				$with_language ? $spanish->slug : 'fr' => $fr->term_id,
			);

		$this->assertSameSetsWithIndex( $expected_translations, $this->pll_env->model->term->get_translations( $updated_term->term_id ), "The term doesn't have the right translations." );
	}

	/**
	 * @testWith ["category", "en", "duplicate_term_slug"]
	 *           ["post_tag", "en", "duplicate_term_slug"]
	 *           ["tr_custom_tax", "en", "duplicate_term_slug"]
	 *           ["category", "chti", "invalid_language"]
	 *           ["post_tag", "chti", "invalid_language"]
	 *           ["tr_custom_tax", "chti", "invalid_language"]
	 *
	 * @param string $taxonomy   Taxonomy name.
	 * @param string $language   Language slug.
	 * @param string $error_code Error code.
	 * @return void
	 */
	public function test_pll_update_term_error_path( $taxonomy, $language, $error_code ) {
		$term_ids = self::factory()->term->create_translated(
			array(
				'name'     => 'The Foo',
				'taxonomy' => $taxonomy,
				'lang'     => 'en',
			),
			array(
				'name'     => 'Le Foo',
				'taxonomy' => $taxonomy,
				'lang'     => 'fr',
			)
		);

		$args = array(
			'slug' => 'the-foo',
			'lang' => $language,
		);

		$result = pll_update_term( $term_ids['fr'], $args );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $error_code, $result->get_error_code() );
	}

	/**
	 * @testWith ["page", true, false]
	 *           ["page", false, false]
	 *           ["post", false, false]
	 *           ["page", true, true]
	 *           ["page", false, true]
	 *           ["post", false, true]
	 *
	 * @param string $post_type         The post type.
	 * @param bool   $with_parent       Whether or not the post has a parent.
	 * @param bool   $with_translations Whether or not the post has translations.
	 * @return void
	 */
	public function test_pll_insert_post_happy_path( $post_type, $with_parent, $with_translations ) {
		$languages    = array( 'en', 'fr', 'de' );
		$translations = array();
		foreach ( $languages as $i => $language ) {
			$args            = array();
			$is_default_lang = 0 === $i;

			if ( $with_parent ) {
				$parent = self::factory()->post->create_and_get(
					array(
						'post_title'  => 'Post title',
						'post_status' => 'publish',
						'lang'        => $language,
					)
				);
				$args['post_parent'] = $parent->ID;
			}

			if ( $with_translations && ! empty( $translations ) ) {
				$args['translations'] = $translations;
			}

			$args['post_title']  = 'Post title';
			$args['post_type']   = $post_type;
			$args['post_status'] = 'publish'; // To auto generate post name.

			$result = pll_insert_post( $args, $language );

			$this->assertIsInt( $result, "The post in {$this->pll_env->model->get_language( $language )->name} could not be created." );

			$translations[ $language ] = $result;
			$post                      = get_post( $result );

			$increment_slug = $i + 1;
			$suffix         = ! $is_default_lang ? "-{$increment_slug}" : '';
			if ( $with_parent ) {
				$suffix = '';
			}
			$expected_slug = "post-title{$suffix}";

			$this->assertSame( $expected_slug, $post->post_name );
		}
	}

	/**
	 * @testWith ["post", "chti", "invalid_language"]
	 *           ["page", "chti", "invalid_language"]
	 *
	 * @param string $post_type  Post type.
	 * @param string $language   Language slug.
	 * @param string $error_code Error code.
	 * @return void
	 */
	public function test_pll_insert_post_error_path( $post_type, $language, $error_code ) {
		self::factory()->post->create_and_get(
			array(
				'post_title'  => 'Post title',
				'post_status' => 'publish',
				'lang'        => 'en',
				'post_type'   => $post_type,
			)
		);

		$postarr = array(
			'post_title'  => 'Post title',
			'post_type'   => $post_type,
			'post_status' => 'publish',
		);

		$result = pll_insert_post( $postarr, $language );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $error_code, $result->get_error_code() );
	}

	/**
	 * @testWith ["post", false, false, false]
	 *           ["post", false, true, false]
	 *           ["post", false, true, true]
	 *           ["page", false, false, false]
	 *           ["page", false, true, false]
	 *           ["page", false, true, true]
	 *           ["page", true, true, true]
	 *           ["page", true, false, true]
	 *           ["page", true, false, false]
	 *
	 * @param string $post_type         The post type.
	 * @param bool   $with_parent       Whether or not the post has a parent.
	 * @param bool   $with_language     Whether or not the post language should be updated.
	 * @param bool   $with_translations Whether or not the post has translations.
	 * @return void
	 */
	public function test_pll_update_post_happy_path( $post_type, $with_parent, $with_language, $with_translations ) {
		$tr_post_ids = self::factory()->post->create_translated(
			array(
				'post_title'  => 'Title EN',
				'post_status' => 'publish',
				'post_type'   => $post_type,
				'lang'        => 'en',
			),
			array(
				'post_title'  => 'Title FR',
				'post_status' => 'publish',
				'post_type'   => $post_type,
				'lang'        => 'fr',
			)
		);

		$lonely_post = self::factory()->post->create_and_get( // Used to update translations.
			array(
				'post_title'  => 'Title DE',
				'post_status' => 'publish',
				'post_type'   => $post_type,
				'lang'        => 'de',
			)
		);

		$spanish = self::factory()->language->create_and_get(
			array(
				'slug'   => 'es',
				'locale' => 'es_ES',
			)
		);

		$en = get_post( $tr_post_ids['en'] );
		$fr = get_post( $tr_post_ids['fr'] );

		$args = array();

		if ( $with_parent ) {
			$parent = self::factory()->post->create_and_get(
				array(
					'post_title'  => 'Title FR',
					'post_status' => 'publish',
					'post_type'   => $post_type,
					'lang'        => 'fr',
				)
			);
			$args['post_parent'] = $parent->ID;
		}

		if ( $with_translations ) {
			$args['translations'] = array(
				'de'                                   => $lonely_post->ID,
				$with_language ? $spanish->slug : 'fr' => $fr->ID,
			);
		}

		if ( $with_language ) {
			$args['lang'] = $spanish->slug;
		}

		$args['post_name'] = $with_translations ? $lonely_post->post_name : $en->post_name;

		$args['ID'] = $fr->ID;
		$result = pll_update_post( $args );

		$this->assertIsInt( $result, 'The post in French should have been updated.' );

		$updated_post = get_post( $result );
		$expected_slug = "{$args['post_name']}-2";

		if ( $with_parent && ! $with_language ) {
			$expected_slug = $args['post_name'];
		}

		$this->assertSame( $expected_slug, $updated_post->post_name );

		$expected_language = $with_language ? $spanish->slug : 'fr';

		$this->assertSame( $expected_language, $this->pll_env->model->post->get_language( $updated_post->ID )->slug, 'The post language is not the right one.' );

		$expected_translations = $with_translations
			? $args['translations']
			: array(
				'en'                                   => $en->ID,
				$with_language ? $spanish->slug : 'fr' => $fr->ID,
			);

		$this->assertSameSetsWithIndex( $expected_translations, $this->pll_env->model->post->get_translations( $updated_post->ID ), "The post doesn't have the right translations." );
	}

	/**
	 * @testWith ["post", "chti", "invalid_language"]
	 *           ["page", "chti", "invalid_language"]
	 *
	 * @param string $post_type  Post type.
	 * @param string $language   Language slug.
	 * @param string $error_code Error code.
	 * @return void
	 */
	public function test_pll_update_post_error_path( $post_type, $language, $error_code ) {
		$post_ids = self::factory()->post->create_translated(
			array(
				'post_title'  => 'Post title',
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'lang'        => 'en',
			),
			array(
				'post_title'  => 'Titre de post',
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'lang'        => 'fr',
			)
		);

		$args = array(
			'ID'        => $post_ids['fr'],
			'post_name' => 'post-title',
			'lang'      => $language,
		);

		$result = pll_update_post( $args );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $error_code, $result->get_error_code() );
	}
}
