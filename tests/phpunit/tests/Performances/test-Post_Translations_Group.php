<?php

namespace WP_Syntex\Polylang\Tests\Integration\Performances;

use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
class Post_Translations_Group_Test extends PLL_UnitTestCase {

	/**
	 * @param PLL_UnitTest_Factory $factory
	 * @return void
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
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

		$this->assertSame( $posts['en'], self::$model->post->get_translation( $posts['en'], 'en' ) );
		$this->assertSame( $posts['fr'], self::$model->post->get_translation( $posts['fr'], 'fr' ) );

		$this->assertSame( $posts['fr'], self::$model->post->get_translation( $posts['en'], 'fr' ) );

		$this->assertSame( $posts['en'], self::$model->post->get_translation( $posts['fr'], 'en' ) );

		// Removes the translations from the group by updating the English post.
		self::$model->post->save_translations( $posts['en'], array() );

		// Checks we updated translations group only once when removing all the translations.
		$this->assertSame( 1, did_action( 'saved_post_translations' ) - $saved_term_count );

		$this->assertSame( self::$model->post->get_translation( $posts['en'], 'fr' ), 0 );
		$this->assertSame( self::$model->post->get_translation( $posts['fr'], 'en' ), 0 );
	}
}
