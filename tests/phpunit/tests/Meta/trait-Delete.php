<?php

namespace WP_Syntex\Polylang_Pro\Tests\Integration\modules\Meta;

trait Delete {
	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_delete_meta( $value ) {
		add_metadata( self::$type, self::$objects['en'], 'the_key', $value );

		$this->assertTrue( delete_metadata( self::$type, self::$objects['en'], 'the_key', $value ) );

		$this->assertEmpty( get_metadata( self::$type, self::$objects['fr'], 'the_key', false ) );
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_delete_meta_with_several_values( $value ) {
		add_metadata( self::$type, self::$objects['en'], 'the_key', 'another_value' );
		add_metadata( self::$type, self::$objects['en'], 'the_key', $value );

		$this->assertTrue( delete_metadata( self::$type, self::$objects['en'], 'the_key', $value ) );

		$result = get_metadata( self::$type, self::$objects['fr'], 'the_key', false );

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
		$mid = add_metadata( self::$type, self::$objects['en'], 'the_key', $value );

		$this->assertEquals( $value, get_metadata( self::$type, self::$objects['fr'], 'the_key', true ) );

		$this->assertTrue( delete_metadata_by_mid( self::$type, $mid ) );

		$this->assertEmpty( get_metadata( self::$type, self::$objects['fr'], 'the_key', false ) );
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_delete_non_matching_meta_value( $value ) {
		add_metadata( self::$type, self::$objects['en'], 'the_key', $value );

		$this->assertFalse( delete_metadata( self::$type, self::$objects['en'], 'the_key', 'wrong_value' ) );

		$result = get_metadata( self::$type, self::$objects['fr'], 'the_key', false );

		$this->assertCount( 1, $result );
		$this->assertEquals( $value, reset( $result ) );
	}
}
