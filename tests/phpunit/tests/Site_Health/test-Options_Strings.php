<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

class Options_Strings_Test extends TestCase {

	public function test_default_lang_shown() {
		$this->pll_admin->options['default_lang'] = 'en';

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field( $fields, 'default_lang', 'Default_lang', 'en' );
	}

	public function test_default_lang_omitted_when_empty() {
		$this->pll_admin->options->reset( 'default_lang' );

		$fields = $this->get_pll_options_fields();

		$this->assertArrayNotHasKey( 'default_lang', $fields, 'Empty default_lang should not produce a field.' );
	}

	public function test_version_shown() {
		$fields = $this->get_pll_options_fields();

		$this->assert_option_field(
			$fields,
			'version',
			'Version',
			$this->pll_admin->options['version']
		);
	}

	public function test_version_omitted_when_empty() {
		$this->set_option_value_via_reflection( 'version', '' );

		$fields = $this->get_pll_options_fields();

		$this->assertArrayNotHasKey( 'version', $fields, 'Empty version should not produce a field.' );
	}

	public function test_previous_version_first_activation() {
		$this->pll_admin->options->reset( 'previous_version' );

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field(
			$fields,
			'previous_version',
			'Previous_version',
			'This is the first activation'
		);
	}

	public function test_previous_version_shown() {
		$this->pll_admin->options['previous_version'] = '3.7.0';

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field( $fields, 'previous_version', 'Previous_version', '3.7.0' );
	}

	public function test_first_activation_formatted_date() {
		$timestamp = (int) $this->pll_admin->options['first_activation'];
		$expected  = wp_date( get_option( 'date_format' ), $timestamp );

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field( $fields, 'first_activation', 'First_activation', $expected );
	}
}
