<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

use WP_Error;

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

	public function test_info_languages_is_applied() {
		// Prevent the external WordPress.org request performed by WP_Debug_Data,
		// which is unrelated to the behavior tested here.
		$filter = function () {
			return new WP_Error( 'test_http_request', 'HTTP request disabled for this test.' );
		};

		add_filter( 'pre_http_request', $filter );

		try {
			$debug_info = $this->get_debug_info();
		} finally {
			remove_filter( 'pre_http_request', $filter );
		}

		$this->assertArrayHasKey( 'pll_language_en', $debug_info );
		$this->assertArrayHasKey( 'pll_language_fr', $debug_info );
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
			self::factory()->language->create_many( 2 );
		}
	}

	public function test_info_languages_preserves_existing_debug_info() {
		$debug_info = array(
			'pre_existing_data' => array(
				'label'       => 'Title of this data',
				'description' => 'Description',
				'fields'      => array(
					'name' => array(
						'label' => 'Name',
						'value' => 'Field name',
					),
				),
			),
		);

		$result = $this->site_health->info_languages( $debug_info );

		$this->assertCount( 3, $result, 'Result should contain one entry per configured language, plus the pre-existing data.' );
		$this->assertSame(
			$debug_info['pre_existing_data'],
			$result['pre_existing_data'],
			'Pre-existing data should be preserved unchanged.'
		);
		$this->assertSame( 'Language: English - en', $result['pll_language_en']['label'], 'New language entry should be added correctly.' );
	}
}
