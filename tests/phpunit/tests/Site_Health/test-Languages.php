<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

use WP_Debug_Data;

class Languages_Test extends TestCase {

	public function test_info_languages_term_props() {
		$info = $this->site_health->info_languages( array() );

		$this->assertIsArray( $info, 'Info should be an array.' );
		$this->assertCount( 2, $info, 'Info should contain two elements.' );

		$this->assertArrayHasKey( 'pll_language_en', $info, 'Info should have an entry with pll_language_en key.' );
		$this->assertArrayHasKey( 'pll_language_fr', $info, 'Info should have an entry with pll_language_fr key.' );
		$this->assertArrayHasKey( 'term_props', $info['pll_language_en']['fields'], 'Info should have an entry with term_props key.' );

		$info = $info['pll_language_en']['fields'];
		$this->assertSame( 'term_props', $info['term_props']['label'], 'The label of the term_props entry should be term_props' );

		$this->assertIsArray( $info['term_props']['value'], 'This should be an array' );
		$this->assertCount( 6, $info['term_props']['value'], 'This should contain 6 elements.' );

		$this->assertArrayHasKey( 'term_language/term_id', $info['term_props']['value'], 'The value of the term_props entry should have an entry with term_language/term_id key.' );
		$this->assertArrayHasKey( 'term_language/term_taxonomy_id', $info['term_props']['value'], 'The value of the term_props entry should have an entry with term_language/term_taxonomy_id key.' );
		$this->assertArrayHasKey( 'term_language/count', $info['term_props']['value'], 'The value of the term_props entry should have an entry with term_language/count key.' );
		$this->assertArrayHasKey( 'language/term_id', $info['term_props']['value'], 'The value of the term_props entry should have an entry with language/term_id key.' );
		$this->assertArrayHasKey( 'language/term_taxonomy_id', $info['term_props']['value'], 'The value of the term_props entry should have an entry with language/term_taxonomy_id key.' );
		$this->assertArrayHasKey( 'language/count', $info['term_props']['value'], 'The value of the term_props entry should have an entry with language/count key.' );

		$en = $this->pll_admin->model->get_language( 'en' );
		$this->assertSame( $en->get_tax_prop( 'language', 'term_id' ), $info['term_props']['value']['language/term_id'] );
		$this->assertSame( $en->get_tax_prop( 'language', 'term_taxonomy_id' ), $info['term_props']['value']['language/term_taxonomy_id'] );
		$this->assertSame( $en->get_tax_prop( 'language', 'count' ), $info['term_props']['value']['language/count'] );
		$this->assertSame( $en->get_tax_prop( 'term_language', 'term_id' ), $info['term_props']['value']['term_language/term_id'] );
		$this->assertSame( $en->get_tax_prop( 'term_language', 'term_taxonomy_id' ), $info['term_props']['value']['term_language/term_taxonomy_id'] );
		$this->assertSame( $en->get_tax_prop( 'term_language', 'count' ), $info['term_props']['value']['term_language/count'] );
	}

	public function test_info_languages_preserves_existing_debug_info() {
		require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';

		$debug_info_with_pll = WP_Debug_Data::debug_data();

		remove_filter( 'debug_information', array( $this->site_health, 'info_languages' ) );

		$debug_info_without_pll = WP_Debug_Data::debug_data();

		$this->assertArrayHasKey( 'pll_language_en', $debug_info_with_pll );
		$this->assertArrayHasKey( 'pll_language_fr', $debug_info_with_pll );

		unset( $debug_info_with_pll['pll_language_en'], $debug_info_with_pll['pll_language_fr'] );

		$this->assertSameSetsWithIndex(
			$debug_info_without_pll,
			$debug_info_with_pll,
			'Existing debug information should be preserved unchanged.'
		);
	}

	public function test_info_languages_contains_expected_fields() {
		$en = $this->pll_admin->model->get_language( 'en' );

		$debug_info = $this->site_health->info_languages( array() );
		$fields = $debug_info['pll_language_en']['fields'];

		$this->assertSame( 'name', $fields['name']['label'], 'Label should equal the key name.' );
		$this->assertSame( $en->name, $fields['name']['value'], 'Name value should match the language object.' );
		$this->assertSame( (string) $en->term_id, (string) $fields['term_id']['value'], 'Term_id value should match the language object.' );
		$this->assertSame( $en->slug, $fields['slug']['value'], 'Slug value should match the language object.' );
		$this->assertSame( 'order', $fields['term_group']['label'], 'Label should equal the key name.' );
		$this->assertSame( (string) $en->term_group, (string) $fields['term_group']['value'], 'Term_group value should match the language object.' );
		foreach ( array( 'flag', 'host', 'taxonomy', 'description', 'parent', 'filter', 'custom_flag' ) as $excluded_key ) {
			$this->assertArrayNotHasKey( $excluded_key, $fields, "Excluded key \"$excluded_key\" should not be present." );
		}
	}

	public function test_info_languages_returns_one_entry_per_language() {
		$debug_info = $this->site_health->info_languages( array() );

		$this->assertCount( 2, $debug_info, 'Result should contain one entry per configured language.' );
		$this->assertArrayHasKey( 'pll_language_en', $debug_info, 'Result should contain an entry for English.' );
		$this->assertArrayHasKey( 'pll_language_fr', $debug_info, 'Result should contain an entry for French.' );
	}

	public function test_info_languages_returns_empty_array_when_no_language_is_set() {
		self::delete_all_languages();

		try {
			$debug_info = $this->site_health->info_languages( array() );

			$this->assertEmpty( $debug_info, 'Result should be empty when no language is set.' );
		} finally {
			// Cleanup: always restore languages, even if the assertion above fails, so subsequent tests in the class aren't affected.
			self::create_language( 'en_US' );
			self::create_language( 'fr_FR' );
		}
	}
}
