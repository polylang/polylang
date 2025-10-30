<?php

namespace WP_Syntex\Polylang_Pro\Tests\Integration\modules\Meta\Delete;

use WP_Syntex\Polylang_Pro\Tests\Integration\modules\Meta\TestCase;

/**
 * @group meta
 * @group post-meta
 */
class Post extends TestCase {
	/**
	 * @var int[]
	 */
	private $posts;

	public function set_up() {
		parent::set_up();

		add_filter(
			'pll_copy_post_metas',
			static function ( $metas ) {
				$metas[] = '_the_key';
				return $metas;
			}
		);

		$this->pll_env->options['sync'] = array( 'post_meta' );

		$this->posts = $this->factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_delete_public_meta( $value ) {
		add_post_meta( $this->posts['en'], 'the_key', $value );

		$this->assertTrue( delete_post_meta( $this->posts['en'], 'the_key', $value ) );

		$this->assertEmpty( get_post_meta( $this->posts['fr'], 'the_key', false ) );
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_delete_meta_with_several_values( $value ) {
		add_post_meta( $this->posts['en'], 'the_key', 'another_value' );
		add_post_meta( $this->posts['en'], 'the_key', $value );

		$this->assertTrue( delete_post_meta( $this->posts['en'], 'the_key', $value ) );

		$result = get_post_meta( $this->posts['fr'], 'the_key', false );

		if ( empty( $value ) ) {
			// WordPress doesn't manage empty values well and deletes all meta values if falsey. @see {delete_metadata}
			$this->assertEmpty( $result );
			return;
		}

		$this->assertCount( 1, $result );
		$this->assertEquals( 'another_value', reset( $result ) );
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_delete_meta_by_mid( $value ) {
		$mid = add_post_meta( $this->posts['en'], 'the_key', $value );

		$this->assertEquals( $value, get_post_meta( $this->posts['fr'], 'the_key', true ) );

		$this->assertTrue( delete_metadata_by_mid( 'post', $mid ) );

		$this->assertEmpty( get_post_meta( $this->posts['fr'], 'the_key', false ) );
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_delete_non_matching_meta_value( $value ) {
		add_post_meta( $this->posts['en'], 'the_key', $value );

		$this->assertFalse( delete_post_meta( $this->posts['en'], 'the_key', 'wrong_value' ) );

		$result = get_post_meta( $this->posts['fr'], 'the_key', false );

		$this->assertCount( 1, $result );
		$this->assertEquals( $value, reset( $result ) );
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_delete_private_meta( $value ) {
		add_post_meta( $this->posts['en'], '_the_key', $value );

		$this->assertTrue( delete_post_meta( $this->posts['en'], '_the_key', $value ) );

		$this->assertEmpty( get_post_meta( $this->posts['fr'], '_the_key', false ) );
	}
}
