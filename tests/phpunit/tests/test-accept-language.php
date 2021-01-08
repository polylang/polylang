<?php

class Accept_Language_Test extends PHPUnit_Framework_TestCase {
	public function test_parse_all_language_tags() {
		$http_header = 'en-GB, en-US;q=0.8,en';

		$accept_languages = PLL_Accept_Language::parse_accept_language_header( $http_header );

		$this->assertEquals( 3, count( $accept_languages ) );
	}

	public function test_parse_simple_language_subtag() {
		$http_header = 'en';

		$accept_languages = PLL_Accept_Language::parse_accept_language_header( $http_header );

		$this->assertEquals( 'en', $accept_languages[0]->get_subtag( 'language' ) );
	}

	public function test_parse_language_subtag_when_region_provided() {
		$http_header = 'en-GB';

		$accept_languages = PLL_Accept_Language::parse_accept_language_header( $http_header );

		$this->assertEquals( 'en', $accept_languages[0]->get_subtag( 'language' ) );
	}

	public function test_parse_region_subtag() {
		$http_header = 'zh-HK';

		$accept_languages = PLL_Accept_Language::parse_accept_language_header( $http_header );

		$this->assertEquals( 'HK', $accept_languages[0]->get_subtag( 'region' ) );
	}

	public function test_parse_region_subtag_when_script_provided() {
		$http_header = 'zh-Hant-HK';

		$accept_languages = PLL_Accept_Language::parse_accept_language_header( $http_header );

		$this->assertEquals( 'HK', $accept_languages[0]->get_subtag( 'region' ) );
	}

	public function test_parse_variant_subtag() {
		$http_header = 'de-formal';

		$accept_languages = PLL_Accept_Language::parse_accept_language_header( $http_header );

		$this->assertEquals( 'formal', $accept_languages[0]->get_subtag( 'variant' ) );
	}

	public function test_parse_variant_subtag_when_region_provided() {
		$http_header = 'de-DE-formal';

		$accept_languages = PLL_Accept_Language::parse_accept_language_header( $http_header );

		$this->assertEquals( 'formal', $accept_languages[0]->get_subtag( 'variant' ) );
	}

}
