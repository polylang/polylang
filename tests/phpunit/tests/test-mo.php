<?php


class MO_Test extends PLL_UnitTestCase {

	/**
	 * @PLL_Language
	 */
	private static $language_fr;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::$language_fr = self::$polylang->model->get_language( 'fr' );
	}

	public function test_save_multiline_string() {
		$mo = new PLL_MO();
		$source_string = "This string contains a line feed.\nIt expands on two lines.";
		$mo->add_entry(
			$mo->make_entry(
				$source_string,
				"Cette chaîne contient un retour à la ligne.\nElle s'étend sur deux lignes."
			)
		);
		$mo->export_to_db( self::$language_fr );

		$saved_mo = new PLL_MO();
		$saved_mo->import_from_db( self::$language_fr );
		$translation = $saved_mo->translate( $source_string );

		$expected_translation = 'Cette chaîne contient un retour à la ligne.
Elle s\'étend sur deux lignes.';
		$this->assertEquals( $expected_translation, $translation );
	}

}
