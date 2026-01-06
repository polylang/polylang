<?php

class Slugs_Test extends PLL_UnitTestCase {

	/**
	 * @var PLL_Context_Admin
	 */
	protected $pll_context;

	/**
	 * @param PLL_UnitTest_Factory $factory
	 * @return void
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 3 );
	}

	public function set_up() {
		parent::set_up();

		$this->pll_context = new PLL_Context_Admin();
	}

	public function test_term_slugs() {
		$term_id = self::factory()->category->create(
			array(
				'name' => 'test',
				'lang' => 'en',
			)
		);

		$_POST['term_lang_choice'] = 'fr';
		$term_id                   = self::factory()->category->create(
			array(
				'name' => 'test',
				'lang' => 'fr',
			)
		);

		$term = get_term( $term_id, 'category' );
		$this->assertSame( 'test-fr', $term->slug );
	}

	public function test_translated_terms_with_parents_sharing_same_name() {
		$en_parent = self::factory()->category->create_and_get(
			array(
				'name' => 'test',
				'lang' => 'en',
			)
		);

		$this->assertInstanceOf( WP_Term::class, $en_parent );
		$this->assertSame( 'test', $en_parent->slug );

		$_POST['term_lang_choice'] = 'en';
		$_POST['parent']           = $en_parent->term_id;
		$en                        = self::factory()->category->create_and_get(
			array(
				'name'   => 'test',
				'parent' => $en_parent->term_id,
				'lang'   => 'en',
			)
		);

		$this->assertInstanceOf( WP_Term::class, $en );
		$this->assertSame( 'test-en', $en->slug );

		// Clean up before creating term in secondary language.
		unset( $_POST );

		$_POST['term_lang_choice'] = 'fr';
		$fr_parent                 = self::factory()->category->create_and_get(
			array(
				'name' => 'test',
				'lang' => 'fr',
			)
		);

		$this->assertInstanceOf( WP_Term::class, $fr_parent );
		$this->assertSame( 'test-fr', $fr_parent->slug );

		$_POST['parent'] = $fr_parent->term_id;
		$fr              = self::factory()->category->create_and_get(
			array(
				'name'   => 'test',
				'parent' => $fr_parent->term_id,
				'lang'   => 'fr',
			)
		);

		$this->assertInstanceOf( WP_Term::class, $fr );
		$this->assertSame( 'test-fr-test-fr', $fr->slug );
	}

	public function test_already_existing_term_slugs_with_parent() {
		$en_parent = self::factory()->category->create_and_get(
			array(
				'name' => 'test',
				'lang' => 'en',
			)
		);

		$this->assertInstanceOf( WP_Term::class, $en_parent );
		$this->assertSame( 'test', $en_parent->slug );

		$_POST['term_lang_choice'] = 'en';
		$_POST['parent']           = $en_parent->term_id;
		$en                        = self::factory()->category->create_and_get(
			array(
				'name'   => 'test',
				'parent' => $en_parent->term_id,
				'lang'   => 'en',
			)
		);

		$this->assertInstanceOf( WP_Term::class, $en );
		$this->assertSame( 'test-en', $en->slug );

		// Let's create another child term with the same parent and the same name.
		$en_new = self::factory()->category->create_and_get(
			array(
				'name'   => 'test',
				'parent' => $en_parent->term_id,
				'lang'   => 'en',
			)
		);

		$this->assertInstanceOf( WP_Error::class, $en_new );
	}

	public function test_update_existing_term_slugs_with_parent() {
		$en_parent = self::factory()->category->create_and_get(
			array(
				'name' => 'test',
				'lang' => 'en',
			)
		);

		$this->assertInstanceOf( WP_Term::class, $en_parent );
		$this->assertSame( 'test', $en_parent->slug );

		$_POST['term_lang_choice'] = 'en';
		$_POST['parent']           = $en_parent->term_id;
		$en                        = self::factory()->category->create_and_get(
			array(
				'name'   => 'test',
				'parent' => $en_parent->term_id,
				'lang'   => 'en',
			)
		);

		$this->assertInstanceOf( WP_Term::class, $en );
		$this->assertSame( 'test-en', $en->slug );

		// Let's update the term.
		wp_update_term( $en->term_id, $en->taxonomy, array( 'name' => 'New Test' ) );
		$en_new = get_term( $en->term_id );

		$this->assertInstanceOf( WP_Term::class, $en_new );
		$this->assertSame( 'New Test', $en_new->name );
		$this->assertSame( 'test-en', $en_new->slug );
	}

	public function test_untranslatable_taxonomy() {
		register_taxonomy( 'test-tax', 'post' ); // Not translatable by default.

		// Filter the language to try to reproduce an error.
		$fr_lang = $this->pll_context->get()->model->get_language( 'fr' );
		add_filter(
			'pll_inserted_term_language',
			function ( $found_language ) use ( $fr_lang ) {
				if ( $found_language instanceof PLL_Language ) {
					return $found_language;
				}

				return $fr_lang;
			}
		);

		// Let's create a term.
		$term = self::factory()->term->create_and_get(
			array(
				'taxonomy' => 'test-tax',
				'name'     => 'test',
			)
		);

		$this->assertInstanceOf( WP_Term::class, $term, 'The term should be created.' );
		$this->assertSame( 'test', $term->name, 'The name is not well created.' );
		$this->assertSame( 'test', $term->slug, 'The slug is not well created.' );

		// Now let's update the term.
		$term_updated = wp_update_term(
			$term->term_id,
			$term->taxonomy,
			array(
				'name' => 'new name',
			)
		);
		$term_updated = get_term( $term_updated['term_id'], $term->taxonomy );

		$this->assertInstanceOf( WP_Term::class, $term_updated, 'The term should still exist.' );
		$this->assertSame( 'new name', $term_updated->name, 'The name should be modified.' );
		$this->assertSame( 'test', $term_updated->slug, 'The slug should remain untouched.' );
	}

	/**
	 * Test should be able to change slug back to original after forced suffix.
	 *
	 * @ticket #2857 {@see https://github.com/polylang/polylang/issues/2857}
	 */
	public function test_can_manually_update_term_slug_removing_suffix() {
		// Create term, change language, gets forced suffix "dog-en".
		$_POST['term_lang_choice'] = 'fr';
		$term_id = self::factory()->category->create(
			array(
				'name' => 'Dog',
				'slug' => 'dog-en', // Simulating the bug where it got suffixed.
				'lang' => 'en',
			)
		);

		$term = get_term( $term_id, 'category' );
		$this->assertSame( 'dog-en', $term->slug );

		// Attempt to change the slug to "dog".
		wp_update_term( $term_id, 'category', array( 'slug' => 'dog' ) );

		$term = get_term( $term_id, 'category' );
		$this->assertSame( 'dog', $term->slug, 'Should be able to change slug from "dog-en" to "dog"' );

		// Change to "dog-2", then back to "dog".
		wp_update_term( $term_id, 'category', array( 'slug' => 'dog-2' ) );
		$term = get_term( $term_id, 'category' );
		$this->assertSame( 'dog-2', $term->slug );

		wp_update_term( $term_id, 'category', array( 'slug' => 'dog' ) );
		$term = get_term( $term_id, 'category' );
		$this->assertSame( 'dog', $term->slug, 'Should be able to change slug from "dog-2" to "dog"' );
	}

