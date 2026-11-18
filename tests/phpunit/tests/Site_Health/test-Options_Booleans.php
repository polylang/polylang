<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

class Options_Booleans_Test extends TestCase {

	/**
	 * @testWith [true, "1: Hide URL language information for default language"]
	 *           [false, "0: Display URL language information for default language"]
	 *
	 * @param bool   $stored          Stored option value.
	 * @param string $expected_value  Expected Site Health field value.
	 * @return void
	 */
	public function test_hide_default( bool $stored, string $expected_value ) {
		$this->pll_admin->options['hide_default'] = $stored;

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field( $fields, 'hide_default', 'Hide_default', $expected_value );
	}

	/**
	 * @testWith [true, "1: Remove `/language/` in pretty permalinks"]
	 *           [false, "0: Keep `/language/` in pretty permalinks"]
	 *
	 * @param bool   $stored          Stored option value.
	 * @param string $expected_value  Expected Site Health field value.
	 * @return void
	 */
	public function test_rewrite( bool $stored, string $expected_value ) {
		$this->pll_admin->options['rewrite'] = $stored;

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field( $fields, 'rewrite', 'Rewrite', $expected_value );
	}

	/**
	 * @testWith [true, "1: The front page URL contains the language code instead of the page name or page id"]
	 *           [false, "0: The front page URL contains the page name or page id instead of the language code"]
	 *
	 * @param bool   $stored          Stored option value.
	 * @param string $expected_value  Expected Site Health field value.
	 * @return void
	 */
	public function test_redirect_lang( bool $stored, string $expected_value ) {
		$this->pll_admin->options['redirect_lang'] = $stored;

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field( $fields, 'redirect_lang', 'Redirect_lang', $expected_value );
	}

	/**
	 * @testWith [true, "1: Detect browser language activated"]
	 *           [false, "0: Detect browser language deactivated"]
	 *
	 * @param bool   $stored          Stored option value.
	 * @param string $expected_value  Expected Site Health field value.
	 * @return void
	 */
	public function test_browser( bool $stored, string $expected_value ) {
		$this->pll_admin->options['browser'] = $stored;

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field( $fields, 'browser', 'Browser', $expected_value );
	}

	/**
	 * @testWith [true, "1: The media are translated"]
	 *           [false, "0: The media are not translated"]
	 *
	 * @param bool   $stored          Stored option value.
	 * @param string $expected_value  Expected Site Health field value.
	 * @return void
	 */
	public function test_media_support( bool $stored, string $expected_value ) {
		$this->pll_admin->options['media_support'] = $stored;

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field( $fields, 'media_support', 'Media_support', $expected_value );
	}
}