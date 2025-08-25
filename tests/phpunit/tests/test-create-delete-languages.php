<?php

class Create_Delete_Languages_Test extends PLL_UnitTestCase {

	public function set_up() {
		parent::set_up();

		$this->pll_env = ( new PLL_Context_Settings() )->get();
	}

	public function test_add_and_delete_language_have_consistent_behavior() {
		// First language.
		$args = array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 2,
		);

		$this->assertTrue( $this->pll_env->model->add_language( $args ) );

		$lang = $this->pll_env->model->get_language( 'en' );

		$this->assertEquals( 'English', $lang->name );
		$this->assertEquals( 'en', $lang->slug );
		$this->assertEquals( 'en_US', $lang->locale );
		$this->assertEquals( 0, $lang->is_rtl );
		$this->assertEquals( 2, $lang->term_group );

		// Check default language.
		$this->assertEquals( 'en', $this->pll_env->model->options['default_lang'] );

		// Second language (rtl).
		$args = array(
			'name'       => 'العربية',
			'slug'       => 'ar',
			'locale'     => 'ar',
			'rtl'        => 1,
			'flag'       => 'arab',
			'term_group' => 1,
		);

		$this->assertTrue( $this->pll_env->model->add_language( $args ) );

		$lang = $this->pll_env->model->get_language( 'ar' );

		$this->assertEquals( 'العربية', $lang->name );
		$this->assertEquals( 'ar', $lang->slug );
		$this->assertEquals( 'ar', $lang->locale );
		$this->assertEquals( 1, $lang->is_rtl );
		$this->assertEquals( 1, $lang->term_group );

		// Check default language.
		$this->assertEquals( 'en', $this->pll_env->model->options['default_lang'] );

		// Check language order.
		$this->assertEqualSetsWithIndex( array( 'ar', 'en' ), $this->pll_env->model->get_languages_list( array( 'fields' => 'slug' ) ) );

		// Attempt to create a language with the same slug as an existing one.
		$this->pll_env->model->add_language( array( 'slug' => 'en', 'locale' => 'en_GB' ) );
		$lang = $this->pll_env->model->get_language( 'en' );
		$this->assertEquals( 'en_US', $lang->locale );
		$this->assertFalse( $this->pll_env->model->get_language( 'en_GB' ) );
		$this->assertEquals( 2, count( $this->pll_env->model->get_languages_list() ) );

		// Delete 1 language.
		$lang = $this->pll_env->model->get_language( 'en_US' );
		$this->pll_env->model->delete_language( $lang->term_id );
		$this->assertEquals( 'ar', $this->pll_env->model->options['default_lang'] );

