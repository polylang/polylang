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

	/**
	 * Test creating a translated term with same slug adds suffix.
	 *
	 * When creating a translation of an existing term with the same slug, a language suffix should be added to avoid conflicts.
	 */
	public function test_translated_terms_with_same_slug_get_suffix() {
		// Create "Dog" in French.
		$_POST['term_lang_choice'] = 'fr';
		$fr_term = self::factory()->category->create_and_get(
			array(
				'name' => 'Dog',
				'slug' => 'dog',
				'lang' => 'fr',
			)
		);

		$this->assertSame( 'dog', $fr_term->slug );

		// Create translation in English.
		$_POST['term_lang_choice'] = 'en';
		$en_term = self::factory()->category->create_and_get(
			array(
				'name' => 'Dog',
				'slug' => 'dog',
				'lang' => 'en',
			)
		);

		// Link them as translations.
		self::$model->term->save_translations( $fr_term->term_id, array( 'en' => $en_term->term_id, 'fr' => $fr_term->term_id ) );

		$this->assertSame( 'dog-en', $en_term->slug, 'Translated term should have language suffix' );
	}

	/**
	 * Test creating an unrelated term with same slug adds suffix.
	 *
	 * When creating a new term in a different language (not a translation) with a slug that already exists, a language suffix should be added.
	 */
	public function test_unrelated_terms_with_same_slug_get_suffix() {
		// Create "Dog" in French.
		$_POST['term_lang_choice'] = 'fr';
		self::factory()->category->create(
			array(
				'name' => 'Dog',
				'slug' => 'dog',
				'lang' => 'fr',
			)
		);

		// Create unrelated "Dog" in German (not a translation).
		$_POST['term_lang_choice'] = 'de';
		$de_term = self::factory()->category->create_and_get(
			array(
				'name' => 'Dog',
				'slug' => 'dog',
				'lang' => 'de',
			)
		);

		$this->assertSame( 'dog-de', $de_term->slug, 'Unrelated term should have language suffix to avoid conflict' );
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
	 * Test that changing a term's language via quick edit doesn't add unwanted suffix.
	 *
	 * @ticket #2857 {@see https://github.com/polylang/polylang/issues/2857}
	 */
	public function test_changing_term_language_via_quick_edit_should_not_add_suffix() {
		wp_set_current_user( 1 );

		// Initialize admin specific case for this test only.
		$links_model             = $this->pll_context->get()->model->get_links_model();
		$pll_admin               = new PLL_Admin( $links_model );
		$pll_admin->filters      = new PLL_Admin_Filters( $pll_admin );
		$pll_admin->terms        = new PLL_CRUD_Terms( $pll_admin );
		$pll_admin->filters_term = new PLL_Admin_Filters_Term( $pll_admin );

		// Create category "Dog" in French.
		$term = self::factory()->category->create_and_get(
			array(
				'name' => 'Dog',
				'slug' => 'dog',
				'lang' => 'fr',
			)
		);

		$this->assertSame( 'dog', $term->slug, 'Initial slug should be "dog"' );
		$this->assertSame( 'fr', self::$model->term->get_language( $term->term_id )->slug );

		// Simulate quick edit to change language to English.
		$_POST = array(
			'action'             => 'inline-save-tax',
			'inline_lang_choice' => 'en',
			'_inline_edit'       => wp_create_nonce( 'taxinlineeditnonce' ),
		);
		$_REQUEST = $_POST;

		wp_update_term( $term->term_id, 'category' );

		// Verify language changed and slug remained unchanged.
		$term = get_term( $term->term_id, 'category' );
		$this->assertSame( 'en', self::$model->term->get_language( $term->term_id )->slug, 'Language should be changed to English' );
		$this->assertSame( 'dog', $term->slug, 'Slug should remain "dog" without suffix when changing language' );
	}

	/**
	 * Test that term slug can be changed manually.
	 *
	 * Users should be able to manually change slugs at any time.
	 *
	 * @ticket #2857 {@see https://github.com/polylang/polylang/issues/2857}
	 */
	public function test_term_slug_can_be_changed_manually() {
		$_POST['term_lang_choice'] = 'en';
		$term = self::factory()->category->create_and_get(
			array(
				'name' => 'Dog',
				'slug' => 'dog-en',
				'lang' => 'en',
			)
		);

		$this->assertSame( 'dog-en', $term->slug );

		// Change slug from "dog-en" to "dog".
		wp_update_term( $term->term_id, 'category', array( 'slug' => 'dog' ) );

		$term = get_term( $term->term_id, 'category' );
		$this->assertSame( 'dog', $term->slug, 'Should be able to manually change slug from "dog-en" to "dog"' );

		// Change to "dog-2", then back to "dog".
		wp_update_term( $term->term_id, 'category', array( 'slug' => 'dog-2' ) );
		$term = get_term( $term->term_id, 'category' );
		$this->assertSame( 'dog-2', $term->slug );

		wp_update_term( $term->term_id, 'category', array( 'slug' => 'dog' ) );
		$term = get_term( $term->term_id, 'category' );
		$this->assertSame( 'dog', $term->slug, 'Should be able to change slug from "dog-2" back to "dog"' );
	}

	/**
	 * Test editing term name preserves its slug.
	 *
	 * When editing a term's name, the slug should remain unchanged.
	 */
	public function test_editing_term_name_preserves_slug() {
		$_POST['term_lang_choice'] = 'en';
		$term = self::factory()->category->create_and_get(
			array(
				'name' => 'Dog',
				'slug' => 'dog',
				'lang' => 'en',
			)
		);

		$this->assertSame( 'dog', $term->slug );

		// Edit name only.
		wp_update_term( $term->term_id, 'category', array( 'name' => 'Chien' ) );

		$term = get_term( $term->term_id, 'category' );
		$this->assertSame( 'dog', $term->slug, 'Slug should remain unchanged when editing name' );
	}

	/**
	 * Test creating term with conflicting slug in same language adds WordPress suffix.
	 *
	 * When creating a second term with a slug that already exists in the same language,
	 * WordPress should add its numeric suffix (e.g., "-2").
	 */
	public function test_conflicting_slug_in_same_language_gets_numeric_suffix() {
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
		$term2 = self::factory()->category->create_and_get(
			array(
				'name' => 'Doggy', // Different name.
				'slug' => 'dog',   // Same slug.
				'lang' => 'en',
			)
		);

		$this->assertSame( 'dog-2', $term2->slug, 'WordPress should add numeric suffix for conflict in same language' );
	}
}