	/**
	 * Test creating a translation with same slug adds suffix.
	 */
	public function test_creating_translation_adds_suffix() {
		// Create "Dog" in French
		$_POST['term_lang_choice'] = 'fr';
		$fr_term_id = self::factory()->category->create(
			array(
				'name' => 'Dog',
				'slug' => 'dog',
				'lang' => 'fr',
			)
		);

		$fr_term = get_term( $fr_term_id, 'category' );
		$this->assertSame( 'dog', $fr_term->slug );

		// Create translation in English
		$_POST['term_lang_choice'] = 'en';
		$en_term_id = self::factory()->category->create(
			array(
				'name' => 'Dog',
				'slug' => 'dog',
				'lang' => 'en',
			)
		);

		// Link them as translations
		$this->pll_context->get()->model->term->save_translations( $fr_term_id, compact( 'en_term_id', 'fr_term_id' ) );

		$en_term = get_term( $en_term_id, 'category' );
		$this->assertSame( 'dog-en', $en_term->slug, 'Translation should have suffix' );
	}

	/**
	 * Test creating unrelated term with existing slug adds suffix.
	 */
	public function test_creating_unrelated_term_with_existing_slug_adds_suffix() {
		// Create "Dog" in French
		$_POST['term_lang_choice'] = 'fr';
		self::factory()->category->create(
			array(
				'name' => 'Dog',
				'slug' => 'dog',
				'lang' => 'fr',
			)
		);

		// Create unrelated "Dog" in German (not a translation)
		$_POST['term_lang_choice'] = 'de';
		$de_term_id = self::factory()->category->create(
			array(
				'name' => 'Dog',
				'slug' => 'dog',
				'lang' => 'de',
			)
		);

		$de_term = get_term( $de_term_id, 'category' );
		$this->assertSame( 'dog-de', $de_term->slug, 'Unrelated term should have suffix to avoid conflict' );
	}

	/**
	 * Test editing term keeps its slug.
	 */
	public function test_editing_term_keeps_slug() {
		$_POST['term_lang_choice'] = 'en';
		$term_id = self::factory()->category->create(
			array(
				'name' => 'Dog',
				'slug' => 'dog',
				'lang' => 'en',
			)
		);

		$term = get_term( $term_id, 'category' );
		$this->assertSame( 'dog', $term->slug );

		// Edit name only
		wp_update_term( $term_id, 'category', array( 'name' => 'Chien' ) );

		$term = get_term( $term_id, 'category' );
		$this->assertSame( 'dog', $term->slug, 'Slug should remain unchanged when editing' );
	}

	/**
	 * Test creating term with conflicting slug in same language.
	 */
	public function test_cannot_use_conflicting_slug_in_same_language() {
		// Create first term.
		$_POST['term_lang_choice'] = 'en';
		self::factory()->category->create(
			array(
				'name' => 'Dog',
				'slug' => 'dog',
				'lang' => 'en',
			)
		);

		// Try to create second term with different name but same slug.
		$_POST['term_lang_choice'] = 'en';
		$term2_id = self::factory()->category->create(
			array(
				'name' => 'Doggy', // Different name.
				'slug' => 'dog',   // Same slug.
				'lang' => 'en',
			)
		);

		$this->assertSame( 'dog-2', get_term( $term2_id, 'category' )->slug );
	}
}
