<?php

namespace WP_Syntex\Polylang_Pro\Tests\Integration\modules\Meta\Delete;

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
	public function test_delete_meta( $value ) {
		add_term_meta( $this->terms['en'], 'the_key', $value );

		$this->assertTrue( delete_term_meta( $this->terms['en'], 'the_key', $value ) );

		$this->assertEmpty( get_term_meta( $this->terms['fr'], 'the_key', false ) );
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_delete_meta_with_several_values( $value ) {
		add_term_meta( $this->terms['en'], 'the_key', 'another_value' );
		add_term_meta( $this->terms['en'], 'the_key', $value );

		$this->assertTrue( delete_term_meta( $this->terms['en'], 'the_key', $value ) );

		$result = get_term_meta( $this->terms['fr'], 'the_key', false );

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
		$mid = add_term_meta( $this->terms['en'], 'the_key', $value );

		$this->assertEquals( $value, get_term_meta( $this->terms['fr'], 'the_key', true ) );

		$this->assertTrue( delete_metadata_by_mid( 'term', $mid ) );

		$this->assertEmpty( get_term_meta( $this->terms['fr'], 'the_key', false ) );
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_delete_non_matching_meta_value( $value ) {
		add_term_meta( $this->terms['en'], 'the_key', $value );

		$this->assertFalse( delete_term_meta( $this->terms['en'], 'the_key', 'wrong_value' ) );

		$result = get_term_meta( $this->terms['fr'], 'the_key', false );

		$this->assertCount( 1, $result );
		$this->assertEquals( $value, reset( $result ) );
	}
}
