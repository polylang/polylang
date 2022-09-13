<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Translated_Object_UnitTestCase
 *
 * Testes PLL_Translated_Object methods tha are common to PLL_Translated_Post and PLL_Translated_Term.
 */
class PLL_Translated_Object_UnitTestCase extends PLL_UnitTestCase {
	/**
	 * @covers PLL_Translated_Object::save_translations()
	 *
	 * @param PLL_Translated_Object $translated_object An instance of a subclass of PLL_Translated_Object.
	 */
	public function dont_save_translations_with_incorrect_language( $translated_object ) {
		$property = new ReflectionProperty( get_class( $translated_object ), 'type' );
		$property->setAccessible( true );
		$object_type = $property->getValue( $translated_object );

		$id = self::factory()->$object_type->create();
		$translated_object->set_language( $id, 'en' );

		$translated_object->save_translations( $id, array( 'fr' => $id ) );

		$this->assertNotContains( 'fr', array_keys( $translated_object->get_translations( $id ) ) );
	}
}
