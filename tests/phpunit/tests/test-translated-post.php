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

	public function test_post_language() {
		$post_id = $this->factory->post->create();
		self::$model->post->set_language( $post_id, 'fr' );

		$this->assertEquals( 'fr', self::$model->post->get_language( $post_id )->slug );
	}

	public function test_post_translation() {
		$en = $this->factory->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$de = $this->factory->post->create();
		self::$model->post->set_language( $de, 'de' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr', 'de' ) );

		$this->assertEquals( self::$model->post->get_translation( $en, 'en' ), $en );
		$this->assertEquals( self::$model->post->get_translation( $fr, 'fr' ), $fr );
		$this->assertEquals( self::$model->post->get_translation( $fr, 'en' ), $en );
		$this->assertEquals( self::$model->post->get_translation( $en, 'fr' ), $fr );
		$this->assertEquals( self::$model->post->get_translation( $de, 'fr' ), $fr );
	}

	public function test_delete_post_translation() {
		$en = $this->factory->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$de = $this->factory->post->create();
		self::$model->post->set_language( $de, 'de' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr', 'de' ) );
		self::$model->post->delete_translation( $fr );

		$this->assertEquals( self::$model->post->get_translation( $fr, 'fr' ), $fr );
		$this->assertEquals( self::$model->post->get_translation( $en, 'de' ), $de );
		$this->assertEquals( self::$model->post->get_translation( $de, 'en' ), $en );

		$this->assertFalse( self::$model->post->get_translation( $en, 'fr' ) ); // fails
		$this->assertFalse( self::$model->post->get_translation( $fr, 'en' ) );
		$this->assertFalse( self::$model->post->get_translation( $fr, 'de' ) );
		$this->assertFalse( self::$model->post->get_translation( $de, 'fr' ) ); // fails
	}

	public function test_current_user_can_synchronize() {
		add_filter( 'pll_pre_current_user_can_synchronize_post', '__return_null' ); // Enable capability check

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		$author = self::factory()->user->create( array( 'role' => 'author' ) );

		wp_set_current_user( $author );

		$en = $this->factory->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->assertTrue( self::$model->post->current_user_can_synchronize( $en ) );
		$this->assertTrue( self::$model->post->current_user_can_synchronize( $fr ) );

		wp_set_current_user( $editor );

		$this->assertTrue( self::$model->post->current_user_can_synchronize( $en ) );
		$this->assertTrue( self::$model->post->current_user_can_synchronize( $fr ) );

		$de = $this->factory->post->create();
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
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );

		wp_set_current_user( 0 );
		$this->assertFalse( self::$model->post->current_user_can_read( $post_id ) );
		$this->assertFalse( self::$model->post->current_user_can_read( $post_id, 'edit' ) );

		wp_set_current_user( 1 );
		$this->assertFalse( self::$model->post->current_user_can_read( $post_id ) );
		$this->assertTrue( self::$model->post->current_user_can_read( $post_id, 'edit' ) );

		$post_id = $this->factory->post->create(
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

		$post_id = $this->factory->post->create( array( 'post_status' => 'private' ) );

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
			$new_post = $this->factory()->attachment->create();
			self::$model->post->set_language( $new_post, $language );
			$translations[ $language ] = $new_post;
		}
		$post_id = array_shift( $translations );
		self::$model->post->save_translations( $post_id, $translations );

		self::$model->post->update_language( $post_id, $to, 'post' );

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
		$options = PLL_Install::get_default_options();
		$model = new PLL_Model( $options );
		$model->post = new PLL_Translated_Post( $model );

		$this->dont_save_translations_with_incorrect_language( $model->post );
	}
}
