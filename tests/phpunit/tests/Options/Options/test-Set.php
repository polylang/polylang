<?php

namespace WP_Syntex\Polylang\Tests\Integration\Options\Options;

use PLL_Context_Admin;
use PLL_UnitTestCase;
use WP_Error;
use WP_UnitTest_Factory;

/**
 * Tests for `Options\Options->set()`.
 *
 * @group options
 */
class Set_Test extends PLL_UnitTestCase {

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'es_ES' );
	}

	public function tear_down() {
		remove_filter( 'pre_http_request', array( $this, 'http_request_mock' ) );

		parent::tear_down();
	}

	/**
	 * The "domains" option should trigger "non blocking errors" for empty or unreachable URLs.
	 *
	 * @return void
	 */
	public function test_should_trigger_non_blocking_errors_on_domains_save() {
		add_filter( 'pre_http_request', array( $this, 'http_request_mock' ), 10, 3 );

		$options = array(
			'default_lang' => 'en',
			'force_lang'   => 3,
		);
		$domains = array(
			'en' => 'https://good-url.org', // Must succeed.
			'fr' => 'https://wrong-url.org', // Must fail.
			'es' => '',
		);

		$this->pll_env       = ( new PLL_Context_Admin( array( 'options' => $options ) ) )->get();
		$GLOBALS['polylang'] = $this->pll_env;

		$errors = $this->pll_env->model->options->set( 'domains', $domains );

		$expected = array(
			'pll_empty_domains'   => 'Español',
			'pll_invalid_domains' => $domains['fr'],
		);

		$this->assertCount( 2, $errors->get_error_codes() );

		foreach ( $errors->get_error_codes() as $code ) {
			// Make sure we have the expected errors, and only them.
			$this->assertArrayHasKey( $code, $expected );
			// Make sure that each error is triggered by the right language/domain.
			$this->assertStringContainsString( $expected[ $code ], $errors->get_error_message( $code ) );
		}

		// Make sure the domains are saved.
		$this->assertSameSetsWithIndex( $domains, $this->pll_env->model->options->get( 'domains' ) );
	}

	public function test_should_not_set_bad_domain_language() {
		add_filter( 'pre_http_request', array( $this, 'http_request_mock' ), 10, 3 );

		$this->pll_env       = ( new PLL_Context_Admin() )->get();
		$GLOBALS['polylang'] = $this->pll_env;

		$domains = array(
			'en'       => 'https://good-url.com',
			'bad-lang' => 'https://good-url.org',
		);
		$this->pll_env->model->options->set( 'domains', $domains );
		$domains = $this->pll_env->model->options->get( 'domains' );

		$this->assertArrayHasKey( 'en', $domains );
		$this->assertSame( 'https://good-url.com', $domains['en'] );
		$this->assertArrayNotHasKey( 'bad-lang', $domains );
	}

	/**
	 * @ticket #2397
	 * @see https://github.com/polylang/polylang-pro/issues/2397
	 */
	public function test_update_language_slug() {
		add_filter( 'pre_http_request', array( $this, 'http_request_mock' ), 10, 3 );

		$options = array(
			'default_lang' => 'en',
			'force_lang'   => 3,
			'domains'      => array(
				'en' => 'https://good-url.com',
				'fr' => 'https://good-url.org',
				'es' => '',
			),
		);

		$this->pll_env       = ( new PLL_Context_Admin( array( 'options' => $options ) ) )->get();
		$GLOBALS['polylang'] = $this->pll_env;

		$language = $this->pll_env->model->languages->get( 'fr' );
		$this->pll_env->model->languages->update(
			array(
				'lang_id'    => $language->term_id,
				'name'       => $language->name,
				'slug'       => 'fra',
				'locale'     => $language->locale,
				'rtl'        => $language->is_rtl,
				'term_group' => $language->term_group,
			)
		);
		$domains = $this->pll_env->model->options->get( 'domains' );

		$this->assertArrayHasKey( 'fra', $domains );
		$this->assertSame( 'https://good-url.org', $domains['fra'] );
		$this->assertArrayNotHasKey( 'fr', $domains );
	}

	public function test_should_not_set_bad_default_language() {
		$options = self::create_options();
		$errors = $options->set( 'default_lang', 'bad-lang' );

		$this->assertInstanceOf( WP_Error::class, $errors );
		$this->assertTrue( $errors->has_errors() );
		$this->assertSame( 'pll_invalid_language', $errors->get_error_code() );
	}

	/**
	 * Callback used to filter the http requests.
	 *
	 * @param false|array|WP_Error $response    A preemptive return value of an HTTP request. Default false.
	 * @param array                $parsed_args HTTP request arguments.
	 * @param string               $url         The request URL.
	 * @return false|array|WP_Error
	 */
	public function http_request_mock( $response, $parsed_args, $url ) {
		$_response = array(
			'headers'  => array(),
			'body'     => '',
			'response' => array(
				'code'    => 200,
				'message' => '',
			),
			'cookies'  => array(),
			'filename' => '',
		);

		if ( 'https://wrong-url.org?deactivate-polylang=1' === $url ) {
			// Bad URL: failure.
			$_response['response']['code'] = 404;
			return $_response;
		}

		if ( strpos( $url, 'deactivate-polylang=1' ) !== false ) {
			// Other domain checks: success.
			return $_response;
		}

		return $response;
	}
}
