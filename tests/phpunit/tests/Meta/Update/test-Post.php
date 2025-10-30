<?php

namespace WP_Syntex\Polylang_Pro\Tests\Integration\modules\Meta\Update;

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

		$this->pll_env->options['sync'] = array( 'post_meta' );

		add_filter(
			'pll_copy_post_metas',
			static function ( $metas ) {
				$metas[] = '_the_key';
				return $metas;
			}
		);

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
	public function test_update_public_meta( $value ) {
		add_post_meta( $this->posts['en'], 'the_key', 'to_be_updated' );

		$this->assertTrue( update_post_meta( $this->posts['en'], 'the_key', $value ) );

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
	public function test_update_meta_by_mid( $value ) {
		$mid = add_post_meta( $this->posts['en'], 'the_key', 'to_be_updated' );

		$this->assertEquals( 'to_be_updated', get_post_meta( $this->posts['fr'], 'the_key', true ) );

		$this->assertTrue( update_metadata_by_mid( 'post', $mid, $value ) );

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
	public function test_update_private_meta( $value ) {
		add_post_meta( $this->posts['en'], '_the_key', 'to_be_updated' );

		$this->assertTrue( update_post_meta( $this->posts['en'], '_the_key', $value ) );

		$result = get_post_meta( $this->posts['fr'], '_the_key', false );

		$this->assertCount( 1, $result );

		$this->assertEquals( $value, reset( $result ) );
	}
}
