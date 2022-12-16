<?php

class Accept_Languages_Collection_Test extends WP_UnitTestCase {
	/**
	 * Polylang pre-registered languages.
	 *
	 * @see settings/languages.php
	 * @var array
	 */
	protected static $known_languages;

	/**
	 * Polylang Admin Model, used to handle languages.
	 *
	 * @var PLL_Admin_Model
	 */
	protected static $model;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$known_languages = include POLYLANG_DIR . '/settings/languages.php';
		$options               = PLL_Install::get_default_options();
		self::$model           = new PLL_Admin_Model( $options );
	}

	/**
	 * Returns a pre-registered language.
	 *
	 * @param string $locale
	 * @return PLL_Language
	 */
	protected function get_known_language( $locale ) {
		$language               = self::$known_languages[ $locale ];
		$language['locale']     = $locale;
		$language['slug']       = $language['code'];
		$language['w3c']        = isset( $language['w3c'] ) ? $language['w3c'] : str_replace( '_', '-', $language['locale'] );
		$language['rtl']        = 'rtl' === $language['dir'] ? 1 : 0;
		$language['term_group'] = 0;
		$result = self::$model->add_language( $language );

		$this->assertNotInstanceOf( WP_Error::class, $result, "{$locale} language is not created." );

		self::$model->clean_languages_cache();

		return self::$model->get_language( $language['slug'] );
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

		$this->assertSame( 3, count( $this->get_accept_languages_array( $accept_languages ) ) );
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
		$en = $this->get_known_language( 'en_GB' );
		$languages = array( $en );

		$best_match = $accept_languages->find_best_match( $languages );

		$this->assertSame( $en->slug, $best_match );
	}

	public function test_pick_matching_language_and_region_when_script_is_missing() {
		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( 'zh-Hant-HK' );
		$zh_hk = $this->get_known_language( 'zh_HK' );
		$languages = array( $zh_hk );

		$best_match = $accept_languages->find_best_match( $languages );

		$this->assertSame( $zh_hk->slug, $best_match );
	}

	public function test_pick_matching_language_and_variant_when_region_is_missing() {
		$accept_languages = PLL_Accept_Languages_Collection::from_accept_language_header( 'de-formal' );
		$de_de_formal = $this->get_known_language( 'de_DE_formal' );
		$languages = array( $de_de_formal );

		$best_match = $accept_languages->find_best_match( $languages );

		$this->assertSame( $de_de_formal->slug, $best_match );
	}

	public function test_pick_matching_language_and_region_with_custom_slug() {
		$accept_languages    = PLL_Accept_Languages_Collection::from_accept_language_header( 'zh-HK' );
		$zh_cn               = self::$known_languages['zh_CN'];
		$zh_cn['locale']     = 'zh_CN';
		$zh_cn['slug']       = 'zh-cn'; // Custom slug.
		$zh_cn['w3c']        = 'zh-CN';
		$zh_cn['rtl']        = 'rtl' === $zh_cn['dir'] ? 1 : 0;
		$zh_cn['term_group'] = 0;
		$result              = self::$model->add_language( $zh_cn );

		$this->assertNotInstanceOf( WP_Error::class, $result, 'zh_CN language is not created.' );

		self::$model->clean_languages_cache();

		$zh_cn = self::$model->get_language( $zh_cn['slug'] );

		$languages = array( $zh_cn );
		$best_match = $accept_languages->find_best_match( $languages );

		$this->assertSame( $zh_cn->slug, $best_match );
	}

}
