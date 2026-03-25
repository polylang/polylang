<?php

namespace WP_Syntex\Polylang_Pro\Tests\Integration\modules\Meta;

trait Update {
	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_update_meta( $value ) {
		add_metadata( self::$type, self::$objects['en'], 'the_key', 'to_be_updated' );

		$this->assertTrue( update_metadata( self::$type, self::$objects['en'], 'the_key', $value ) );

		$result = get_metadata( self::$type, self::$objects['fr'], 'the_key', false );

		$this->assertCount( 1, $result );

		$this->assertEquals( $value, reset( $result ) );
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_update_meta_with_several_values( $value ) {
		add_metadata( self::$type, self::$objects['en'], 'the_key', 'another_value' );
		add_metadata( self::$type, self::$objects['en'], 'the_key', 'yet_another_value' );

		$this->assertTrue( update_metadata( self::$type, self::$objects['en'], 'the_key', $value, 'another_value' ) );

		$this->assertEqualSetsWithIndex(
			array( $value, 'yet_another_value' ),
			get_metadata( self::$type, self::$objects['fr'], 'the_key', false )
		);
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_update_meta_by_mid( $value ) {
		$mid = add_metadata( self::$type, self::$objects['en'], 'the_key', 'to_be_updated' );

		$this->assertEquals( 'to_be_updated', get_metadata( self::$type, self::$objects['fr'], 'the_key', true ) );

		$this->assertTrue( update_metadata_by_mid( self::$type, $mid, $value ) );

		$result = get_metadata( self::$type, self::$objects['fr'], 'the_key', false );

		$this->assertCount( 1, $result );

		$this->assertEquals( $value, reset( $result ) );
	}
}
