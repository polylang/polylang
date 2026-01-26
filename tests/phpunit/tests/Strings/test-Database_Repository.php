<?php

namespace WP_Syntex\Polylang\Tests\Strings;

use PLL_MO;
use PLL_Model;
use PLL_UnitTestCase;
use PLL_Admin_Strings;
use PLL_UnitTest_Factory;
use WP_Syntex\Polylang\Strings\Collection;
use WP_Syntex\Polylang\Strings\Database_Repository;
use WP_Syntex\Polylang\Strings\Translatable;

/**
 * @group strings
 */
class Database_Repository_Test extends PLL_UnitTestCase {
	use \PLL_MO_Trait;

	/**
	 * @var Database_Repository
	 */
	private $repository;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
	}

	public function set_up() {
		parent::set_up();

		$options          = self::create_options();
		$this->pll_model  = new PLL_Model( $options );
		$this->repository = new Database_Repository();
	}

	public function tear_down() {
		// Reset the static strings in PLL_Admin_Strings.
		$reflection = new \ReflectionClass( PLL_Admin_Strings::class );
		$property = $reflection->getProperty( 'strings' );
		$property->setAccessible( true );
		$property->setValue( null, array() );

		// Reset the static strings in PLL_WPML_Compat.
		if ( class_exists( 'PLL_WPML_Compat' ) ) {
			$reflection = new \ReflectionClass( \PLL_WPML_Compat::class );
			$property = $reflection->getProperty( 'strings' );
			$property->setAccessible( true );
			$property->setValue( null, array() );

			// Also reset the singleton instance.
			$instance_property = $reflection->getProperty( 'instance' );
			$instance_property->setAccessible( true );
			$instance_property->setValue( null, null );

			// Clean the option.
			delete_option( 'polylang_wpml_strings' );
		}

		// Flush the PLL_MO cache.
		$this->flush_pll_mo_cache( $this->pll_model->get_languages_list() );

		parent::tear_down();
	}

	public function test_save_should_register_all_strings_from_collection() {
		$translatable1 = new Translatable( 'String One', 'name_one', 'ContextA' );
		$translatable2 = new Translatable( 'String Two', 'name_two', 'ContextB', true );
		$collection    = new Collection( array( $translatable1, $translatable2 ) );

		$this->repository->save( $collection, $this->pll_model->get_language( 'en' ) );

		$strings = PLL_Admin_Strings::get_strings();
		$this->assertArrayHasKey( md5( 'String One' ), $strings );
		$this->assertArrayHasKey( md5( 'String Two' ), $strings );
		$this->assertSame( 'name_one', $strings[ md5( 'String One' ) ]['name'] );
		$this->assertSame( 'name_two', $strings[ md5( 'String Two' ) ]['name'] );
		$this->assertSame( 'ContextA', $strings[ md5( 'String One' ) ]['context'] );
		$this->assertSame( 'ContextB', $strings[ md5( 'String Two' ) ]['context'] );
		$this->assertFalse( $strings[ md5( 'String One' ) ]['multiline'] );
		$this->assertTrue( $strings[ md5( 'String Two' ) ]['multiline'] );
	}

	public function test_save_with_empty_collection_should_not_register_any_strings() {
		$collection = new Collection();

		$this->repository->save( $collection, $this->pll_model->get_language( 'en' ) );

		$strings = PLL_Admin_Strings::get_strings();
		// Only default widget strings might be present, so we check no custom strings were added.
		$this->assertArrayNotHasKey( md5( 'String One' ), $strings );
	}

	public function test_find_all_should_return_registered_strings_as_collection() {
		PLL_Admin_Strings::register_string( 'name_one', 'String One', 'ContextA', false );
		PLL_Admin_Strings::register_string( 'name_two', 'String Two', 'ContextB', true );

		$collection = $this->repository->find_all();

		$this->assertInstanceOf( Collection::class, $collection );
		$this->assertTrue( $collection->has( md5( 'String One' ) ) );
		$this->assertTrue( $collection->has( md5( 'String Two' ) ) );

		$translatable1 = $collection->get( md5( 'String One' ) );
		$translatable2 = $collection->get( md5( 'String Two' ) );

		$this->assertSame( 'name_one', $translatable1->get_name() );
		$this->assertSame( 'String One', $translatable1->get_value() );
		$this->assertSame( 'ContextA', $translatable1->get_context() );
		$this->assertFalse( $translatable1->is_multiline() );

		$this->assertSame( 'name_two', $translatable2->get_name() );
		$this->assertSame( 'String Two', $translatable2->get_value() );
		$this->assertSame( 'ContextB', $translatable2->get_context() );
		$this->assertTrue( $translatable2->is_multiline() );
	}

	public function test_find_all_with_no_registered_strings_should_return_collection() {
		$collection = $this->repository->find_all();

		$this->assertInstanceOf( Collection::class, $collection );
	}

	public function test_remove_should_unregister_string_when_registered_via_icl_api() {
		// Initialize a minimal Polylang environment for icl_register_string to work.
		$options     = self::create_options( array( 'default_lang' => 'en' ) );
		$model       = new \PLL_Model( $options );
		$links_model = $model->get_links_model();
		$pll_admin   = new \PLL_Admin( $links_model );
		$pll_admin->init();

		// Make sure the WPML compat functions are loaded.
		if ( ! function_exists( 'icl_register_string' ) ) {
			require_once POLYLANG_DIR . '/modules/wpml/wpml-legacy-api.php';
		}

		icl_register_string( 'TestContext', 'my_icl_string', 'ICL String Value' );

		// Manually add the pll_get_strings filter since it might not have been added during instance creation.
		add_filter( 'pll_get_strings', array( \PLL_WPML_Compat::instance(), 'get_strings' ) );

		$id = md5( 'ICL String Value' );

		// Reset PLL_Admin_Strings static to force re-reading.
		$reflection = new \ReflectionClass( PLL_Admin_Strings::class );
		$property = $reflection->getProperty( 'strings' );
		$property->setAccessible( true );
		$property->setValue( null, array() );

		// Remove the string.
		$this->repository->remove_wpml_string( $id );

		// Verify the string is no longer registered.
		$mo =new PLL_MO();
		$mo->import_from_db( $this->pll_model->get_language( 'en' ) );
		$this->assertArrayNotHasKey( $id, $mo->entries );
	}

	public function test_save_translations_should_save_translations_for_language() {
		$language = $this->pll_model->get_language( 'en' );
		$this->assertNotNull( $language );

		$translatable1 = new Translatable( 'Hello', 'hello_string', 'TestContext' );
		$translatable2 = new Translatable( 'World', 'world_string', 'TestContext' );
		$collection    = new Collection( array( $translatable1, $translatable2 ) );

		$translatable1->set_value( 'Hello translated' );
		$translatable2->set_value( 'World translated' );

		$this->repository->save( $collection, $language );

		// Verify translations were saved.
		$mo = new PLL_MO();
		$mo->import_from_db( $language );

		$this->assertSame( 'Hello translated', $mo->translate( 'Hello' ) );
		$this->assertSame( 'World translated', $mo->translate( 'World' ) );
	}

	public function test_save_translations_should_skip_translatables_without_translations() {
		$language = $this->pll_model->get_language( 'en' );
		$this->assertNotNull( $language );

		$translatable1 = new Translatable( 'Hello', 'hello_string', 'TestContext' );
		$translatable2 = new Translatable( 'World', 'world_string', 'TestContext' );
		$collection    = new Collection( array( $translatable1, $translatable2 ) );

		// Only provide translation for one string.
		$translatable1->set_value( 'Hello translated' );

		$this->repository->save( $collection, $language );

		// Verify only the provided translation was saved.
		$mo = new PLL_MO();
		$mo->import_from_db( $language );

		$this->assertSame( 'Hello translated', $mo->translate( 'Hello' ) );
		$this->assertSame( 'World', $mo->translate( 'World' ) ); // Falls back to original.
	}

	public function test_save_translations_with_empty_translations_should_do_nothing() {
		$language = $this->pll_model->get_language( 'en' );
		$this->assertNotNull( $language );

		$translatable = new Translatable( 'Hello', 'hello_string', 'TestContext' );
		$collection   = new Collection( array( $translatable ) );

		$this->repository->save( $collection, $language );

		// Verify no translations were saved.
		$mo = new PLL_MO();
		$mo->import_from_db( $language );

		$this->assertSame( 'Hello', $mo->translate( 'Hello' ) ); // Falls back to original.
	}

	public function test_save_translations_with_empty_collection_should_do_nothing() {
		$language = $this->pll_model->get_language( 'en' );
		$this->assertNotNull( $language );

		$collection = new Collection();

		// This should not throw any errors.
		$this->repository->save( $collection, $language );

		$mo = new PLL_MO();
		$mo->import_from_db( $language );

		// No entries should exist.
		$this->assertEmpty( $mo->entries );
	}

	public function test_save_translations_should_apply_sanitization_filter() {
		$language = $this->pll_model->get_language( 'en' );
		$this->assertNotNull( $language );

		$translatable = new Translatable( 'Hello', 'hello_string', 'TestContext' );
		$collection   = new Collection( array( $translatable ) );

		$filter_called = false;
		add_filter(
			'pll_sanitize_string_translation',
			function ( $translation ) use ( &$filter_called ) {
				$filter_called = true;

				return strtoupper( $translation );
			}
		);

		$translatable->set_value( 'hello translated' );

		$this->repository->save( $collection, $language );

		$this->assertTrue( $filter_called );

		// Verify the filter was applied.
		$mo = new PLL_MO();
		$mo->import_from_db( $language );

		$this->assertSame( 'HELLO TRANSLATED', $mo->translate( 'Hello' ) );
	}

	public function test_save_translations_should_update_existing_translations() {
		$language = $this->pll_model->get_language( 'en' );
		$this->assertNotNull( $language );

		// First save initial translation.
		$mo = new PLL_MO();
		$mo->add_entry( $mo->make_entry( 'Hello', 'Hello initial' ) );
		$mo->export_to_db( $language );

		$translatable = new Translatable( 'Hello', 'hello_string', 'TestContext' );
		$collection   = new Collection( array( $translatable ) );

		// Update with new translation.
		$translatable->set_value( 'Hello updated' );

		$this->repository->save( $collection, $language );

		// Verify translation was updated.
		$mo = new PLL_MO();
		$mo->import_from_db( $language );

		$this->assertSame( 'Hello updated', $mo->translate( 'Hello' ) );
	}

	public function test_translations_should_be_language_specific() {
		$language_en = $this->pll_model->get_language( 'en' );
		$language_fr = $this->pll_model->get_language( 'fr' );
		$this->assertNotNull( $language_en );
		$this->assertNotNull( $language_fr );

		$translatable = new Translatable( 'Hello', 'hello_string', 'TestContext' );
		$collection   = new Collection( array( $translatable ) );

		// Save different translations for each language.
		$translatable->set_value( 'Hello in English' );
		$this->repository->save( $collection, $language_en );
		$translatable->set_value( 'Bonjour en français' );
		$this->repository->save( $collection, $language_fr );

		// Retrieve and verify translations.
		$mo = new PLL_MO();
		$mo->import_from_db( $language_en );
		$mo->import_from_db( $language_fr );

		$this->assertSame( 'Hello in English', $mo->translate( 'Hello' ) );
		$this->assertSame( 'Bonjour en français', $mo->translate( 'Hello' ) );
	}
}
