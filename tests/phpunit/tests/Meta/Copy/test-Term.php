<?php

namespace WP_Syntex\Polylang_Pro\Tests\Integration\modules\Meta\Copy;

use WP_Syntex\Polylang_Pro\Tests\Integration\modules\Meta\TestCase;

/**
 * @group meta
 * @group term-meta
 */
class Term extends TestCase {
	/**
	 * @var int[]
	 */
	private $terms;

	public function set_up() {
		parent::set_up();

		$this->terms = $this->factory()->term->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);

		add_filter(
			'pll_copy_term_metas',
			static function ( $metas ) {
				$metas[] = 'the_key';
				return $metas;
			}
		);
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_copy_meta( $value ) {
		add_term_meta( $this->terms['en'], 'the_key', $value );

		$this->pll_env->sync->term_metas->copy( $this->terms['en'], $this->terms['fr'], 'fr' );

		$this->assertEquals( $value, get_term_meta( $this->terms['fr'], 'the_key', true ) );
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_copy_meta_with_several_values( $value ) {
		add_term_meta( $this->terms['en'], 'the_key', 'another_value' );
		add_term_meta( $this->terms['en'], 'the_key', $value );

		$this->pll_env->sync->term_metas->copy( $this->terms['en'], $this->terms['fr'], 'fr' );

		$this->assertEquals(
			array(
				'another_value',
				$value,
			),
			get_term_meta( $this->terms['fr'], 'the_key', false )
		);
	}
}
