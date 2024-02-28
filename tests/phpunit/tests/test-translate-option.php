<?php

/**
 * This class is only for updating options.
 * Registering and translating options is already tested in WPML_Config_Test
 */
class Translate_Option_Test extends PLL_UnitTestCase {

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once POLYLANG_DIR . '/include/api.php';
	}

	public function set_up() {
		parent::set_up();

		$links_model = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );
		$GLOBALS['polylang'] = &$this->pll_admin;
	}

	public function tear_down() {
		parent::tear_down();

		unset( $GLOBALS['polylang'] );
	}

	protected function add_string_translations( $lang, $translations ) {
		$language = self::$model->get_language( $lang );
		$mo = new PLL_MO();
		$mo->import_from_db( $language );
		foreach ( $translations as $original => $translation ) {
			$mo->add_entry( $mo->make_entry( $original, $translation ) );
		}
		$mo->export_to_db( $language );
	}

	protected function prepare_option_simple() {
		add_option( 'my_option', 'val' );
		new PLL_Translate_Option( 'my_option' );
	}

	public function test_update_option_simple() {
		$this->prepare_option_simple();

		$languages = array( 'en', 'fr' );
		foreach ( $languages as $lang ) {
			$this->add_string_translations( $lang, array( 'val' => 'val_' . $lang ) );
		}

		// Quick check.
		$this->pll_admin->load_strings_translations( 'en' );
		$this->assertSame( 'val_en', get_option( 'my_option' ) );
		$this->pll_admin->load_strings_translations( 'fr' );
		$this->assertSame( 'val_fr', get_option( 'my_option' ) );

		update_option( 'my_option', 'new_val' );

		$this->pll_admin->load_strings_translations( 'en' );
		$this->assertSame( 'val_en', get_option( 'my_option' ) );
		$this->pll_admin->load_strings_translations( 'fr' );
		$this->assertSame( 'val_fr', get_option( 'my_option' ) );
	}

	public function test_update_option_simple_with_no_translation() {
		$this->prepare_option_simple();
		$this->add_string_translations( 'en', array( 'val' => 'val' ) );

		// Quick check.
		$this->pll_admin->load_strings_translations( 'en' );
		$this->assertSame( 'val', get_option( 'my_option' ) );

		update_option( 'my_option', 'new_val' );

		$this->pll_admin->load_strings_translations( 'en' );
		$this->assertSame( 'new_val', get_option( 'my_option' ) );
	}

	public function test_update_option_simple_when_filtered() {
		$this->prepare_option_simple();
		$this->add_string_translations( 'en', array( 'val' => 'val_en' ) );

		PLL()->curlang = self::$model->get_language( 'en' );
		update_option( 'my_option', 'val_en' );

		$language = self::$model->get_language( 'en' );
		$mo = new PLL_MO();
		$mo->import_from_db( $language );
		$this->assertArrayHasKey( 'val', $mo->entries );
		$this->assertArrayNotHasKey( 'val_en', $mo->entries );
	}

	protected function prepare_option_multiple( $method = 'ARRAY' ) {
		$options = array(
			'option_name_1'   => 'val1',
			'option$_group_1' => array(
				'sub_option_name_11' => 'val11',
			),
			'option$_'        => array(
				'sub_option_name_12' => 'val12',
			),
		);

		if ( 'OBJECT' === $method ) {
			$options = json_decode( wp_json_encode( $options ) ); // Recursively converts the arrays to objects.
		}

		add_option( 'my_options', $options );
	}

	protected function register_option_multiple() {
		$keys = array(
			'option_name_1'   => 1,
			'option$_group_1' => array(
				'sub_option_name_11' => 1,
			),
			'option$_'        => array(
				'sub_option_name_12' => 1,
			),
		);

		new PLL_Translate_Option( 'my_options', $keys );
	}

	protected function register_option_multiple_with_wildcard() {
		$keys = array(
			'option_name_1' => 1,
			'option$_*'     => 1, // Match `option$_group_1` and `option$_`.
		);

		new PLL_Translate_Option( 'my_options', $keys );
	}


	protected function translate_strings() {
		$languages = array( 'en', 'fr' );

		foreach ( $languages as $lang ) {
			$translations = array(
				'val1'  => 'val1_' . $lang,
				'val11' => 'val11_' . $lang,
				'val12' => 'val12_' . $lang,
			);
			$this->add_string_translations( $lang, $translations );
		}
	}

	protected function update_option_with_new_val( $method = 'ARRAY' ) {
		$options = array(
			'option_name_1'   => 'new_val1',
			'option$_group_1' => array(
				'sub_option_name_11' => 'new_val11',
			),
			'option$_'        => array(
				'sub_option_name_12' => 'new_val12',
			),
		);

		if ( 'OBJECT' === $method ) {
			$options = json_decode( wp_json_encode( $options ) ); // Recursively converts the arrays to objects.
		}

		update_option( 'my_options', $options );
	}

	protected function _test_update_option_multiple() {
		$this->translate_strings();

		$languages = array( 'en', 'fr' );

		// Quick check.
		foreach ( $languages as $lang ) {
			$this->pll_admin->load_strings_translations( $lang );
			$options = get_option( 'my_options' );
			$this->assertSame( 'val1_' . $lang, $options['option_name_1'] );
			$this->assertSame( 'val11_' . $lang, $options['option$_group_1']['sub_option_name_11'] );
			$this->assertSame( 'val12_' . $lang, $options['option$_']['sub_option_name_12'] );
		}

		$this->update_option_with_new_val( 'ARRAY' );

		foreach ( $languages as $lang ) {
			$this->pll_admin->load_strings_translations( $lang );
			$options = get_option( 'my_options' );
			$this->assertSame( 'val1_' . $lang, $options['option_name_1'] );
			$this->assertSame( 'val11_' . $lang, $options['option$_group_1']['sub_option_name_11'] );
			$this->assertSame( 'val12_' . $lang, $options['option$_']['sub_option_name_12'] );
		}
	}

	public function test_update_option_multiple() {
		$this->prepare_option_multiple( 'ARRAY' );
		$this->register_option_multiple();
		$this->_test_update_option_multiple();
	}

	public function test_update_option_multiple_with_wildcard() {
		$this->prepare_option_multiple( 'ARRAY' );
		$this->register_option_multiple_with_wildcard();
		$this->_test_update_option_multiple();
	}

	protected function _test_update_object_option_multiple() {
		$this->translate_strings();
		$this->update_option_with_new_val( 'OBJECT' );

		$languages = array( 'en', 'fr' );

		foreach ( $languages as $lang ) {
			$this->pll_admin->load_strings_translations( $lang );
			$options = get_option( 'my_options' );
			$this->assertSame( 'val1_' . $lang, $options->option_name_1 );
			$this->assertSame( 'val11_' . $lang, $options->{'option$_group_1'}->sub_option_name_11 );
		}
	}

	public function test_update_object_option_multiple() {
		$this->prepare_option_multiple( 'OBJECT' );
		$this->register_option_multiple();
		$this->_test_update_object_option_multiple();
	}

	public function test_update_object_option_multiple_with_wildcard() {
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

		$this->pll_admin->load_strings_translations( 'en' );
		$options = get_option( 'my_options' );
		$this->assertSame( 'new_val1', $options['option_name_1'] );
		$this->assertSame( 'new_val11', $options['option$_group_1']['sub_option_name_11'] );
		$this->assertSame( 'new_val12', $options['option$_']['sub_option_name_12'] );
	}

	public function test_update_option_multiple_with_no_translation() {
		$this->prepare_option_multiple( 'ARRAY' );
		$this->register_option_multiple();
		$this->_test_update_option_multiple_with_no_translation();
	}

	public function test_update_option_multiple_with_no_translation_with_wildcard() {
		$this->prepare_option_multiple( 'ARRAY' );
		$this->register_option_multiple_with_wildcard();
		$this->_test_update_option_multiple_with_no_translation();
	}

	protected function _test_update_object_option_multiple_with_no_translation() {
		$this->do_no_translate_strings();

		$this->update_option_with_new_val( 'OBJECT' );

		$this->pll_admin->load_strings_translations( 'en' );
		$options = get_option( 'my_options' );
		$this->assertSame( 'new_val1', $options->option_name_1 );
		$this->assertSame( 'new_val11', $options->{'option$_group_1'}->sub_option_name_11 );
	}

	public function test_update_object_option_multiple_with_no_translation() {
		$this->prepare_option_multiple( 'OBJECT' );
		$this->register_option_multiple();
		$this->_test_update_object_option_multiple_with_no_translation();
	}

	public function test_update_object_option_multiple_with_no_translation_with_wildcard() {
		$this->prepare_option_multiple( 'OBJECT' );
		$this->register_option_multiple_with_wildcard();
		$this->_test_update_object_option_multiple_with_no_translation();
	}

	protected function _test_update_option_with_translated_val( $method = 'ARRAY' ) {
		$this->translate_strings();

		$options = array(
			'option_name_1'   => 'val1_en',
			'option$_group_1' => array(
				'sub_option_name_11' => 'val11_en',
			),
			'option$_'        => array(
				'sub_option_name_12' => 'val12_en',
			),
		);

		if ( 'OBJECT' === $method ) {
			$options = json_decode( wp_json_encode( $options ) ); // Recursively converts the arrays to objects.
		}

		PLL()->curlang = self::$model->get_language( 'en' );
		update_option( 'my_options', $options );

		$language = self::$model->get_language( 'en' );
		$mo = new PLL_MO();
		$mo->import_from_db( $language );
		$this->assertArrayHasKey( 'val1', $mo->entries );
		$this->assertArrayNotHasKey( 'val1_en', $mo->entries );
		$this->assertArrayHasKey( 'val11', $mo->entries );
		$this->assertArrayNotHasKey( 'val11_en', $mo->entries );
		$this->assertArrayHasKey( 'val12', $mo->entries );
		$this->assertArrayNotHasKey( 'val12_en', $mo->entries );
	}

	public function test_update_option_multiple_when_filtered() {
		$this->prepare_option_multiple( 'ARRAY' );
		$this->register_option_multiple();
		$this->_test_update_option_with_translated_val( 'ARRAY' );
	}

	public function test_update_option_multiple_when_filtered_with_wildcard() {
		$this->prepare_option_multiple( 'ARRAY' );
		$this->register_option_multiple_with_wildcard();
		$this->_test_update_option_with_translated_val( 'ARRAY' );
	}

	public function test_update_object_option_multiple_when_filtered() {
		$this->prepare_option_multiple( 'OBJECT' );
		$this->register_option_multiple();
		$this->_test_update_option_with_translated_val( 'OBJECT' );
	}

	public function test_update_object_option_multiple_when_filtered_with_wildcard() {
		$this->prepare_option_multiple( 'OBJECT' );
		$this->register_option_multiple_with_wildcard();
		$this->_test_update_option_with_translated_val( 'OBJECT' );
	}

	public function test_update_same_option_with_two_objects() {
		add_option( 'my_option', 'val' );
		new PLL_Translate_Option( 'my_option' );
		new PLL_Translate_Option( 'my_option' );

		$this->add_string_translations( 'en', array( 'val' => 'val_en' ) );
		$this->add_string_translations( 'fr', array( 'val' => 'val_fr' ) );

		update_option( 'my_option', 'new_val' );

		$this->pll_admin->load_strings_translations( 'en' );
		$this->assertSame( 'val_en', get_option( 'my_option' ), 'Translations should be kept after option update' );

		$this->pll_admin->load_strings_translations( 'fr' );
		$this->assertSame( 'val_fr', get_option( 'my_option' ), 'Translations should be kept after option update' );
	}

	public function test_update_same_option_with_two_objects_when_filtered() {
		add_option( 'my_option', 'val' );
		new PLL_Translate_Option( 'my_option' );
		new PLL_Translate_Option( 'my_option' );

		$this->add_string_translations( 'en', array( 'val' => 'val_en' ) );
		$this->add_string_translations( 'fr', array( 'val' => 'val_fr' ) );

		PLL()->curlang = self::$model->get_language( 'en' );
		update_option( 'my_option', 'new_val' );

		$this->pll_admin->load_strings_translations( 'en' );
		$this->assertSame( 'new_val', get_option( 'my_option' ), 'Translation should be updated together with the option when the admin language filter is used' );

		$this->pll_admin->load_strings_translations( 'fr' );
		$this->assertSame( 'val_fr', get_option( 'my_option' ), 'Translations should be kept after option update' );
	}

	public function test_update_option_with_two_strings_in_two_objects() {
		$options = array(
			'key1' => 'val1',
			'key2' => 'val2',
		);
		add_option( 'my_options', $options );

		new PLL_Translate_Option( 'my_options', array( 'key1' => 1 ) );
		new PLL_Translate_Option( 'my_options', array( 'key2' => 1 ) );

		$this->add_string_translations( 'fr', array( 'val1' => 'val1_fr' ) );
		$this->add_string_translations( 'fr', array( 'val2' => 'val2_fr' ) );

		$options['key2'] = 'new_val2';
		update_option( 'my_options', $options );

		$this->pll_admin->load_strings_translations( 'fr' );
		$options = get_option( 'my_options' );
		$this->assertSame( 'val1_fr', $options['key1'], 'Translations should be kept after option update' );
		$this->assertSame( 'val2_fr', $options['key2'], 'Translations should be kept after option update' );
	}

	public function test_update_option_with_same_string_in_different_options() {
		add_option( 'my_option1', 'val' );
		add_option( 'my_option2', 'val' );
		new PLL_Translate_Option( 'my_option1' );
		new PLL_Translate_Option( 'my_option2' );

		$this->add_string_translations( 'fr', array( 'val' => 'val_fr' ) );

		update_option( 'my_option1', 'new_val' );

		$this->pll_admin->load_strings_translations( 'fr' );
		$this->assertSame( 'val_fr', get_option( 'my_option1' ), 'Translations should be kept after option update' );
		$this->assertSame( 'val_fr', get_option( 'my_option2' ), 'Translations should be kept after option update' );
	}
}
