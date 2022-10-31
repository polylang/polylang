<?php

class Slugs_Test extends PLL_UnitTestCase {
	/**
	 * Used to set our filters and to
	 * manage languages or save translations.
	 *
	 * @var PLL_Admin
	 */
	private $pll_admin;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	public function set_up() {
		parent::set_up();

		$options                       = PLL_Install::get_default_options();
		$options['default_lang']       = 'en'; // Default language is the first one created, see self::wpSetUpBeforeClass().
		$model                         = new PLL_Admin_Model( $options );
		$links_model                   = new PLL_Links_Default( $model );
		$this->pll_admin               = new PLL_Admin( $links_model );
		$this->pll_admin->term         = new PLL_CRUD_Terms( $this->pll_admin ); // Activates our generic term filters.
		$this->pll_admin->filters_term = new PLL_Admin_Filters_Term( $this->pll_admin );  // Activates our filters for admin.
	}

	public function test_term_slugs() {
		$term_id = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		$this->pll_admin->model->term->set_language( $term_id, 'en' );

		$_POST['term_lang_choice'] = 'fr';
		$term_id                   = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		$this->pll_admin->model->term->set_language( $term_id, 'fr' );

		$term = get_term( $term_id, 'category' );
		$this->assertSame( 'test-fr', $term->slug );
	}

	public function test_translated_terms_with_parents_sharing_same_name() {
		$en_parent = self::factory()->term->create_and_get( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		$this->pll_admin->model->term->set_language( $en_parent->term_id, 'en' );

		$this->assertInstanceOf( WP_Term::class, $en_parent );
		$this->assertSame( 'test', $en_parent->slug );

		$_POST['term_lang_choice'] = 'en';
		$_POST['parent']           = $en_parent->term_id;
		$en                        = self::factory()->term->create_and_get( array( 'taxonomy' => 'category', 'name' => 'test', 'parent' => $en_parent->term_id ) );
		$this->pll_admin->model->term->set_language( $en, 'en' );

		$this->assertInstanceOf( WP_Term::class, $en );
		$this->assertSame( 'test-en', $en->slug );

		// Clean up before creating term in secondary language.
		unset( $_POST );

		$_POST['term_lang_choice'] = 'fr';
		$fr_parent                 = self::factory()->term->create_and_get( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		$this->pll_admin->model->term->set_language( $fr_parent->term_id, 'fr' );

		$this->assertInstanceOf( WP_Term::class, $fr_parent );
		$this->assertSame( 'test-fr', $fr_parent->slug );

		$_POST['parent'] = $fr_parent->term_id;
		$fr              = self::factory()->term->create_and_get( array( 'taxonomy' => 'category', 'name' => 'test', 'parent' => $fr_parent->term_id ) );
		$this->pll_admin->model->term->set_language( $fr->term_id, 'fr' );

		$this->assertInstanceOf( WP_Term::class, $fr );
		$this->assertSame( 'test-fr-test-fr', $fr->slug );
	}

	public function test_already_existing_term_slugs_with_parent() {
		$en_parent = self::factory()->term->create_and_get( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		$this->pll_admin->model->term->set_language( $en_parent->term_id, 'en' );

		$this->assertInstanceOf( WP_Term::class, $en_parent );
		$this->assertSame( 'test', $en_parent->slug );

		$_POST['term_lang_choice'] = 'en';
		$_POST['parent']           = $en_parent->term_id;
		$en                        = self::factory()->term->create_and_get( array( 'taxonomy' => 'category', 'name' => 'test', 'parent' => $en_parent->term_id ) );
		$this->pll_admin->model->term->set_language( $en, 'en' );

		$this->assertInstanceOf( WP_Term::class, $en );
		$this->assertSame( 'test-en', $en->slug );

		// Let's create another child term with the same parent and the same name.
		$en_new = self::factory()->term->create_and_get( array( 'taxonomy' => 'category', 'name' => 'test', 'parent' => $en_parent->term_id ) );
		$this->pll_admin->model->term->set_language( $en_new, 'en' );

		$this->assertInstanceOf( WP_Error::class, $en_new );
	}

	public function test_update_existing_term_slugs_with_parent() {
		$en_parent = self::factory()->term->create_and_get( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		$this->pll_admin->model->term->set_language( $en_parent->term_id, 'en' );

		$this->assertInstanceOf( WP_Term::class, $en_parent );
		$this->assertSame( 'test', $en_parent->slug );

		$_POST['term_lang_choice'] = 'en';
		$_POST['parent']           = $en_parent->term_id;
		$en                        = self::factory()->term->create_and_get( array( 'taxonomy' => 'category', 'name' => 'test', 'parent' => $en_parent->term_id ) );
		$this->pll_admin->model->term->set_language( $en, 'en' );

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
		register_taxonomy(
			'test-tax',
			'post',
			array(
				'public' => false, // Not translatable by default.
			)
		);

		// Filter the language to try to reproduce an error.
		$fr_lang = self::$model->get_language( 'fr' );
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
