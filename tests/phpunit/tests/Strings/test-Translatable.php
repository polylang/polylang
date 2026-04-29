<?php

namespace WP_Syntex\Polylang\Tests\Strings;

use PLL_Model;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
use Translation_Entry;
use WP_Syntex\Polylang\Strings\Translatable;

/**
 * @group strings
 */
class Translatable_Test extends PLL_UnitTestCase {
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
	}

	public function set_up() {
		parent::set_up();

		$this->pll_model = new PLL_Model(
			self::create_options(
				array(
					'default_lang' => 'en',
				)
			)
		);
	}

	public function test_constructor_with_default_context() {
		$translatable = new Translatable( 'Test', 'test_name' );

		$this->assertSame( 'Polylang', $translatable->get_context() );
	}

	public function test_constructor_with_multiline_true() {
		$translatable = new Translatable( 'Test', 'test_name', 'test_context', null, true );

		$this->assertTrue( $translatable->is_multiline() );
	}

	public function test_get_id_returns_md5_hash() {
		$source = 'Test string';
		$context = 'test_context';
		$expected_id = md5( $source . $context );

		$translatable = new Translatable( $source, 'name', $context );

		$this->assertSame( $expected_id, $translatable->get_id() );
	}

	public function test_get_id_is_unique_for_different_sources() {
		$translatable1 = new Translatable( 'Source1', 'name1', 'context' );
		$translatable2 = new Translatable( 'Source2', 'name2', 'context' );

		$this->assertNotSame( $translatable1->get_id(), $translatable2->get_id() );
	}

	public function test_get_id_is_unique_for_different_contexts() {
		$translatable1 = new Translatable( 'Source', 'name1', 'context1' );
		$translatable2 = new Translatable( 'Source', 'name2', 'context2' );

		$this->assertNotSame( $translatable1->get_id(), $translatable2->get_id() );
	}

	public function test_get_id_is_same_for_same_source_and_context() {
		$translatable1 = new Translatable( 'Source', 'name1', 'context' );
		$translatable2 = new Translatable( 'Source', 'name2', 'context' );

		$this->assertSame( $translatable1->get_id(), $translatable2->get_id() );
	}

	public function test_get_name() {
		$translatable = new Translatable( 'Source', 'test_name', 'context' );

		$this->assertSame( 'test_name', $translatable->get_name() );
	}

	public function test_get_source() {
		$translatable = new Translatable( 'Test source', 'name', 'context' );

		$this->assertSame( 'Test source', $translatable->get_source() );
	}

	public function test_get_translation_returns_source_when_no_translation_set() {
		$translatable = new Translatable( 'Source', 'name', 'context' );
		$language = $this->pll_model->languages->get( 'en' );

		$this->assertSame( 'Source', $translatable->get_translation( $language ) );
	}

	public function test_get_translation_returns_set_translation() {
		$translatable = new Translatable( 'Source', 'name', 'context' );
		$language = $this->pll_model->languages->get( 'en' );
		$translatable->set_translation( $language, 'Test translation' );

		$this->assertSame( 'Test translation', $translatable->get_translation( $language ) );
	}

	public function test_get_context() {
		$translatable = new Translatable( 'Source', 'name', 'test_context' );

		$this->assertSame( 'test_context', $translatable->get_context() );
	}

	public function test_get_previous_translation_returns_empty_string_when_no_translation_set() {
		$translatable = new Translatable( 'Source', 'name', 'context' );
		$language     = $this->pll_model->languages->get( 'en' );

		$this->assertIsString( $translatable->get_previous_translation( $language ) );
		$this->assertEmpty( $translatable->get_previous_translation( $language ) );
	}

	public function test_set_translation_updates_translation() {
		$translatable = new Translatable( 'Source', 'name', 'context' );
		$language = $this->pll_model->languages->get( 'en' );

		$translatable->set_translation( $language, 'Old translation' );
		$translatable->set_translation( $language, 'New translation' );

		$this->assertSame( 'New translation', $translatable->get_translation( $language ) );
	}

	public function test_set_translation_updates_previous_translation() {
		$translatable = new Translatable( 'Source', 'name', 'context' );
		$language = $this->pll_model->languages->get( 'en' );

		$translatable->set_translation( $language, 'Old translation' );
		$translatable->set_translation( $language, 'New translation' );

		$this->assertSame( 'Old translation', $translatable->get_previous_translation( $language ) );
	}

	public function test_set_translation_for_multiple_languages() {
		$translatable = new Translatable( 'Source', 'name', 'context' );
		$en = $this->pll_model->languages->get( 'en' );
		$fr = $this->pll_model->languages->get( 'fr' );

		$translatable->set_translation( $en, 'English translation' );
		$translatable->set_translation( $fr, 'French translation' );

		$this->assertSame( 'English translation', $translatable->get_translation( $en ) );
		$this->assertSame( 'French translation', $translatable->get_translation( $fr ) );
	}

	public function test_is_multiline_returns_false_by_default() {
		$translatable = new Translatable( 'Source', 'name', 'context' );

		$this->assertFalse( $translatable->is_multiline() );
	}

	public function test_is_multiline_returns_true_when_set() {
		$translatable = new Translatable( 'Source', 'name', 'context', null, true );

		$this->assertTrue( $translatable->is_multiline() );
	}

	public function test_get_entry_returns_correct_translation_entry() {
		$translatable = new Translatable( 'Test source', 'name', 'test_context' );
		$language = $this->pll_model->languages->get( 'en' );
		$translatable->set_translation( $language, 'Test translation' );

		$entry = $translatable->get_entry( $language );

		$this->assertInstanceOf( Translation_Entry::class, $entry );
		$this->assertSame( 'Test source', $entry->singular );
		$this->assertSame( array( 'Test translation' ), $entry->translations );
		$this->assertSame( 'test_context', $entry->context );
	}

	public function test_sanitize_returns_translation_for_non_matching_name() {
		$translatable = new Translatable( 'Source', 'name', 'context' );

		$result = $translatable->sanitize( 'Input', 'different_name', 'context', 'Original', 'Previous' );

		$this->assertSame( 'Input', $result );
	}

	public function test_sanitize_returns_translation_for_non_matching_context() {
		$translatable = new Translatable( 'Source', 'name', 'context' );

		$result = $translatable->sanitize( 'Input', 'name', 'different_context', 'Original', 'Previous' );

		$this->assertSame( 'Input', $result );
	}

	public function test_sanitize_returns_translation_when_unchanged() {
		$translatable = new Translatable( 'Source', 'name', 'context' );

		$result = $translatable->sanitize( '  Same  ', 'name', 'context', 'Original', '  Same  ' );

		$this->assertSame( '  Same  ', $result );
	}

	public function test_sanitize_with_custom_callback() {
		$custom_callback = function ( $string ) {
			return strtoupper( $string );
		};

		$translatable = new Translatable( 'Source', 'name', 'context', $custom_callback );
		$result = $translatable->sanitize( 'hello', 'name', 'context', 'Original', 'Previous' );

		$this->assertSame( 'HELLO', $result );
	}
}
