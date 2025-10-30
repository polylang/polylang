<?php

namespace WP_Syntex\Polylang_Pro\Tests\Integration\modules\Meta\Add;

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

		$this->posts = $this->factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);

		$this->pll_env->options['sync'] = array( 'post_meta' );
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_add_public_meta( $value ) {
		add_post_meta( $this->posts['en'], 'the_key', $value );

		$this->assertEquals( $value, get_post_meta( $this->posts['fr'], 'the_key', true ) );
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_add_meta_with_several_values( $value ) {
		add_post_meta( $this->posts['en'], 'the_key', 'another_value' );
		add_post_meta( $this->posts['en'], 'the_key', $value );

		$this->assertEquals(
			array(
				'another_value',
				$value,
			),
			get_post_meta( $this->posts['fr'], 'the_key', false )
		);
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_add_private_meta( $value ) {
		add_filter(
			'pll_copy_post_metas',
			static function ( $metas ) {
				$metas[] = '_the_key';
				return $metas;
			}
		);
		add_post_meta( $this->posts['en'], '_the_key', $value );

		$this->assertEquals( $value, get_post_meta( $this->posts['fr'], '_the_key', true ) );
	}
}