		// Delete the last language.
		$lang = $this->pll_env->model->get_language( 'ar' );
		$this->pll_env->model->delete_language( $lang->term_id );
		$this->assertEquals( array(), $this->pll_env->model->get_languages_list() );
	}

	/**
	 * Issue #910
	 */
	public function test_language_properties_in_transient() {
		$args = array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 2,
		);

		$this->pll_env->model->add_language( $args );
		$this->pll_env->model->set_languages_ready();
		$this->pll_env->model->get_languages_list(); // Saves the transient.

		$properties = array(
			'term_id',
			'name',
			'slug',
			'term_group',
			'term_props',
			'locale',
			'is_rtl',
			'w3c',
			'facebook',
			'home_url',
			'search_url',
			'host',
			'page_on_front',
			'page_for_posts',
			'flag_code',
			'flag_url',
			'flag',
			'custom_flag_url',
			'custom_flag',
			'active',
			'fallbacks',
			'is_default',
		);

		$languages = get_transient( 'pll_languages_list' );
		$language  = reset( $languages );
		$this->assertSameSets( $properties, array_keys( $language ) );

		// Let's check PLL_Language::$term_props.
		$this->assertArrayHasKey( 'language', $language['term_props'] );
		$this->assertArrayHasKey( 'term_id', $language['term_props']['language'] );
		$this->assertArrayHasKey( 'term_taxonomy_id', $language['term_props']['language'] );
		$this->assertArrayHasKey( 'count', $language['term_props']['language'] );
		$this->assertArrayHasKey( 'term_language', $language['term_props'] );
		$this->assertArrayHasKey( 'term_id', $language['term_props']['term_language'] );
		$this->assertArrayHasKey( 'term_taxonomy_id', $language['term_props']['term_language'] );
		$this->assertArrayHasKey( 'count', $language['term_props']['term_language'] );
	}

	/**
	 * This tests a conflict with Yoast SEO.
	 */
	public function test_create_language_when_term_link_requested_on_created_term() {
		// First language.
		$args = array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 2,
		);
		$this->pll_env->model->add_language( $args );

		$links_model     = $this->pll_env->model->get_links_model();
		$pll_admin = new PLL_Admin( $links_model );
		$pll_admin->options['hide_default'] = 1;
		new PLL_Filters_Links( $pll_admin );

		// These filters reproduces Yoast SEO's behavior.
		add_action(
			'created_term',
			function ( $term_id, $tt_id, $taxonomy ) {
				get_term_link( $term_id, $taxonomy );
			},
			PHP_INT_MAX,
			3
		);
		add_action(
			'edited_term',
			function ( $term_id, $tt_id, $taxonomy ) {
				get_term_link( $term_id, $taxonomy );
			},
			PHP_INT_MAX,
			3
		);

		// Second language.
		$args = array(
			'name'       => 'Francais',
			'slug'       => 'fr',
			'locale'     => 'fr_FR',
			'rtl'        => 0,
			'flag'       => 'fr',
			'term_group' => 2,
		);
		$this->assertTrue( $this->pll_env->model->add_language( $args ) );
	}

	public function test_default_language_order() {
		$args = array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 0,
		);
		$this->assertTrue( $this->pll_env->model->add_language( $args ) );

		$args = array(
			'name'       => 'Français',
			'slug'       => 'fr',
			'locale'     => 'fr_FR',
			'rtl'        => 0,
			'flag'       => 'fr',
			'term_group' => 1,
		);
		$this->assertTrue( $this->pll_env->model->add_language( $args ) );

		$args = array(
			'name'       => 'Deutsch',
			'slug'       => 'de',
			'locale'     => 'de_DE',
			'rtl'        => 0,
			'flag'       => 'de',
			'term_group' => 2,
		);
		$this->assertTrue( $this->pll_env->model->add_language( $args ) );

		$args = array(
			'name'       => 'Español',
			'slug'       => 'es',
			'locale'     => 'es_ES',
			'rtl'        => 0,
			'flag'       => 'es',
			'term_group' => 3,
		);
		$this->assertTrue( $this->pll_env->model->add_language( $args ) );

		$expected = array(
			'en',
			'fr',
			'de',
			'es',
		);

		$this->assertSameSetsWithIndex( $expected, $this->pll_env->model->get_languages_list( array( 'fields' => 'slug' ) ) );
	}

	public function test_create_language_object_without_term_language_tax() {
		$args = array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 0,
		);
		$this->assertTrue( $this->pll_env->model->add_language( $args ) );

		$term_language_args = array(
			'taxonomy' => 'term_language',
			'hide_empty' => false,
		);
		$terms_to_delete = get_terms( $term_language_args );

		foreach ( $terms_to_delete as $term ) {
			wp_delete_term( $term->term_id, 'term_language' );
		}

		$this->assertEmpty( get_terms( $term_language_args ) );

		$language = $this->pll_env->model->get_language( 'en' );

		$this->assertInstanceOf( PLL_Language::class, $language );
	}

	/**
	 * Test a second language deletion with 'term_group' > 0
	 * and the language is assigned to a content.
	 *
	 * Polylang Pro #1626
	 */
	public function test_delete_language_with_content_which_has_this_language() {
		$this->pll_env->terms = new PLL_CRUD_Terms( $this->pll_env );

		$args = array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 0,
		);
		$this->assertTrue( $this->pll_env->model->add_language( $args ) );

		$args = array(
			'name'       => 'Français',
			'slug'       => 'fr',
			'locale'     => 'fr_FR',
			'rtl'        => 0,
			'flag'       => 'fr',
			'term_group' => 1,
		);
		$this->assertTrue( $this->pll_env->model->add_language( $args ) );
		$this->assertEquals( 'en', $this->pll_env->options['default_lang'] );

		$fr = self::factory()->post->create();
		$this->pll_env->model->post->set_language( $fr, 'fr' );

		$lang = $this->pll_env->model->get_language( 'fr' );
		$this->pll_env->model->delete_language( $lang->term_id );
		$this->assertCount( 1, $this->pll_env->model->get_languages_list() );
	}

	/**
	 * Simulate cleaning the languages cache and building the languages list
	 * during the language deletion process by hooking to pre_delete_term.
	 *
	 * @return void
	 */
	public function clean_languages_cache_and_build_languages_list() {
		$this->pll_env->model->clean_languages_cache();
		$this->pll_env->model->get_languages_list();
	}

	/**
	 * Test a second language deletion with 'term_group' > 0
	 * and the language is assigned to a content.
	 *
	 * A language cache clean up and a languages list built are also run during the language deletion process.
	 *
	 * Polylang Pro #1626
	 */
	public function test_delete_language_with_content_which_has_this_language_and_with_clean_languages_cache() {
		add_action( 'pre_delete_term', array( $this, 'clean_languages_cache_and_build_languages_list' ) );

		// First language.
		$args = array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 0,
		);
		$this->assertTrue( $this->pll_env->model->add_language( $args ) );

		$args = array(
			'name'       => 'Español',
			'slug'       => 'es',
			'locale'     => 'es_ES',
			'rtl'        => 0,
			'flag'       => 'es',
			'term_group' => 3,
		);
		$this->assertTrue( $this->pll_env->model->add_language( $args ) );

		$args = array(
			'name'       => 'Deutsch',
			'slug'       => 'de',
			'locale'     => 'de_DE',
			'rtl'        => 0,
			'flag'       => 'de',
			'term_group' => 2,
		);
		$this->assertTrue( $this->pll_env->model->add_language( $args ) );

		$args = array(
			'name'       => 'Français',
			'slug'       => 'fr',
			'locale'     => 'fr_FR',
			'rtl'        => 0,
			'flag'       => 'fr',
			'term_group' => 1,
		);
		$this->assertTrue( $this->pll_env->model->add_language( $args ) );
		$this->assertEquals( 'en', $this->pll_env->options['default_lang'] );

		$fr = self::factory()->post->create();
		$this->pll_env->model->post->set_language( $fr, 'fr' );

		$lang = $this->pll_env->model->get_language( 'fr' );
		$this->pll_env->model->delete_language( $lang->term_id );
		$this->assertCount( 3, $this->pll_env->model->get_languages_list() );
	}
}
