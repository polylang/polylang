<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

use ReflectionClass;
use WP_Syntex\Polylang\Options\Options;

class Options_Test extends TestCase {

	public function set_up() {
		parent::set_up();

		remove_filter( 'pll_get_post_types', '__return_empty_array' );
		remove_filter( 'pll_get_taxonomies', '__return_empty_array' );

		_unregister_post_type( 'trcpt' );
		unregister_taxonomy( 'trtax' );
	}

	public function test_info_options_section_structure() {
		$debug_info = $this->get_debug_info();

		$this->assertArrayHasKey( 'pll_options', $debug_info, 'Debug information should contain pll_options.' );
		$this->assertSame(
			sprintf( '%s options', POLYLANG ),
			$debug_info['pll_options']['label'],
			'pll_options label should match.'
		);
		$this->assertIsArray( $debug_info['pll_options']['fields'], 'pll_options fields should be an array.' );
	}

	public function test_info_options_preserves_existing_debug_info() {
		$debug_info = $this->get_debug_info();

		$this->assert_pre_existing_data_preserved( $debug_info );
	}

	public function test_cpt_and_taxonomies_fields_from_model() {
		$fields = $this->get_pll_options_fields();
		$model  = $this->pll_admin->model;

		$this->assert_option_field(
			$fields,
			'cpt',
			'Translated post types',
			implode( ', ', $model->get_translated_post_types() )
		);
		$this->assert_option_field(
			$fields,
			'taxonomies',
			'Translated taxonomies',
			implode( ', ', $model->get_translated_taxonomies() )
		);
	}

	public function test_cpt_and_taxonomies_fields_include_custom_types() {
		register_post_type( 'trcpt' ); // phpcs:ignore WordPress.NamingConventions.ValidPostTypeSlug
		register_taxonomy( 'trtax', 'post' ); // phpcs:ignore WordPress.NamingConventions.ValidTaxonomySlug

		$this->pll_admin->options['post_types'] = array( 'trcpt' );
		$this->pll_admin->options['taxonomies'] = array( 'trtax' );
		$this->pll_admin->model->cache->clean( 'post_types' );
		$this->pll_admin->model->cache->clean( 'taxonomies' );

		$fields = $this->get_pll_options_fields();

		$this->assertStringContainsString( 'trcpt', (string) $fields['cpt']['value'], 'cpt field should include the custom post type.' );
		$this->assertStringContainsString( 'trtax', (string) $fields['taxonomies']['value'], 'taxonomies field should include the custom taxonomy.' );
	}

	public function test_cpt_and_taxonomies_fields_omitted_when_none_translated() {
		add_filter( 'pll_get_post_types', '__return_empty_array' );
		add_filter( 'pll_get_taxonomies', '__return_empty_array' );
		$this->pll_admin->model->cache->clean( 'post_types' );
		$this->pll_admin->model->cache->clean( 'taxonomies' );

		$fields = $this->get_pll_options_fields();

		$this->assertArrayNotHasKey( 'cpt', $fields, 'cpt should be omitted when no post types are translated.' );
		$this->assertArrayNotHasKey( 'taxonomies', $fields, 'taxonomies should be omitted when no taxonomies are translated.' );
	}

	public function test_post_types_and_taxonomies_options_have_no_own_fields() {
		$fields = $this->get_pll_options_fields();

		$this->assertArrayNotHasKey( 'post_types', $fields, 'post_types option should not appear as its own field.' );
	}

	public function test_unregistered_raw_option_is_skipped() {
		$options    = $this->pll_admin->model->options;
		$reflection = new ReflectionClass( Options::class );
		$property   = $reflection->getProperty( 'options' );

		version_compare( PHP_VERSION, '8.1', '<' ) && $property->setAccessible( true );

		$blog_id = get_current_blog_id();
		/** @var array $all_options */
		$all_options = $property->getValue( $options );

		$all_options[ $blog_id ]['uninstall'] = 1;
		$property->setValue( $options, $all_options );

		$fields = $this->get_pll_options_fields();

		$this->assertArrayNotHasKey( 'uninstall', $fields, 'Raw non-option values should not appear in site health.' );
	}
}
