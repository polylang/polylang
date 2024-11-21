<?php

/**
 * Test the 'domains' option.
 */
class Option_Domains_Test extends PLL_UnitTestCase {

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'es_ES' );
	}

	public function tear_down() {
		remove_filter( 'pre_http_request', array( $this, 'http_request_filter' ) );

		parent::tear_down();
	}

	public function test_non_blocking_errors() {
		add_filter( 'pre_http_request', array( $this, 'http_request_filter' ), 10, 3 );

		$options = array(
			'default_lang' => 'en',
			'force_lang'   => 3,
		);
		$domains = array(
			'en' => 'https://example.org', // Will succeed.
			'fr' => 'https://example.com', // Will fail.
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

		$this->assertSameSetsWithIndex( $domains, $this->pll_env->model->options->get( 'domains' ) );
	}

	public function http_request_filter( $response, $parsed_args, $url ) {
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

		switch ( $url ) {
			case 'https://example.org?deactivate-polylang=1':
				// EN: success.
				return $_response;

			case 'https://example.com?deactivate-polylang=1':
				// FR: failure.
				$_response['response']['code'] = 404;
				return $_response;
		}

		return $response;
	}
}
