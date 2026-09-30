<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

use WP_Error;
use WP_Debug_Data;

class Languages_Test extends TestCase {

	public function set_up() {
		parent::set_up();

		// Prevent the external WordPress.org request performed by WP_Debug_Data,
		// which is unrelated to the behavior tested here.
		add_filter(
			'pre_http_request',
			function () {
				return new WP_Error(
					'test_http_request',
					'HTTP request disabled for this test.'
				);
			}
		);
		require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
	}

	public function test_info_languages_contains_expected_fields() {
		$debug_info = WP_Debug_Data::debug_data();

		$this->assertIsArray( $debug_info, 'Info should be an array.' );

		$this->assertArrayHasKey( 'pll_language_en', $debug_info, 'Info should have an entry with pll_language_en key.' );
		$this->assertArrayHasKey( 'pll_language_fr', $debug_info, 'Info should have an entry with pll_language_fr key.' );

		$fields = $debug_info['pll_language_en']['fields'];
		$this->assertArrayHasKey( 'term_props', $fields, 'Info should have an entry with term_props key.' );
		$this->assertSame( 'term_props', $fields['term_props']['label'], 'The label of the term_props entry should be term_props' );

		$term_props = $fields['term_props']['value'];
		$this->assertIsArray( $term_props, 'This should be an array' );
		$this->assertCount( 6, $term_props, 'This should contain 6 elements.' );

		$this->assertArrayHasKey( 'term_language/term_id', $term_props, 'The value of the term_props entry should have an entry with term_language/term_id key.' );
		$this->assertArrayHasKey( 'term_language/term_taxonomy_id', $term_props, 'The value of the term_props entry should have an entry with term_language/term_taxonomy_id key.' );
		$this->assertArrayHasKey( 'term_language/count', $term_props, 'The value of the term_props entry should have an entry with term_language/count key.' );
		$this->assertArrayHasKey( 'language/term_id', $term_props, 'The value of the term_props entry should have an entry with language/term_id key.' );
		$this->assertArrayHasKey( 'language/term_taxonomy_id', $term_props, 'The value of the term_props entry should have an entry with language/term_taxonomy_id key.' );
		$this->assertArrayHasKey( 'language/count', $term_props, 'The value of the term_props entry should have an entry with language/count key.' );

		$en = $this->pll_admin->model->get_language( 'en' );
		$this->assertSame( $en->get_tax_prop( 'language', 'term_id' ), $term_props['language/term_id'] );
		$this->assertSame( $en->get_tax_prop( 'language', 'term_taxonomy_id' ), $term_props['language/term_taxonomy_id'] );
		$this->assertSame( $en->get_tax_prop( 'language', 'count' ), $term_props['language/count'] );
		$this->assertSame( $en->get_tax_prop( 'term_language', 'term_id' ), $term_props['term_language/term_id'] );
		$this->assertSame( $en->get_tax_prop( 'term_language', 'term_taxonomy_id' ), $term_props['term_language/term_taxonomy_id'] );
		$this->assertSame( $en->get_tax_prop( 'term_language', 'count' ), $term_props['term_language/count'] );

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

	public function test_info_languages_preserves_existing_debug_info() {
		$pre_existing_data = array(
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
		add_filter(
			'debug_information',
			function ( $debug_info ) use ( $pre_existing_data ) {
				$debug_info['pre_existing_data'] = $pre_existing_data;

				return $debug_info;
			}
		);

		$debug_info = WP_Debug_Data::debug_data();

		$this->assertSame(
			$debug_info['pre_existing_data'],
			$debug_info['pre_existing_data'],
			'Pre-existing data should be preserved unchanged.'
		);
		$this->assertSame(
			$pre_existing_data,
			$debug_info['pre_existing_data'],
			'Pre-existing data should be preserved unchanged.'
		);
		$this->assertSame( 'Language: English - en', $debug_info['pll_language_en']['label'], 'New language entry should be added correctly.' );
	}
}
