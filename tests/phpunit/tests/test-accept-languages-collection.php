<?php

class Accept_Languages_Collection_Test extends PLL_UnitTestCase {
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

		$this->assertCount( 3, $this->get_accept_languages_array( $accept_languages ) );
	}

	public function test_parse_simple_language_subtag() {
		$http_header = 'en';

		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( $http_header );

		$this->assertSame( 'en', $this->get_accept_languages_array( $accept_languages )[0]->get_subtag( 'language' ) );
	}

	public function test_parse_language_subtag_when_region_provided() {
		$http_header = 'en-GB';

		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( $http_header );

		$this->assertSame( 'en', $this->get_accept_languages_array( $accept_languages )[0]->get_subtag( 'language' ) );
	}

	public function test_parse_region_subtag() {
		$http_header = 'zh-HK';

		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( $http_header );

		$this->assertSame( 'HK', $this->get_accept_languages_array( $accept_languages )[0]->get_subtag( 'region' ) );
	}

	public function test_parse_region_subtag_when_script_provided() {
		$http_header = 'zh-Hant-HK';

		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( $http_header );

		$this->assertSame( 'HK', $this->get_accept_languages_array( $accept_languages )[0]->get_subtag( 'region' ) );
	}

	public function test_parse_variant_subtag() {
		$http_header = 'de-formal';

		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( $http_header );

		$this->assertSame( 'formal', $this->get_accept_languages_array( $accept_languages )[0]->get_subtag( 'variant' ) );
	}

	public function test_parse_variant_subtag_when_region_provided() {
		$http_header = 'de-DE-formal';

		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( $http_header );

		$this->assertSame( 'formal', $this->get_accept_languages_array( $accept_languages )[0]->get_subtag( 'variant' ) );
	}

	public function test_pick_matching_language_with_different_region() {
		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( 'en-US' );
		self::create_language( 'en_GB' );
		$en = self::$model->get_language( 'en_GB' );
		$languages = array( $en );

		$best_match = $accept_languages->find_best_match( $languages );

		$this->assertSame( $en->slug, $best_match );
	}

	public function test_pick_matching_language_and_region_when_script_is_missing() {
		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( 'zh-Hant-HK' );
		self::create_language( 'zh_HK' );
		$zh_hk = self::$model->get_language( 'zh_HK' );
		$languages = array( $zh_hk );

		$best_match = $accept_languages->find_best_match( $languages );

		$this->assertSame( $zh_hk->slug, $best_match );
	}

	public function test_pick_matching_language_and_variant_when_region_is_missing() {
		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( 'de-formal' );
		self::create_language( 'de_DE_formal' );
		$de_de_formal = self::$model->get_language( 'de_DE_formal' );
		$languages = array( $de_de_formal );

		$best_match = $accept_languages->find_best_match( $languages );

		$this->assertSame( $de_de_formal->slug, $best_match );
	}

	public function test_pick_matching_language_and_region_with_custom_slug() {
		$accept_languages    = PLL_Accept_Languages_Collection::from_accept_language_header( 'zh-HK' );
		$zh_cn['slug']       = 'zh-cn'; // Custom slug.
		self::create_language( 'zh_CN', $zh_cn );
		$zh_cn = self::$model->get_language( 'zh_CN' );
		$languages = array( $zh_cn );

		self::$model->clean_languages_cache();

		$zh_cn = self::$model->get_language( $zh_cn['slug'] );

		$languages = array( $zh_cn );

		$best_match = $accept_languages->find_best_match( $languages );

		$this->assertSame( $zh_cn->slug, $best_match );
	}

}
