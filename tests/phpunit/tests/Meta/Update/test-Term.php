<?php

namespace WP_Syntex\Polylang_Pro\Tests\Integration\modules\Meta\Update;

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

		add_filter(
			'pll_copy_term_metas',
			static function ( $metas ) {
				$metas[] = 'the_key';
				return $metas;
			}
		);

		$this->terms = $this->factory()->term->create_translated(
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
	public function test_update_meta( $value ) {
		add_term_meta( $this->terms['en'], 'the_key', 'to_be_updated' );

		$this->assertTrue( update_term_meta( $this->terms['en'], 'the_key', $value ) );

		$result = get_term_meta( $this->terms['fr'], 'the_key', false );

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
		$mid = add_term_meta( $this->terms['en'], 'the_key', 'to_be_updated' );

		$this->assertEquals( 'to_be_updated', get_term_meta( $this->terms['fr'], 'the_key', true ) );

		$this->assertTrue( update_metadata_by_mid( 'term', $mid, $value ) );

		$result = get_term_meta( $this->terms['fr'], 'the_key', false );

		$this->assertCount( 1, $result );

		$this->assertEquals( $value, reset( $result ) );
	}
}
