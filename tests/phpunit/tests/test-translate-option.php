<?php

/**
 * This class is only for updating options.
 * Registering and translating options is already tested in WPML_Config_Test
 */
class Translate_Option_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once POLYLANG_DIR . '/include/api.php';
		$GLOBALS['polylang'] = &self::$polylang;
	}

	protected function add_string_translations( $lang, $translations ) {
		$language = self::$polylang->model->get_language( $lang );
		$mo = new PLL_MO();
		$mo->import_from_db( $language );
		foreach ( $translations as $original => $translation ) {
			$mo->add_entry( $mo->make_entry( $original, $translation ) );
		}
		$mo->export_to_db( $language );
	}

	protected function prepare_option_simple() {
		add_option( 'my_option', 'val' );

		self::$polylang = new PLL_Admin( self::$polylang->links_model );
		new PLL_Translate_Option( 'my_option' );
	}

	function test_update_option_simple() {
		$this->prepare_option_simple();

		$languages = array( 'en', 'fr' );
		foreach ( $languages as $lang ) {
			$this->add_string_translations( $lang, array( 'val' => 'val_' . $lang ) );
		}

		// Quick check.
		self::$polylang->load_strings_translations( 'en' );
		$this->assertEquals( 'val_en', get_option( 'my_option' ) );
		self::$polylang->load_strings_translations( 'fr' );
		$this->assertEquals( 'val_fr', get_option( 'my_option' ) );

		update_option( 'my_option', 'new_val' );

		self::$polylang->load_strings_translations( 'en' );
		$this->assertEquals( 'val_en', get_option( 'my_option' ) );
		self::$polylang->load_strings_translations( 'fr' );
		$this->assertEquals( 'val_fr', get_option( 'my_option' ) );
	}

	function test_update_option_simple_with_no_translation() {
		$this->prepare_option_simple();
		$this->add_string_translations( 'en', array( 'val' => 'val' ) );

		// Quick check.
		self::$polylang->load_strings_translations( 'en' );
		$this->assertEquals( 'val', get_option( 'my_option' ) );

		update_option( 'my_option', 'new_val' );

		self::$polylang->load_strings_translations( 'en' );
		$this->assertEquals( 'new_val', get_option( 'my_option' ) );
	}

	function test_update_option_simple_when_filtered() {
		$this->prepare_option_simple();
		$this->add_string_translations( 'en', array( 'val' => 'val_en' ) );

		PLL()->curlang = self::$polylang->model->get_language( 'en' );
		update_option( 'my_option', 'val_en' );

		$language = self::$polylang->model->get_language( 'en' );
		$mo = new PLL_MO();
		$mo->import_from_db( $language );
		$this->assertArrayHasKey( 'val', $mo->entries );
		$this->assertArrayNotHasKey( 'val_en', $mo->entries );
	}

	protected function prepare_option_multiple( $method = 'ARRAY' ) {
		$options = array(
			'option_name_1'   => 'val1',
			'options_group_1' => array(
				'sub_option_name_11' => 'val11',
			),
		);

		if ( 'OBJECT' === $method ) {
			$options = json_decode( json_encode( $options ) ); // Recursively converts the arrays to objects.
		}

		add_option( 'my_options', $options );
	}

	protected function register_option_multiple() {
		$keys = array(
			'option_name_1'   => 1,
			'options_group_1' => array(
				'sub_option_name_11' => 1,
			),
		);

		self::$polylang = new PLL_Admin( self::$polylang->links_model );
		new PLL_Translate_Option( 'my_options', $keys );
	}

	protected function register_option_multiple_with_wildcard() {
		$keys = array(
			'option_name_1' => 1,
			'options_*'     => 1,
		);

		self::$polylang = new PLL_Admin( self::$polylang->links_model );
		new PLL_Translate_Option( 'my_options', $keys );
	}


	protected function translate_strings() {
		$languages = array( 'en', 'fr' );

		foreach ( $languages as $lang ) {
			$translations = array(
				'val1'  => 'val1_' . $lang,
				'val11' => 'val11_' . $lang,
			);
			$this->add_string_translations( $lang, $translations );
		}
	}

	protected function update_option_with_new_val( $method = 'ARRAY' ) {
		$options = array(
			'option_name_1'   => 'new_val1',
			'options_group_1' => array(
				'sub_option_name_11' => 'new_val11',
			),
		);

		if ( 'OBJECT' === $method ) {
			$options = json_decode( json_encode( $options ) ); // Recursively converts the arrays to objects.
		}

		update_option( 'my_options', $options );
	}

	protected function _test_update_option_multiple() {
		$this->translate_strings();

		$languages = array( 'en', 'fr' );

		// Quick check.
		foreach ( $languages as $lang ) {
			self::$polylang->load_strings_translations( $lang );
			$options = get_option( 'my_options' );
			$this->assertEquals( 'val1_' . $lang, $options['option_name_1'] );
			$this->assertEquals( 'val11_' . $lang, $options['options_group_1']['sub_option_name_11'] );
		}

		$this->update_option_with_new_val( 'ARRAY' );

		foreach ( $languages as $lang ) {
			self::$polylang->load_strings_translations( $lang );
			$options = get_option( 'my_options' );
			$this->assertEquals( 'val1_' . $lang, $options['option_name_1'] );
			$this->assertEquals( 'val11_' . $lang, $options['options_group_1']['sub_option_name_11'] );
		}
	}

	function test_update_option_multiple() {
		$this->prepare_option_multiple( 'ARRAY' );
		$this->register_option_multiple();
		$this->_test_update_option_multiple();
	}

	function test_update_option_multiple_with_wildcard() {
		$this->prepare_option_multiple( 'ARRAY' );
		$this->register_option_multiple_with_wildcard();
		$this->_test_update_option_multiple();
	}

	protected function _test_update_object_option_multiple() {
		$this->translate_strings();
		$this->update_option_with_new_val( 'OBJECT' );

		$languages = array( 'en', 'fr' );

		foreach ( $languages as $lang ) {
			self::$polylang->load_strings_translations( $lang );
			$options = get_option( 'my_options' );
			$this->assertEquals( 'val1_' . $lang, $options->option_name_1 );
			$this->assertEquals( 'val11_' . $lang, $options->options_group_1->sub_option_name_11 );
		}
	}

	function test_update_object_option_multiple() {
		$this->prepare_option_multiple( 'OBJECT' );
		$this->register_option_multiple();
		$this->_test_update_object_option_multiple();
	}

	function test_update_object_option_multiple_with_wildcard() {
		$this->prepare_option_multiple( 'OBJECT' );
		$this->register_option_multiple_with_wildcard();
		$this->_test_update_object_option_multiple();
	}

	protected function do_no_translate_strings() {
		$translations = array(
			'val1'  => 'val1',
			'val11' => 'val11',
		);
		$this->add_string_translations( 'en', $translations );
	}

	protected function _test_update_option_multiple_with_no_translation() {
		$this->do_no_translate_strings();

		$this->update_option_with_new_val( 'ARRAY' );

		self::$polylang->load_strings_translations( 'en' );
		$options = get_option( 'my_options' );
		$this->assertEquals( 'new_val1', $options['option_name_1'] );
		$this->assertEquals( 'new_val11', $options['options_group_1']['sub_option_name_11'] );
	}

	function test_update_option_multiple_with_no_translation() {
		$this->prepare_option_multiple( 'ARRAY' );
		$this->register_option_multiple();
		$this->_test_update_option_multiple_with_no_translation();
	}

	function test_update_option_multiple_with_no_translation_with_wildcard() {
		$this->prepare_option_multiple( 'ARRAY' );
		$this->register_option_multiple_with_wildcard();
		$this->_test_update_option_multiple_with_no_translation();
	}

	function _test_update_object_option_multiple_with_no_translation() {
		$this->do_no_translate_strings();

		$this->update_option_with_new_val( 'OBJECT' );

		self::$polylang->load_strings_translations( 'en' );
		$options = get_option( 'my_options' );
		$this->assertEquals( 'new_val1', $options->option_name_1 );
		$this->assertEquals( 'new_val11', $options->options_group_1->sub_option_name_11 );
	}

	function test_update_object_option_multiple_with_no_translation() {
		$this->prepare_option_multiple( 'OBJECT' );
		$this->register_option_multiple();
		$this->_test_update_object_option_multiple_with_no_translation();
	}

	function test_update_object_option_multiple_with_no_translation_with_wildcard() {
		$this->prepare_option_multiple( 'OBJECT' );
		$this->register_option_multiple_with_wildcard();
		$this->_test_update_object_option_multiple_with_no_translation();
	}

	protected function _test_update_option_with_translated_val( $method = 'ARRAY' ) {
		$this->translate_strings();

		$options = array(
			'option_name_1'   => 'val1_en',
			'options_group_1' => array(
				'sub_option_name_11' => 'val11_en',
			),
		);

		if ( 'OBJECT' === $method ) {
			$options = json_decode( json_encode( $options ) ); // Recursively converts the arrays to objects.
		}

		PLL()->curlang = self::$polylang->model->get_language( 'en' );
		update_option( 'my_options', $options );

		$language = self::$polylang->model->get_language( 'en' );
		$mo = new PLL_MO();
		$mo->import_from_db( $language );
		$this->assertArrayHasKey( 'val1', $mo->entries );
		$this->assertArrayNotHasKey( 'val1_en', $mo->entries );
		$this->assertArrayHasKey( 'val11', $mo->entries );
		$this->assertArrayNotHasKey( 'val11_en', $mo->entries );
	}

	function test_update_option_multiple_when_filtered() {
		$this->prepare_option_multiple( 'ARRAY' );
		$this->register_option_multiple();
		$this->_test_update_option_with_translated_val( 'ARRAY' );
	}

	function test_update_option_multiple_when_filtered_with_wildcard() {
		$this->prepare_option_multiple( 'ARRAY' );
		$this->register_option_multiple_with_wildcard();
		$this->_test_update_option_with_translated_val( 'ARRAY' );
	}

	function test_update_object_option_multiple_when_filtered() {
		$this->prepare_option_multiple( 'OBJECT' );
		$this->register_option_multiple();
		$this->_test_update_option_with_translated_val( 'OBJECT' );
	}

	function test_update_object_option_multiple_when_filtered_with_wildcard() {
		$this->prepare_option_multiple( 'OBJECT' );
		$this->register_option_multiple_with_wildcard();
		$this->_test_update_option_with_translated_val( 'OBJECT' );
	}
}
