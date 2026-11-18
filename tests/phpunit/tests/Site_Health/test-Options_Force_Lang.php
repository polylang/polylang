<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

class Options_Force_Lang_Test extends TestCase {

	public function test_force_lang_from_content() {
		$this->enable_language_from_content();
		$this->pll_admin->options['force_lang'] = 0;

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field(
			$fields,
			'force_lang',
			'Force_lang',
			'0: The language is set from content'
		);
	}

	public function test_force_lang_from_directory() {
		$this->pll_admin->options['force_lang'] = 1;

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field(
			$fields,
			'force_lang',
			'Force_lang',
			'1: The language is set from the directory name in pretty permalinks'
		);
	}

	public function test_force_lang_from_subdomain() {
		$this->pll_admin->options['force_lang'] = 2;

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field(
			$fields,
			'force_lang',
			'Force_lang',
			'2: The language is set from the subdomain name in pretty permalinks'
		);
	}

	public function test_force_lang_from_domains() {
		$this->pll_admin->options['force_lang'] = 3;

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field(
			$fields,
			'force_lang',
			'Force_lang',
			'3: The language is set from different domains'
		);
	}

	public function test_force_lang_unknown_value_omits_field() {
		$this->set_option_value_via_reflection( 'force_lang', 99 );

		$fields = $this->get_pll_options_fields();

		$this->assertArrayNotHasKey( 'force_lang', $fields, 'Unknown force_lang value should not produce a field.' );
	}
}
