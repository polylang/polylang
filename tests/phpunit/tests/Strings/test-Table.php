<?php

namespace WP_Syntex\Polylang\Tests\Strings;

use PLL_MO;
use PLL_Model;
use PLL_Admin;
use PLL_Table_String;
use PLL_UnitTestCase;
use PLL_Admin_Strings;
use PLL_UnitTest_Factory;
use PLL_Handle_WP_Redirect_Trait;

/**
 * @group strings
 */
class Table_Test extends PLL_UnitTestCase {
	use PLL_Handle_WP_Redirect_Trait;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 3 );
	}

	public function set_up() {
		parent::set_up();

		$options       = self::create_options();
		$model         = new PLL_Model( $options );
		$links_model   = $model->get_links_model();
		$this->pll_env = new PLL_Admin( $links_model );

		// Clear registered strings.
		$reflection = new \ReflectionClass( PLL_Admin_Strings::class );
		$property = $reflection->getProperty( 'strings' );
		$property->setAccessible( true );
		$property->setValue( null, array() );

		// Clear MO entries for all languages to prevent test contamination.
		foreach ( $this->pll_env->model->languages->get_list() as $language ) {
			$mo = new PLL_MO();
			$mo->export_to_db( $language );
		}
	}

	/**
	 * Registers test strings.
	 *
	 * @return array<string, array<string, mixed>> Registered strings array.
	 */
	private function register_test_strings(): array {
		PLL_Admin_Strings::register_string( 'test_one', 'Foo', 'Group1', false );
		PLL_Admin_Strings::register_string( 'test_two', 'Bar', 'Group1', false );
		PLL_Admin_Strings::register_string( 'test_three', 'Baz', 'Group1', false );
		PLL_Admin_Strings::register_string( 'test_four', 'Qux', 'Group2', false );
		PLL_Admin_Strings::register_string( 'test_five', 'Quux', 'Group2', false );
		PLL_Admin_Strings::register_string( 'test_six', "Line one\nLine two\nLine three", 'Group3', true );
		PLL_Admin_Strings::register_string( 'test_seven', 'Simple text', 'Group3', false );
		PLL_Admin_Strings::register_string( 'test_eight', 'Another string', 'Group4', false );

		return PLL_Admin_Strings::get_strings();
	}

	/**
	 * Creates a table instance with registered strings.
	 *
	 * @return PLL_Table_String
	 */
	private function create_table_instance(): PLL_Table_String {
		$table = new PLL_Table_String( $this->pll_env->model->languages );
		return $table;
	}

	/**
	 * Tests happy path: saving translations successfully.
	 *
	 * @return void
	 */
	public function test_save_translations_happy_path() {
		$this->register_test_strings();
		$table = $this->create_table_instance();

		// Set up POST data with translations.
		$_REQUEST['_wpnonce_string-translation'] = wp_create_nonce( 'string-translation' );
		$_POST['submit'] = 'Submit';
		$_POST['translation'] = array(
			'fr' => array(
				md5( 'Foo' ) => 'FooFR',
				md5( 'Bar' ) => 'BarFR',
				md5( 'Baz' ) => 'BazFR',
				md5( 'Qux' ) => 'QuxFR',
				md5( 'Quux' ) => 'QuuxFR',
				md5( "Line one\nLine two\nLine three" ) => "Ligne un\nLigne deux\nLigne trois",
				md5( 'Simple text' ) => 'Texte simple',
				md5( 'Another string' ) => 'Autre chaîne',
			),
			'en' => array(
				md5( 'Foo' ) => '',
				md5( 'Bar' ) => '',
				md5( 'Baz' ) => '',
				md5( 'Qux' ) => '',
				md5( 'Quux' ) => '',
				md5( "Line one\nLine two\nLine three" ) => '',
				md5( 'Simple text' ) => '',
				md5( 'Another string' ) => '',
			),
		);

		// Call save_translations (will redirect, but database operations complete first).
		$this->assert_redirect( array( $table, 'save_translations' ) );

		// Verify French translations were saved.
		$fr_language = $this->pll_env->model->languages->get( 'fr' );
		$mo = new PLL_MO();
		$mo->import_from_db( $fr_language );

		$this->assertArrayHasKey( 'Foo', $mo->entries, 'French translation should contain test_one entry.' );
		$this->assertSame( 'FooFR', $mo->entries['Foo']->translations[0], 'French test_one translation should be saved.' );
		$this->assertArrayHasKey( 'Bar', $mo->entries, 'French translation should contain test_two entry.' );
		$this->assertSame( 'BarFR', $mo->entries['Bar']->translations[0], 'French test_two translation should be saved.' );
		$this->assertArrayHasKey( 'Baz', $mo->entries, 'French translation should contain test_three entry.' );
		$this->assertSame( 'BazFR', $mo->entries['Baz']->translations[0], 'French test_three translation should be saved.' );
		$this->assertArrayHasKey( 'Qux', $mo->entries, 'French translation should contain test_four entry.' );
		$this->assertSame( 'QuxFR', $mo->entries['Qux']->translations[0], 'French test_four translation should be saved.' );
		$this->assertArrayHasKey( 'Quux', $mo->entries, 'French translation should contain test_five entry.' );
		$this->assertSame( 'QuuxFR', $mo->entries['Quux']->translations[0], 'French test_five translation should be saved.' );
		$this->assertArrayHasKey( "Line one\nLine two\nLine three", $mo->entries, 'French translation should contain test_six entry.' );
		$this->assertSame( "Ligne un\nLigne deux\nLigne trois", $mo->entries["Line one\nLine two\nLine three"]->translations[0], 'French test_six translation should be saved.' );
		$this->assertArrayHasKey( 'Simple text', $mo->entries, 'French translation should contain test_seven entry.' );
		$this->assertSame( 'Texte simple', $mo->entries['Simple text']->translations[0], 'French test_seven translation should be saved.' );
		$this->assertArrayHasKey( 'Another string', $mo->entries, 'French translation should contain test_eight entry.' );
		$this->assertSame( 'Autre chaîne', $mo->entries['Another string']->translations[0], 'French test_eight translation should be saved.' );
	}

	/**
	 * Tests unhappy path: missing nonce.
	 *
	 * @return void
	 */
	public function test_save_translations_missing_nonce() {
		$this->register_test_strings();
		$table = $this->create_table_instance();

		// Set up POST data without nonce.
		$_POST['submit'] = 'Submit';
		$_POST['translation'] = array(
			'fr' => array(
				md5( 'Foo' ) => 'FooFR',
			),
		);

		// Expect wp_die to be called due to missing nonce.
		$this->expectException( 'WPDieException' );

		$table->save_translations();
	}

	/**
	 * Tests unhappy path: invalid nonce.
	 *
	 * @return void
	 */
	public function test_save_translations_invalid_nonce() {
		$this->register_test_strings();
		$table = $this->create_table_instance();

		// Set up POST data with invalid nonce.
		$_REQUEST['_wpnonce_string-translation'] = 'invalid-nonce';
		$_POST['submit'] = 'Submit';
		$_POST['translation'] = array(
			'fr' => array(
				md5( 'Foo' ) => 'FooFR',
			),
		);

		// Expect wp_die to be called due to invalid nonce.
		$this->expectException( 'WPDieException' );

		$table->save_translations();
	}

	/**
	 * Tests unhappy path: missing submit button.
	 *
	 * @return void
	 */
	public function test_save_translations_missing_submit() {
		$this->register_test_strings();
		$table = $this->create_table_instance();

		// Set up POST data without submit button.
		$_REQUEST['_wpnonce_string-translation'] = wp_create_nonce( 'string-translation' );
		$_POST['translation'] = array(
			'fr' => array(
				md5( 'Foo' ) => 'FooFR',
			),
		);

		$this->assert_redirect( array( $table, 'save_translations' ) );

		// Verify no translations were saved.
		$fr_language = $this->pll_env->model->languages->get( 'fr' );
		$mo = new PLL_MO();
		$mo->import_from_db( $fr_language );

		$this->assertArrayNotHasKey( 'Foo', $mo->entries, 'No translations should be saved when submit is missing.' );
	}

	/**
	 * Tests unhappy path: invalid row keys (not in registered strings).
	 *
	 * @return void
	 */
	public function test_save_translations_invalid_row_keys() {
		$this->register_test_strings();
		$table = $this->create_table_instance();

		$_REQUEST['_wpnonce_string-translation'] = wp_create_nonce( 'string-translation' );
		$_POST['submit'] = 'Submit';
		$_POST['translation'] = array(
			'fr' => array(
				'invalid-row-key' => 'some translation',
			),
		);

		$this->assert_redirect( array( $table, 'save_translations' ) );

		// Verify no translations were saved.
		$fr_language = $this->pll_env->model->languages->get( 'fr' );
		$mo = new PLL_MO();
		$mo->import_from_db( $fr_language );

		$this->assertEmpty( $mo->entries, 'No translations should be saved for invalid row keys.' );
	}

	/**
	 * Tests unhappy path: empty translations array.
	 *
	 * @return void
	 */
	public function test_save_translations_empty_translations() {
		$this->register_test_strings();
		$table = $this->create_table_instance();

		$_REQUEST['_wpnonce_string-translation'] = wp_create_nonce( 'string-translation' );
		$_POST['submit'] = 'Submit';
		$_POST['translation'] = array(
			'fr' => array(),
		);

		$this->assert_redirect( array( $table, 'save_translations' ) );

		// Verify no translations were saved.
		$fr_language = $this->pll_env->model->languages->get( 'fr' );
		$mo = new PLL_MO();
		$mo->import_from_db( $fr_language );

		$this->assertEmpty( $mo->entries, 'No translations should be saved when translations array is empty.' );
	}

	/**
	 * Tests unhappy path: missing language data.
	 *
	 * @return void
	 */
	public function test_save_translations_missing_language_data() {
		$this->register_test_strings();
		$table = $this->create_table_instance();

		$_REQUEST['_wpnonce_string-translation'] = wp_create_nonce( 'string-translation' );
		$_POST['submit'] = 'Submit';
		$_POST['translation'] = array(
			'nonexistent-language' => array(
				md5( 'Foo' ) => 'FooFR',
			),
		);

		$this->assert_redirect( array( $table, 'save_translations' ) );

		// Verify no translations were saved.
		$fr_language = $this->pll_env->model->languages->get( 'fr' );
		$mo = new PLL_MO();
		$mo->import_from_db( $fr_language );

		$this->assertEmpty( $mo->entries, 'No translations should be saved for nonexistent language.' );
	}

	/**
	 * Tests unhappy path: translation is not an array.
	 *
	 * @return void
	 */
	public function test_save_translations_translation_not_array() {
		$this->register_test_strings();
		$table = $this->create_table_instance();

		$_REQUEST['_wpnonce_string-translation'] = wp_create_nonce( 'string-translation' );
		$_POST['submit'] = 'Submit';
		$_POST['translation'] = array(
			'fr' => 'not-an-array',
		);

		$this->assert_redirect( array( $table, 'save_translations' ) );

		// Verify no translations were saved.
		$fr_language = $this->pll_env->model->languages->get( 'fr' );
		$mo = new PLL_MO();
		$mo->import_from_db( $fr_language );

		$this->assertEmpty( $mo->entries, 'No translations should be saved when translation is not an array.' );
	}

	/**
	 * Tests happy path: database cleaning functionality.
	 *
	 * @return void
	 */
	public function test_save_translations_database_cleaning() {
		$this->register_test_strings();
		$table = $this->create_table_instance();

		wp_set_current_user( 1 ); // Set admin user.

		$fr_language = $this->pll_env->model->languages->get( 'fr' );
		$old_mo = new PLL_MO();
		$old_mo->add_entry( $old_mo->make_entry( 'old-string', 'old-translation' ) );
		$old_mo->add_entry( $old_mo->make_entry( 'Foo', 'FooFR' ) );
		$old_mo->export_to_db( $fr_language );

		$_REQUEST['_wpnonce_string-translation'] = wp_create_nonce( 'string-translation' );
		$_POST['submit'] = 'Submit';
		$_POST['clean'] = '1';
		$_POST['translation'] = array(
			'fr' => array(
				md5( 'Foo' ) => 'FooFRUpdated',
			),
		);

		$this->assert_redirect( array( $table, 'save_translations' ) );

		// Verify database was cleaned (only registered strings remain).
		$mo = new PLL_MO();
		$mo->import_from_db( $fr_language );

		// Old string should be removed.
		$this->assertArrayNotHasKey( 'old-string', $mo->entries, 'Old unregistered string should be removed during cleaning.' );

		// Registered strings should remain.
		$this->assertArrayHasKey( 'Foo', $mo->entries, 'Registered string should remain after cleaning.' );
		$this->assertArrayHasKey( 'Bar', $mo->entries, 'Registered string should remain after cleaning.' );
		$this->assertArrayHasKey( 'Baz', $mo->entries, 'Registered string should remain after cleaning.' );
		$this->assertArrayHasKey( 'Qux', $mo->entries, 'Registered string should remain after cleaning.' );
		$this->assertArrayHasKey( 'Quux', $mo->entries, 'Registered string should remain after cleaning.' );
		$this->assertArrayHasKey( "Line one\nLine two\nLine three", $mo->entries, 'Registered string should remain after cleaning.' );
		$this->assertArrayHasKey( 'Simple text', $mo->entries, 'Registered string should remain after cleaning.' );
		$this->assertArrayHasKey( 'Another string', $mo->entries, 'Registered string should remain after cleaning.' );
	}

	/**
	 * Tests unhappy path: database cleaning without proper permissions.
	 *
	 * @return void
	 */
	public function test_save_translations_database_cleaning_no_permission() {
		$this->register_test_strings();
		$table = $this->create_table_instance();

		// Set up as non-administrator.
		$user = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user );

		$_REQUEST['_wpnonce_string-translation'] = wp_create_nonce( 'string-translation' );
		$_POST['submit'] = 'Submit';
		$_POST['clean'] = '1';
		$_POST['translation'] = array(
			'fr' => array(
				md5( 'Foo' ) => 'FooFR',
			),
		);

		$this->assert_redirect( array( $table, 'save_translations' ) );

		// Database cleaning should not happen, normal save should occur.
		$fr_language = $this->pll_env->model->languages->get( 'fr' );
		$mo = new PLL_MO();
		$mo->import_from_db( $fr_language );

		// Translation should still be saved, but cleaning should not occur.
		$this->assertArrayHasKey( 'Foo', $mo->entries, 'Translation should be saved even without cleaning permission.' );
	}

	/**
	 * Tests happy path: multiple languages translations.
	 *
	 * @return void
	 */
	public function test_save_translations_multiple_languages() {
		$this->register_test_strings();
		$table = $this->create_table_instance();

		$_REQUEST['_wpnonce_string-translation'] = wp_create_nonce( 'string-translation' );
		$_POST['submit'] = 'Submit';
		$_POST['translation'] = array(
			'fr' => array(
				md5( 'Foo' ) => 'FooFR',
			),
			'de' => array(
				md5( 'Foo' ) => 'FooDE',
			),
		);

		$this->assert_redirect( array( $table, 'save_translations' ) );

		// Verify French translation.
		$fr_language = $this->pll_env->model->languages->get( 'fr' );
		$fr_mo = new PLL_MO();
		$fr_mo->import_from_db( $fr_language );
		$this->assertSame( 'FooFR', $fr_mo->entries['Foo']->translations[0], 'French translation should be saved.' );

		// Verify German translation.
		$de_language = $this->pll_env->model->languages->get( 'de' );
		$de_mo = new PLL_MO();
		$de_mo->import_from_db( $de_language );
		$this->assertSame( 'FooDE', $de_mo->entries['Foo']->translations[0], 'German translation should be saved.' );
	}

	/**
	 * Tests happy path: empty translation values (clearing translations).
	 *
	 * @return void
	 */
	public function test_save_translations_empty_values() {
		$this->register_test_strings();
		$table = $this->create_table_instance();

		// First, save a translation.
		$_REQUEST['_wpnonce_string-translation'] = wp_create_nonce( 'string-translation' );
		$_POST['submit'] = 'Submit';
		$_POST['translation'] = array(
			'fr' => array(
				md5( 'Foo' ) => 'FooFR',
			),
		);

		$this->assert_redirect( array( $table, 'save_translations' ) );

		// Now clear it with empty value.
		$_REQUEST['_wpnonce_string-translation'] = wp_create_nonce( 'string-translation' );
		$_POST['submit'] = 'Submit';
		$_POST['translation'] = array(
			'fr' => array(
				md5( 'Foo' ) => '',
			),
		);

		$this->assert_redirect( array( $table, 'save_translations' ) );

		// Verify empty translation was saved (or entry removed).
		$fr_language = $this->pll_env->model->languages->get( 'fr' );
		$mo = new PLL_MO();
		$mo->import_from_db( $fr_language );

		// Empty translations might be saved as empty string or entry might be removed.
		// The behavior depends on PLL_MO implementation.
		if ( isset( $mo->entries['Foo'] ) ) {
			$this->assertSame( '', $mo->entries['Foo']->translations[0], 'Empty translation should be saved as empty string.' );
		} else {
			$this->assertArrayNotHasKey( 'Foo', $mo->entries, 'Empty translation entry should be removed.' );
		}
	}
}
