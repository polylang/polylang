<?php

class Accept_Languages_Collection_Test extends PHPUnit_Framework_TestCase {
	/**
	 * Polylang pre-registered languages.
	 *
	 * @see settings/languages.php
	 * @var array
	 */
	protected static $known_languages;

	public static function set_up_before_class() {
		parent::set_up_before_class();
		self::$known_languages = include POLYLANG_DIR . '/settings/languages.php';
	}

	/**
	 * Returns a pre-registered language.
	 *
	 * @param string $locale
	 * @return PLL_Language
	 */
	protected function get_known_language( $locale ) {
		return new PLL_Language( self::$known_languages[ $locale ] );
	}

	/**
	 * Use reflection to access PLL_Accept_Language values from PLL_Accept_Languages_Collection.
	 *
	 * @param PLL_Accept_Languages_Collection $accept_languages_collection Instance.
	 * @return PLL_Accept_Language[]
	 */
	protected function get_accept_languages_array( $accept_languages_collection ) {
		$reflection = new ReflectionProperty( PLL_Accept_Languages_Collection::class, 'accept_languages' );
		$reflection->setAccessible( true );
		return $reflection->getValue( $accept_languages_collection );
	}

	public function test_parse_all_language_tags() {
		$http_header = 'en-GB, en-US;q=0.8,en';

		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( $http_header );

		$this->assertEquals( 3, count( $this->get_accept_languages_array( $accept_languages ) ) );
	}

	public function test_parse_simple_language_subtag() {
		$http_header = 'en';

		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( $http_header );

		$this->assertEquals( 'en', $this->get_accept_languages_array( $accept_languages )[0]->get_subtag( 'language' ) );
	}

	public function test_parse_language_subtag_when_region_provided() {
		$http_header = 'en-GB';

		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( $http_header );

		$this->assertEquals( 'en', $this->get_accept_languages_array( $accept_languages )[0]->get_subtag( 'language' ) );
	}

	public function test_parse_region_subtag() {
		$http_header = 'zh-HK';

		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( $http_header );

		$this->assertEquals( 'HK', $this->get_accept_languages_array( $accept_languages )[0]->get_subtag( 'region' ) );
	}

	public function test_parse_region_subtag_when_script_provided() {
		$http_header = 'zh-Hant-HK';

		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( $http_header );

		$this->assertEquals( 'HK', $this->get_accept_languages_array( $accept_languages )[0]->get_subtag( 'region' ) );
	}

	public function test_parse_variant_subtag() {
		$http_header = 'de-formal';

		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( $http_header );

		$this->assertEquals( 'formal', $this->get_accept_languages_array( $accept_languages )[0]->get_subtag( 'variant' ) );
	}

	public function test_parse_variant_subtag_when_region_provided() {
		$http_header = 'de-DE-formal';

		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( $http_header );

		$this->assertEquals( 'formal', $this->get_accept_languages_array( $accept_languages )[0]->get_subtag( 'variant' ) );
	}

	public function test_pick_matching_language_with_different_region() {
		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( 'en-US' );
		$en = $this->get_known_language( 'en_GB' );
		$languages = array( $en );

		$best_match = $accept_languages->find_best_match( $languages );

		$this->assertEquals( $en->slug, $best_match );
	}

	public function test_pick_matching_language_and_region_when_script_is_missing() {
		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( 'zh-Hant-HK' );
		$zh_hk = $this->get_known_language( 'zh_HK' );
		$languages = array( $zh_hk );

		$best_match = $accept_languages->find_best_match( $languages );

		$this->assertEquals( $zh_hk->slug, $best_match );
	}

	public function test_pick_matching_language_and_variant_when_region_is_missing() {
		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( 'de-formal' );
		$de_de_formal = $this->get_known_language( 'de_DE_formal' );
		$languages = array( $de_de_formal );

		$best_match = $accept_languages->find_best_match( $languages );

		$this->assertEquals( $de_de_formal->slug, $best_match );
	}

	public function test_pick_matching_language_and_region_with_custom_slug() {
		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( 'zh-HK' );
		$zh_cn = new PLL_Language(
			array_merge(
				self::$known_languages['zh_CN'],
				array(
					'slug' => 'zh-cn',
					'w3c'  => 'zh-CN', // Is computed from locale when language is set from term. {@see PLL_Language::__construct()}.
				)
			)
		);
		$languages = array( $zh_cn );

		$best_match = $accept_languages->find_best_match( $languages );

		$this->assertEquals( $zh_cn->slug, $best_match );
	}

}
