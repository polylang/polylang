<?php

class Create_Delete_Languages_Test extends PLL_UnitTestCase {

	public function test_add_and_delete_language() {
		// first language
		$args = array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 2,
		);

		$this->assertTrue( self::$model->add_language( $args ) );

		$lang = self::$model->get_language( 'en' );

		$this->assertEquals( 'English', $lang->name );
		$this->assertEquals( 'en', $lang->slug );
		$this->assertEquals( 'en_US', $lang->locale );
		$this->assertEquals( 0, $lang->is_rtl );
		$this->assertEquals( 2, $lang->term_group );

		// second language (rtl)
		$args = array(
			'name'       => 'العربية',
			'slug'       => 'ar',
			'locale'     => 'ar',
			'rtl'        => 1,
			'flag'       => 'arab',
			'term_group' => 1,
		);

		$this->assertTrue( self::$model->add_language( $args ) );

		$lang = self::$model->get_language( 'ar' );

		$this->assertEquals( 'العربية', $lang->name );
		$this->assertEquals( 'ar', $lang->slug );
		$this->assertEquals( 'ar', $lang->locale );
		$this->assertEquals( 1, $lang->is_rtl );
		$this->assertEquals( 1, $lang->term_group );

		// check default language
		$this->assertEquals( 'en', self::$model->options['default_lang'] );

		// check language order
		$this->assertEqualSetsWithIndex( array( 'ar', 'en' ), self::$model->get_languages_list( array( 'fields' => 'slug' ) ) );

		// attempt to create a language with the same slug as an existing one
		self::$model->add_language( array( 'slug' => 'en-gb', 'locale' => 'en_GB' ) );
		$lang = self::$model->get_language( 'en' );
		$this->assertEquals( 'en_US', $lang->locale );
		$this->assertFalse( self::$model->get_language( 'en_GB' ) );
		$this->assertEquals( 2, count( self::$model->get_languages_list() ) );

		// delete 1 language
		$lang = self::$model->get_language( 'en_US' );
		self::$model->delete_language( $lang->term_id );
		$this->assertEquals( 'ar', self::$model->options['default_lang'] );

