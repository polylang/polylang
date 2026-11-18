<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

class Options_Domains_Test extends TestCase {

	public function test_domains_omitted_when_language_is_not_set_from_domains() {
		$this->pll_admin->options['force_lang'] = 1;
		$this->pll_admin->options['domains']   = array(
			'en' => 'https://example.com',
			'fr' => 'https://fr.example.com',
		);

		$fields = $this->get_pll_options_fields();

		$this->assertArrayNotHasKey( 'domains', $fields, 'domains should be omitted when force_lang is not different domains.' );
	}

	public function test_domains_omitted_when_language_is_set_from_domains_and_value_empty() {
		$this->pll_admin->options['force_lang'] = 3;
		$this->pll_admin->options->reset( 'domains' );

		$fields = $this->get_pll_options_fields();

		$this->assertArrayNotHasKey( 'domains', $fields, 'Empty domains should not produce a field.' );
	}

	public function test_domains_shown_when_language_is_set_from_domains() {
		$domains = array(
			'en' => 'https://example.com',
			'fr' => 'https://fr.example.com',
		);

		$this->pll_admin->options['force_lang'] = 3;
		$this->pll_admin->options['domains']   = $domains;

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field( $fields, 'domains', 'Domains', $domains );
	}
}
