<?php

namespace WP_Syntex\Polylang\Tests\Integration\REST;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use PLL_Context_Rest;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;

class Locale_Test extends PLL_UnitTestCase {
	/**
	 * @var PLL_REST_Request
	 */
	private $pll_rest;

	/**
	 * @var Spy_REST_Server
	 */
	private $server;

	protected static $administrator;

	/**
	 * @param PLL_UnitTest_Factory $factory
	 * @return void
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );

		self::$administrator = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * @global $wp_rest_server
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->pll_rest = ( new PLL_Context_Rest() )->get();
		$this->server   = $GLOBALS['wp_rest_server'];
	}

	public function test_should_return_locale_matching_lang_parameter() {
		register_rest_route(
			'pll-phpunit/v1',
			'/test',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => function () {
					return new WP_REST_Response( array( 'locale' => get_locale() ) );
				},
				'permission_callback' => '__return_true',
			)
		);

		$request                 = new WP_REST_Request( 'GET', '/pll-phpunit/v1/test' );
		$_REQUEST['lang']        = 'fr';
		$this->pll_rest->curlang = $this->pll_rest->model->get_language( 'fr' );
		$response                = $this->server->dispatch( $request );

		$this->assertSame( 'fr_FR', $response->get_data()['locale'] );
	}
}