		// delete the last language
		$lang = self::$model->get_language( 'ar' );
		self::$model->delete_language( $lang->term_id );
		$this->assertEquals( array(), self::$model->get_languages_list() );
	}

	/**
	 * Bug fixed in 2.3.
	 */
	public function test_unique_language_code_if_same_as_locale() {
		// First language
		$args = array(
			'name'       => 'العربية',
			'slug'       => 'a', // Intentional mistake
			'locale'     => 'ar',
			'rtl'        => 1,
			'flag'       => 'arab',
			'term_group' => 1,
		);

		$this->assertTrue( self::$model->add_language( $args ) );

		$lang = self::$model->get_language( 'ar' );
		$args['lang_id'] = $lang->term_id;
		$args['slug'] = 'ar';
		$this->assertTrue( self::$model->update_language( $args ) );

		self::$model->delete_language( $lang->term_id );
	}

	public function test_invalid_languages() {
		global $wp_settings_errors;

		$args = array(
			'name'       => '',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 1,
		);

		$this->assertWPError( self::$model->add_language( $args ), 'The language must have a name' );

		$args['name'] = 'English';
		$args['locale'] = 'EN';

		$this->assertWPError( self::$model->add_language( $args ), 'Enter a valid WordPress locale' );

		$args['locale'] = 'en-US';

		$this->assertWPError( self::$model->add_language( $args ), 'Enter a valid WordPress locale' );

		$args['locale'] = 'en_US';
		$args['slug'] = 'EN';

		$this->assertWPError( self::$model->add_language( $args ), 'The language code contains invalid characters' );

		$args['slug'] = 'en';
		$args['flag'] = 'en';

		$this->assertWPError( self::$model->add_language( $args ), 'The flag does not exist' );
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

		self::$model->add_language( $args );
		self::$model->get_languages_list(); // Saves the transient.

		$properties = array(
			'term_id',
			'name',
			'slug',
			'term_group',
			'term_taxonomy_id',
			'count',
			'tl_term_id',
			'tl_term_taxonomy_id',
			'tl_count',
			'locale',
			'is_rtl',
			'w3c',
			'facebook',
			'home_url',
			'search_url',
			'host',
			'mo_id',
			'page_on_front',
			'page_for_posts',
			'flag_code',
			'flag_url',
			'flag',
			'custom_flag_url',
			'custom_flag',
		);

		$languages = get_transient( 'pll_languages_list' );
		$this->assertEqualSets( $properties, array_keys( reset( $languages ) ) );
	}

	/**
	 * This test a conflict with Yoast SEO.
	 */
	public function test_create_language_when_term_link_requested_on_created_term() {
		// first language
		$args = array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 2,
		);
		self::$model->add_language( $args );

		$links_model     = self::$model->get_links_model();
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

		// second language
		$args = array(
			'name'       => 'Francais',
			'slug'       => 'fr',
			'locale'     => 'fr_FR',
			'rtl'        => 0,
			'flag'       => 'fr',
			'term_group' => 2,
		);
		$this->assertTrue( self::$model->add_language( $args ) );
	}

	public function test_delete_languages() {
		global $wpdb;

		// Let's create some languages.
		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'es_ES' );

		$languages = self::$model->get_languages_list();

		$en = self::$model->get_language( 'en' );
		$fr = self::$model->get_language( 'fr' );
		$es = self::$model->get_language( 'es' );

		$this->assertCount( 3, $languages, 'There should be three languages created.' );
		$this->assertInstanceOf( PLL_Language::class, $en, 'English should have been created.' );
		$this->assertInstanceOf( PLL_Language::class, $fr, 'French should have been created.' );
		$this->assertInstanceOf( PLL_Language::class, $es, 'Spanish should have been created.' );

		// Let's create some posts and terms.
		$this->factory->post->create(); // Trick to ensure later created post won't have the same ids as terms.

		$en_post = $this->factory->post->create();
		self::$model->post->set_language( $en_post, 'en' );
		$fr_post = $this->factory->post->create();
		self::$model->post->set_language( $fr_post, 'fr' );
		$es_post = $this->factory->post->create();
		self::$model->post->set_language( $es_post, 'es' );
		$post_translations = array(
			'en' => $en_post,
			'fr' => $fr_post,
			'es' => $es_post,
		);
		self::$model->post->save_translations( $en_post, $post_translations );

		$this->assertSame( $post_translations, self::$model->post->get_translations( $en_post ) );

		$en_term = $this->factory->term->create();
		self::$model->term->set_language( $en_term, 'en' );
		$fr_term = $this->factory->term->create();
		self::$model->term->set_language( $fr_term, 'fr' );
		$es_term = $this->factory->term->create();
		self::$model->term->set_language( $es_term, 'es' );
		$term_translations = array(
			'en' => $en_term,
			'fr' => $fr_term,
			'es' => $es_term,
		);
		self::$model->term->save_translations( $en_term, $term_translations );
		
		$this->assertSameSets( $term_translations, self::$model->term->get_translations( $en_term ) );

		// Let's delete one language.
		self::$model->delete_language( $es->term_id );

		$this->assertFalse( self::$model->get_language( 'es' ), 'Spanish shoud have been deleted.' );
		$this->assertEmpty(
			$wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d", $es->term_taxonomy_id ) ), 
			'Spanish deletion shoud clean the corresponding term relationships.' 
		);
		$post_translations_from_db = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT description
					FROM {$wpdb->term_relationships}
					INNER JOIN {$wpdb->term_taxonomy} 
					ON {$wpdb->term_relationships}.term_taxonomy_id={$wpdb->term_taxonomy}.term_taxonomy_id
					AND {$wpdb->term_relationships}.object_id=%d
					AND {$wpdb->term_taxonomy}.taxonomy='post_translations'",
				$en_post
			),
			ARRAY_A
		);
		$this->assertCount( 1, $post_translations_from_db, 'One translation group should still exist.' );
		$post_translations = maybe_unserialize( reset( $post_translations_from_db )['description'] );
		$this->assertArrayNotHasKey( 'es', $post_translations, 'Spanish cannot be part of the post translation group.' );
		$this->assertArrayHasKey( 'en', $post_translations, 'English should be part of the post translation group.' );
		$this->assertArrayHasKey( 'fr', $post_translations, 'French should be part of the post translation group.' );
		$term_translations_from_db = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT description
					FROM {$wpdb->term_relationships}
					INNER JOIN {$wpdb->term_taxonomy} 
					ON {$wpdb->term_relationships}.term_taxonomy_id={$wpdb->term_taxonomy}.term_taxonomy_id
					AND {$wpdb->term_relationships}.object_id=%d
					AND {$wpdb->term_taxonomy}.taxonomy='term_translations'",
				$en_term
			),
			ARRAY_A
		);
		$this->assertCount( 1, $term_translations_from_db, 'One translation group should still exist.' );
		$term_translations = maybe_unserialize( reset( $term_translations_from_db )['description'] );
		$this->assertArrayNotHasKey( 'es', $term_translations, 'Spanish cannot be part of the term translation group.' );
		$this->assertArrayHasKey( 'en', $term_translations, 'English should be part of the term translation group.' );
		$this->assertArrayHasKey( 'fr', $term_translations, 'French should be part of the term translation group.' );
		
		// Before any process, get the 'post_translations' and 'term_translations' term ids.
		$post_translations_id = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT term_id
					FROM {$wpdb->term_relationships}
					INNER JOIN {$wpdb->term_taxonomy} 
					ON {$wpdb->term_relationships}.term_taxonomy_id={$wpdb->term_taxonomy}.term_taxonomy_id
					AND {$wpdb->term_relationships}.object_id=%d
					AND {$wpdb->term_taxonomy}.taxonomy='post_translations'",
				$en_post
			),
			ARRAY_A
		);
		$this->assertCount( 1, $post_translations_id, 'It should exist only one post_translations.' );
		$post_translations_id = reset( $post_translations_id )['term_id'];

		$term_translations_id = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT term_id
					FROM {$wpdb->term_relationships}
					INNER JOIN {$wpdb->term_taxonomy} 
					ON {$wpdb->term_relationships}.term_taxonomy_id={$wpdb->term_taxonomy}.term_taxonomy_id
					AND {$wpdb->term_relationships}.object_id=%d
					AND {$wpdb->term_taxonomy}.taxonomy='term_translations'",
				$en_term
			),
			ARRAY_A
		);
		$this->assertCount( 1, $term_translations_id, 'It should exist only one term_translations.' );
		$term_translations_id = reset( $term_translations_id )['term_id'];

		// Let's delete a second language.
		self::$model->delete_language( $fr->term_id );
		
		$this->assertFalse( self::$model->get_language( 'fr' ), 'French shoud have been deleted.' );
		$this->assertEmpty(
			$wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d", $fr->term_taxonomy_id ) ), 
			'French deletion shoud clean the corresponding term relationships.' 
		);
		$post_relationships_to_post_translations_term = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d", $post_translations_id ) );
		$term_relationships_to_post_translations_term = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d", $term_translations_id ) );
		$this->assertEmpty( $post_relationships_to_post_translations_term, 'It should not remain any post_translations term relationships after deleting the last secondary language.' );
		$this->assertEmpty( $term_relationships_to_post_translations_term, 'It should not remain any term_translations term relationships after deleting the last secondary language.' );
	}
}
