<?php

namespace WP_Syntex\Polylang_Pro\Tests\Integration\modules\Meta;

trait Copy {
	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_copy_meta( $value ) {
		add_metadata( self::$type, self::$objects['en'], 'the_key', $value );

		$this->get_metas_object()->copy( self::$objects['en'], self::$objects['fr'], 'fr' );

		$this->assertEquals( $value, get_metadata( self::$type, self::$objects['fr'], 'the_key', true ) );
	}

	/**
	 * @dataProvider meta_values_provider
	 *
	 * @param mixed $value The value to add to the meta.
	 * @return void
	 */
	public function test_copy_meta_with_several_values( $value ) {
		add_metadata( self::$type, self::$objects['en'], 'the_key', 'another_value' );
		add_metadata( self::$type, self::$objects['en'], 'the_key', $value );

		$this->get_metas_object()->copy( self::$objects['en'], self::$objects['fr'], 'fr' );

		$this->assertEquals(
			array(
				'another_value',
				$value,
			),
			get_metadata( self::$type, self::$objects['fr'], 'the_key', false )
		);
	}
}
