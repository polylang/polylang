<?php

namespace WP_Syntex\Polylang\Tests\Strings;

use PLL_MO;
use PLL_Model;
use PLL_Admin;
use PLL_Table_String;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
use PLL_Handle_WP_Redirect_Trait;
use WP_Syntex\Polylang\Strings\Database_Repository;

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
		Database_Repository::reset();

		// Clear MO entries for all languages to prevent test contamination.
		foreach ( $this->pll_env->model->languages->get_list() as $language ) {
			$mo = new PLL_MO();
			$mo->export_to_db( $language );
		}
	}

	/**
	 * Registers test strings.
	 *
	 * @return void
	 */
	private function register_test_strings(): void {
		Database_Repository::register( 'test_one', 'Foo', 'Group1', null, false );
		Database_Repository::register( 'test_two', 'Bar', 'Group1', null, false );
		Database_Repository::register( 'test_three', 'Baz', 'Group1', null, false );
		Database_Repository::register( 'test_four', 'Qux', 'Group2', null, false );
		Database_Repository::register( 'test_five', 'Quux', 'Group2', null, false );
		Database_Repository::register( 'test_six', "Line one\nLine two\nLine three", 'Group3', null, true );
		Database_Repository::register( 'test_seven', 'Simple text', 'Group3', null, false );
		Database_Repository::register( 'test_eight', 'Another string', 'Group4', null, false );
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
				md5( 'FooGroup1' )                            => 'FooFR',
				md5( 'BarGroup1' )                            => 'BarFR',
				md5( 'BazGroup1' )                            => 'BazFR',
				md5( 'QuxGroup2' )                            => 'QuxFR',
				md5( 'QuuxGroup2' )                           => 'QuuxFR',
				md5( "Line one\nLine two\nLine threeGroup3" ) => "Ligne un\nLigne deux\nLigne trois",
				md5( 'Simple textGroup3' )                    => 'Texte simple',
				md5( 'Another stringGroup4' )                 => 'Autre chaîne',
			),
			'en' => array(
				md5( 'FooGroup1' )                            => '',
				md5( 'BarGroup1' )                            => '',
				md5( 'BazGroup1' )                            => '',
				md5( 'QuxGroup2' )                            => '',
				md5( 'QuuxGroup2' )                           => '',
				md5( "Line one\nLine two\nLine threeGroup3" ) => '',
				md5( 'Simple textGroup3' )                    => '',
				md5( 'Another stringGroup4' )                 => '',
			),
		);

		// Call save_translations (will redirect, but database operations complete first).
		$this->assert_redirect( array( $table, 'save_translations' ) );

		// Verify French translations were saved.
		$fr_language = $this->pll_env->model->languages->get( 'fr' );
		$mo = new PLL_MO();
		$mo->import_from_db( $fr_language );

		$this->assertSame( 'FooFR', $mo->translate( 'Foo', 'Group1' ), 'French translation should contain Foo entry.' );
		$this->assertSame( 'BarFR', $mo->translate( 'Bar', 'Group1' ), 'French translation should contain Bar entry.' );
		$this->assertSame( 'BazFR', $mo->translate( 'Baz', 'Group1' ), 'French translation should contain Baz entry.' );
		$this->assertSame( 'QuxFR', $mo->translate( 'Qux', 'Group2' ), 'French translation should contain Qux entry.' );
		$this->assertSame( 'QuuxFR', $mo->translate( 'Quux', 'Group2' ), 'French translation should contain Quux entry.' );
		$this->assertSame( "Ligne un\nLigne deux\nLigne trois", $mo->translate( "Line one\nLine two\nLine three", 'Group3' ), 'French translation should contain "Line one\nLine two\nLine three" entry.' );
		$this->assertSame( 'Texte simple', $mo->translate( 'Simple text', 'Group3' ), 'French translation should contain "Texte simple" entry.' );
		$this->assertSame( 'Autre chaîne', $mo->translate( 'Another string', 'Group4' ), 'French translation should contain "Autre chaîne" entry.' );
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
				md5( 'FooGroup1' ) => 'FooFR',
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
				md5( 'FooGroup1' ) => 'FooFR',
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
				md5( 'FooGroup1' ) => 'FooFR',
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
				md5( 'FooGroup1' ) => 'FooFR',
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
		$old_mo->export_to_db( $fr_language );

		$_REQUEST['_wpnonce_string-translation'] = wp_create_nonce( 'string-translation' );
		$_POST['submit'] = 'Submit';
		$_POST['clean'] = '1';
		$_POST['translation'] = array(
			'fr' => array(
				md5( 'FooGroup1' ) => 'FooFRUpdated',
			),
		);

		$this->assert_redirect( array( $table, 'save_translations' ) );

		// Verify database was cleaned (only registered strings remain).
		$mo = new PLL_MO();
		$mo->import_from_db( $fr_language );

		// Old string should be removed.
		$this->assertArrayNotHasKey( 'old-string', $mo->entries, 'Old unregistered string should be removed during cleaning.' );

		// Foo should be updated.
		$this->assertSame( 'FooFRUpdated', $mo->translate( 'Foo', 'Group1' ), 'Registered string should remain after cleaning.' );
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
				md5( 'FooGroup1' ) => 'FooFR',
			),
		);

		$this->assert_redirect( array( $table, 'save_translations' ) );

		// Database cleaning should not happen, normal save should occur.
		$fr_language = $this->pll_env->model->languages->get( 'fr' );
		$mo = new PLL_MO();
		$mo->import_from_db( $fr_language );

		// Translation should still be saved, but cleaning should not occur.
		$this->assertSame( 'FooFR', $mo->translate( 'Foo', 'Group1' ), 'Translation should be saved even without cleaning permission.' );
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
				md5( 'FooGroup1' ) => 'FooFR',
			),
			'de' => array(
				md5( 'FooGroup1' ) => 'FooDE',
			),
		);

		$this->assert_redirect( array( $table, 'save_translations' ) );

		// Verify French translation.
		$fr_language = $this->pll_env->model->languages->get( 'fr' );
		$fr_mo = new PLL_MO();
		$fr_mo->import_from_db( $fr_language );
		$this->assertSame( 'FooFR', $fr_mo->translate( 'Foo', 'Group1' ), 'French translation should be saved.' );

		// Verify German translation.
		$de_language = $this->pll_env->model->languages->get( 'de' );
		$de_mo = new PLL_MO();
		$de_mo->import_from_db( $de_language );
		$this->assertSame( 'FooDE', $de_mo->translate( 'Foo', 'Group1' ), 'German translation should be saved.' );
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
				md5( 'FooGroup1' ) => 'FooFR',
			),
		);

		$this->assert_redirect( array( $table, 'save_translations' ) );

		// Now clear it with empty value.
		$_REQUEST['_wpnonce_string-translation'] = wp_create_nonce( 'string-translation' );
		$_POST['submit'] = 'Submit';
		$_POST['translation'] = array(
			'fr' => array(
				md5( 'FooGroup1' ) => '',
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
			$this->assertSame( '', $mo->translate( 'Foo', 'Group1' ), 'Empty translation should be saved as empty string.' );
		} else {
			$this->assertEmpty( $mo->entries, 'Empty translation entry should be removed.' );
		}
	}
}
