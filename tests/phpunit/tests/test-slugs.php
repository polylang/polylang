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

		$factory->language->create_many( 2 );
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
}
