<?php

class Translated_Post_Test extends PLL_Translated_Object_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE_formal' );
	}

	public function tear_down() {
		parent::tear_down();

		if ( is_multisite() ) {
			restore_current_blog();
		}
	}

	public function test_post_language() {
		$post_id = self::factory()->post->create();
		$language_set = self::$model->post->set_language( $post_id, 'fr' );

		$this->assertTrue( $language_set );
		$this->assertEquals( 'fr', self::$model->post->get_language( $post_id )->slug );
	}

	public function test_post_language_updated_if_another_language_is_set() {
		$post_id = self::factory()->post->create();

		$this->assertNotEmpty( $post_id );

		self::$model->post->set_language( $post_id, 'fr' );

		$this->assertTrue( self::$model->post->set_language( $post_id, 'en' ) );
		$this->assertEquals( 'en', self::$model->post->get_language( $post_id )->slug );
	}

	public function test_post_language_not_updated_if_already_set() {
		$post_id = self::factory()->post->create();

		$this->assertNotEmpty( $post_id );

		self::$model->post->set_language( $post_id, 'fr' );

		$this->assertFalse( self::$model->post->set_language( $post_id, 'fr' ) );
	}

	public function test_post_translation() {
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$de = self::factory()->post->create();
		self::$model->post->set_language( $de, 'de' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr', 'de' ) );

		$this->assertSame( $en, self::$model->post->get_translation( $en, 'en' ) );
		$this->assertSame( $fr, self::$model->post->get_translation( $fr, 'fr' ) );
		$this->assertSame( $de, self::$model->post->get_translation( $de, 'de' ) );

		$this->assertSame( $fr, self::$model->post->get_translation( $en, 'fr' ) );
		$this->assertSame( $de, self::$model->post->get_translation( $en, 'de' ) );

		$this->assertSame( $en, self::$model->post->get_translation( $fr, 'en' ) );
		$this->assertSame( $de, self::$model->post->get_translation( $fr, 'de' ) );

		$this->assertSame( $en, self::$model->post->get_translation( $de, 'en' ) );
		$this->assertSame( $fr, self::$model->post->get_translation( $de, 'fr' ) );
	}

	public function test_delete_post_translation() {
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$de = self::factory()->post->create();
		self::$model->post->set_language( $de, 'de' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr', 'de' ) );
		self::$model->post->delete_translation( $fr );

		$this->assertSame( $fr, self::$model->post->get_translation( $fr, 'fr' ) );
		$this->assertSame( $de, self::$model->post->get_translation( $en, 'de' ) );
		$this->assertSame( $en, self::$model->post->get_translation( $de, 'en' ) );

		$this->assertSame( 0, self::$model->post->get_translation( $en, 'fr' ) ); // fails
		$this->assertSame( 0, self::$model->post->get_translation( $fr, 'en' ) );
		$this->assertSame( 0, self::$model->post->get_translation( $fr, 'de' ) );
		$this->assertSame( 0, self::$model->post->get_translation( $de, 'fr' ) ); // fails
	}

	/**
	 * Checks that the translations group are kept unique when linking several translations together.
	 *
	 * @ticket #2717 see {https://github.com/polylang/polylang-pro/issues/2717}.
	 */
	public function test_save_translations() {
		$posts = self::factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' ),
			array( 'lang' => 'de' )
		);

		$terms = wp_get_object_terms( $posts, 'post_translations' );
		$this->assertCount( 1, $terms );

		$this->assertSame( $posts['en'], self::$model->post->get_translation( $posts['en'], 'en' ) );
		$this->assertSame( $posts['fr'], self::$model->post->get_translation( $posts['fr'], 'fr' ) );
		$this->assertSame( $posts['de'], self::$model->post->get_translation( $posts['de'], 'de' ) );

		$this->assertSame( $posts['fr'], self::$model->post->get_translation( $posts['en'], 'fr' ) );
		$this->assertSame( $posts['de'], self::$model->post->get_translation( $posts['en'], 'de' ) );

		$this->assertSame( $posts['en'], self::$model->post->get_translation( $posts['fr'], 'en' ) );
		$this->assertSame( $posts['de'], self::$model->post->get_translation( $posts['fr'], 'de' ) );

		$this->assertSame( $posts['fr'], self::$model->post->get_translation( $posts['de'], 'fr' ) );
		$this->assertSame( $posts['en'], self::$model->post->get_translation( $posts['de'], 'en' ) );

		/*
		.* Removes the translations from the group by updating the German post.
		.* It keeps a translations group only for German.
		.* Translations in English and French are also unlinked.
		.* See `PLL_Translated_Post::delete_translation()`.
		 */
		self::$model->post->save_translations( $posts['de'], array() );

		$terms = wp_get_object_terms( $posts, 'post_translations' );
		$this->assertCount( 1, $terms );

		$this->assertSame( 0, self::$model->post->get_translation( $posts['de'], 'fr' ) );
		$this->assertSame( 0, self::$model->post->get_translation( $posts['de'], 'en' ) );
		$this->assertSame( 0, self::$model->post->get_translation( $posts['en'], 'fr' ) );

		// Links again the French and English posts.
		self::$model->post->save_translations( $posts['fr'], array( 'fr' => $posts['fr'], 'en' => $posts['en'] ) );

		$terms = wp_get_object_terms( $posts, 'post_translations' );
		$this->assertCount( 2, $terms ); // Is correct at this step because the German post isn't translated into either English or French.

		// Links again the German and English posts but not with the French one.
		self::$model->post->save_translations( $posts['de'], array( 'de' => $posts['de'], 'en' => $posts['en'] ) );

		$terms = wp_get_object_terms( $posts, 'post_translations' );
		$this->assertCount( 1, $terms );

		$this->assertSame( $posts['en'], self::$model->post->get_translation( $posts['de'], 'en' ) );
		$this->assertSame( $posts['de'], self::$model->post->get_translation( $posts['en'], 'de' ) );

		// The French post is no longer in the translations group.
		$this->assertSame( 0, self::$model->post->get_translation( $posts['fr'], 'en' ) );
		$this->assertSame( 0, self::$model->post->get_translation( $posts['fr'], 'de' ) );
	}

	public function test_current_user_can_synchronize() {
		add_filter( 'pll_pre_current_user_can_synchronize_post', '__return_null' ); // Enable capability check

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		$author = self::factory()->user->create( array( 'role' => 'author' ) );

		wp_set_current_user( $author );

		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->assertTrue( self::$model->post->current_user_can_synchronize( $en ) );
		$this->assertTrue( self::$model->post->current_user_can_synchronize( $fr ) );

		wp_set_current_user( $editor );

		$this->assertTrue( self::$model->post->current_user_can_synchronize( $en ) );
		$this->assertTrue( self::$model->post->current_user_can_synchronize( $fr ) );

		$de = self::factory()->post->create();
		self::$model->post->set_language( $de, 'de' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr', 'de' ) );

		wp_set_current_user( $editor );

		$this->assertTrue( self::$model->post->current_user_can_synchronize( $en ) );
		$this->assertTrue( self::$model->post->current_user_can_synchronize( $fr ) );
		$this->assertTrue( self::$model->post->current_user_can_synchronize( $de ) );

		wp_set_current_user( $author );

		$this->assertFalse( self::$model->post->current_user_can_synchronize( $en ) );
		$this->assertFalse( self::$model->post->current_user_can_synchronize( $fr ) );
		$this->assertFalse( self::$model->post->current_user_can_synchronize( $de ) );
	}

	public function test_current_user_can_read() {
		$post_id = self::factory()->post->create( array( 'post_status' => 'draft' ) );

		wp_set_current_user( 0 );
		$this->assertFalse( self::$model->post->current_user_can_read( $post_id ) );
		$this->assertFalse( self::$model->post->current_user_can_read( $post_id, 'edit' ) );

		wp_set_current_user( 1 );
		$this->assertFalse( self::$model->post->current_user_can_read( $post_id ) );
		$this->assertTrue( self::$model->post->current_user_can_read( $post_id, 'edit' ) );

		$post_id = self::factory()->post->create(
			array(
				'post_status' => 'future',
				'post_date'   => gmdate( 'Y-m-d H:i:s', time() + 100 ),
			)
		);

		wp_set_current_user( 0 );
		$this->assertFalse( self::$model->post->current_user_can_read( $post_id ) );
		$this->assertFalse( self::$model->post->current_user_can_read( $post_id, 'edit' ) );

		wp_set_current_user( 1 );
		$this->assertFalse( self::$model->post->current_user_can_read( $post_id ) );
		$this->assertTrue( self::$model->post->current_user_can_read( $post_id, 'edit' ) );

		$post_id = self::factory()->post->create( array( 'post_status' => 'private' ) );

		wp_set_current_user( 0 );
		$this->assertFalse( self::$model->post->current_user_can_read( $post_id ) );
		$this->assertFalse( self::$model->post->current_user_can_read( $post_id, 'edit' ) );

		wp_set_current_user( 1 );
		$this->assertTrue( self::$model->post->current_user_can_read( $post_id ) );
		$this->assertTrue( self::$model->post->current_user_can_read( $post_id, 'edit' ) );
	}

	/**
	 * @dataProvider update_language_provider
	 *
	 * @param string[] $original_group An array of language locales to be included in the original translations group.
	 * @param string   $to A language locale to update the post to.
	 * @param string[] $expected_new_group An array of language locales to be included in the new translations group.
	 * @param string[] $expected_former_group Optional. Represents the former translations group of the post if changing language should have set the post in a separate group.
	 */
	public function test_update_language( $original_group, $to, $expected_new_group, $expected_former_group = array() ) {
		wp_set_current_user( 1 ); // Needs edit_post capability.

		$translations = array();
		foreach ( $original_group as $language ) {
			$new_post = self::factory()->attachment->create();
			self::$model->post->set_language( $new_post, $language );
			$translations[ $language ] = $new_post;
		}
		$post_id = array_shift( $translations );
		self::$model->post->save_translations( $post_id, $translations );

		self::$model->post->set_language( $post_id, self::$model->get_language( $to ) );

		$updated_language_translations_group = array_keys( self::$model->post->get_translations( $post_id ) );
		$updated_language_old_translations_group = array_keys( self::$model->post->get_translations( array_values( $translations )[0] ) );
		$this->assertEqualSets( $expected_new_group, $updated_language_translations_group );
		if ( ! empty( $expected_former_group ) ) {
			$this->assertEqualSets( $expected_former_group, $updated_language_old_translations_group );
		}
	}

	public function update_language_provider() {
		return array(
			'Update to same language' => array(
				'original_group' => array( 'en', 'fr' ),
				'to' => 'en',
				'expected_new_group' => array( 'en', 'fr' ),
			),
			'Update to language not in translations group' => array(
				'original_group' => array( 'en', 'fr' ),
				'to' => 'de',
				'expected_new_group' => array( 'fr', 'de' ),
			),
			'Update to language already in translations group' => array(
				'original_group' => array( 'en', 'fr', 'de' ),
				'to' => 'fr',
				'expected_new_group' => array( 'fr' ),
				'expected_former_group' => array( 'fr', 'de' ),
			),
		);
	}

	/**
	 * @covers PLL_Translated_Object::save_translations()
	 */
	public function test_dont_save_translations_with_incorrect_language() {
		$options = self::create_options(
			array(
				'default_lang' => 'en',
			)
		);
		$model = new PLL_Model( $options );

		$this->dont_save_translations_with_incorrect_language( $model->post );
	}

	/**
	 * @ticket #1698 see {https://github.com/polylang/polylang-pro/issues/1698}.
	 * @covers PLL_Translated_Post::get_db_infos()
	 */
	public function test_get_db_infos() {
		$options = self::create_options(
			array(
				'default_lang' => 'en',
			)
		);
		$model = new PLL_Model( $options );

		$ref = new ReflectionMethod( $model->post, 'get_db_infos' );
		$ref->setAccessible( true );
		$db_infos = $ref->invoke( $model->post );

		$this->assertSame( $GLOBALS['wpdb']->posts, $db_infos['table'], 'get_db_infos() does not return the right table name.' );
		$this->assertSame( $GLOBALS['wpdb']->posts, $db_infos['default_alias'], 'get_db_infos() does not return the right field alias.' );

		if ( ! is_multisite() ) {
			return;
		}

		$site_id = self::factory()->blog->create();

		switch_to_blog( $site_id );
		$multi_db_infos = $ref->invoke( $model->post );

		$this->assertSame( $GLOBALS['wpdb']->posts, $multi_db_infos['table'], 'get_db_infos() does not return the right table name.' );
		$this->assertSame( $GLOBALS['wpdb']->posts, $multi_db_infos['default_alias'], 'get_db_infos() does not return the right field alias.' );
		$this->assertNotSame( $db_infos['table'], $multi_db_infos['table'], 'The table name should be different between blogs.' );
		$this->assertNotSame( $db_infos['default_alias'], $multi_db_infos['default_alias'], 'The field alias should be different between blogs.' );
	}

	/**
	 * Checks that updating a post translations group is done only once when we unlink all translations.
	 */
	public function test_should_not_update_translations_group_when_removing_all_translations() {
		$posts = self::factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);

		$saved_term_count = did_action( 'saved_post_translations' );

		$terms = wp_get_object_terms( $posts, 'post_translations' );
		$this->assertCount( 1, $terms );

		// Removes the translations from the group by updating the English post.
		self::$model->post->save_translations( $posts['en'], array() );

		// Checks we updated translations group only once when removing all the translations.
		$this->assertSame( 1, did_action( 'saved_post_translations' ) - $saved_term_count );

		$this->assertSame( 0, self::$model->post->get_translation( $posts['en'], 'fr' ) );
		$this->assertSame( 0, self::$model->post->get_translation( $posts['fr'], 'en' ) );
	}
}
