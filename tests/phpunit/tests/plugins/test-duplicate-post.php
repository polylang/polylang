<?php

if ( file_exists( DIR_TESTROOT . '/../duplicate-post/' ) ) {
	require_once DIR_TESTROOT . '/../duplicate-post/duplicate-post.php';
	require_once DIR_TESTROOT . '/../duplicate-post/admin-functions.php';

	class Duplicate_Post_Test extends PLL_UnitTestCase {

		/**
		 * @param WP_UnitTest_Factory $factory
		 */
		public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
			parent::wpSetUpBeforeClass( $factory );

			self::$model->post->registered_post_type( 'post' ); // Important.

			self::create_language( 'en_US' );
			self::create_language( 'fr_FR' );

			PLL_Integrations::instance()->duplicate_post = new PLL_Duplicate_Post();
			PLL_Integrations::instance()->duplicate_post->init();
		}

		public function test_exclude_post_translations() {
			$en = self::factory()->post->create();
			self::$model->post->set_language( $en, 'en' );

			$fr = self::factory()->post->create();
			self::$model->post->set_language( $fr, 'fr' );

			self::$model->post->save_translations( $en, compact( 'fr' ) );

			$post = get_post( $en );
			duplicate_post_admin_init();
			$new_id = duplicate_post_create_duplicate( $post, 'draft' );

			// Check our code.
			$this->assertContains( 'post_translations', get_object_taxonomies( $post->post_type ) );
			$this->assertContains( 'post_translations', get_option( 'duplicate_post_taxonomies_blacklist' ) );

			// Check the integration.
			$this->assertSame( $fr, self::$model->post->get( $en, 'fr' ) );
			$this->assertSame( 0, self::$model->post->get( $new_id, 'fr' ) );
		}
	}
}
