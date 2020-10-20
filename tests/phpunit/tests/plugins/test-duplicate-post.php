<?php

if ( file_exists( DIR_TESTROOT . '/../duplicate-post/' ) ) {
	require_once DIR_TESTROOT . '/../duplicate-post/duplicate-post.php';
	require_once DIR_TESTROOT . '/../duplicate-post/duplicate-post-admin.php';

	class Duplicate_Post_Test extends PLL_UnitTestCase {

		/**
		 * @param WP_UnitTest_Factory $factory
		 */
		public static function wpSetUpBeforeClass( $factory ) {
			parent::wpSetUpBeforeClass( $factory );

			self::$polylang->model->post->registered_post_type( 'post' ); // Important.

			self::create_language( 'en_US' );
			self::create_language( 'fr_FR' );

			PLL_Integrations::instance()->duplicate_post = new PLL_Duplicate_Post();
			PLL_Integrations::instance()->duplicate_post->init();
		}

		function test_exclude_post_translations() {
			$en = $this->factory->post->create();
			self::$polylang->model->post->set_language( $en, 'en' );

			$fr = $this->factory->post->create();
			self::$polylang->model->post->set_language( $fr, 'fr' );

			self::$polylang->model->post->save_translations( $en, compact( 'fr' ) );

			$post = get_post( $en );
			duplicate_post_admin_init();
			$new_id = duplicate_post_create_duplicate( $post, 'draft' );

			// Check our code.
			$this->assertContains( 'post_translations', get_object_taxonomies( $post->post_type ) );
			$this->assertContains( 'post_translations', get_option( 'duplicate_post_taxonomies_blacklist' ) );

			// Check the integration.
			$this->assertEquals( $fr, self::$polylang->model->post->get( $en, 'fr' ) );
			$this->assertFalse( self::$polylang->model->post->get( $new_id, 'fr' ) );
		}
	}
}
