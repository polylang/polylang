<?php

class Rest_Request_Test extends PLL_UnitTestCase {
	/**
	 * @var string
	 */
	public $structure = '/%postname%/';

	/**
	 * @var PLL_REST_Request
	 */
	private $pll_rest;

	/**
	 * Initialization before all tests run.
	 *
	 * @param  WP_UnitTest_Factory $factory WP_UnitTest_Factory object.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$links_model         = self::$model->get_links_model();
		$this->pll_rest      = new PLL_REST_Request( $links_model );
		$GLOBALS['polylang'] = &$this->pll_rest;
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		parent::tear_down();

		unset( $GLOBALS['polylang'] );
	}

	/**
	 * @dataProvider routes_provider
	 *
	 * @param array $data {
	 *    @type string $method   HTTP method.
	 *    @type string $route    Route.
	 *    @type string $resource Resource name.
	 * }
	 */
	public function test_should_define_language_when_language_is_valid( $data ) {
		self::$model->options['default_lang'] = 'en';
		$this->pll_rest->init();

		$request = new WP_REST_Request( $data['method'], $data['route'] );
		$request = $this->set_lang_param( $request, 'fr' );
		$response = rest_do_request( $request );

		$this->assertNotEmpty( $response );
		$this->assertInstanceOf( 'PLL_Language', $this->pll_rest->curlang );
		$this->assertSame( 'fr', $this->pll_rest->curlang->slug );
	}

	/**
	 * @dataProvider routes_provider
	 *
	 * @param array $data {
	 *    @type string $method   HTTP method.
	 *    @type string $route    Route.
	 *    @type string $resource Resource name.
	 * }
	 */
	public function test_should_define_default_language_when_language_is_invalid( $data ) {
		self::$model->options['default_lang'] = 'en';
		$this->pll_rest->init();

		$request = new WP_REST_Request( $data['method'], $data['route'] );
		$request = $this->set_lang_param( $request, 'it' );
		$response = rest_do_request( $request );

		$this->assertNotEmpty( $response );
		$this->assertInstanceOf( 'PLL_Language', $this->pll_rest->curlang );
		$this->assertSame( 'en', $this->pll_rest->curlang->slug );
	}

	/**
	 * @dataProvider routes_provider
	 *
	 * @param array $data {
	 *    @type string $method   HTTP method.
	 *    @type string $route    Route.
	 *    @type string $resource Resource name.
	 * }
	 */
	public function test_should_not_define_default_language_when_default_language_is_invalid( $data ) {
		self::$model->options['default_lang'] = 'es';
		$this->pll_rest->init();

		$request = new WP_REST_Request( $data['method'], $data['route'] );
		$request = $this->set_lang_param( $request, 'it' );
		$response = rest_do_request( $request );

		$this->assertNotEmpty( $response );
		$this->assertFalse( $this->pll_rest->curlang );
	}

	/**
	 * @dataProvider routes_provider
	 *
	 * @FIXME Should we avoid set the current language when not sent really ?
	 *
	 * @param array $data {
	 *    @type string $method   HTTP method.
	 *    @type string $route    Route.
	 *    @type string $resource Resource name.
	 * }
	 */
	public function test_should_not_define_language_when_not_sent( $data ) {
		self::$model->options['default_lang'] = 'en';
		$this->pll_rest->init();

		$request = new WP_REST_Request( $data['method'], $data['route'] );
		$response = rest_do_request( $request );

		$this->assertNotEmpty( $response );
		$this->assertNull( $this->pll_rest->curlang );
	}

	/**
	 * Yields HTTP requests methods and routes.
	 *
	 * @return array $data {
	 *    @type string   $method   HTTP method.
	 *    @type string   $resource Resource of the route.
	 *    @type string   $route    REST route.
	 * }
	 */
	public function routes_provider() {
		$routes = array(
			'post'     => '/wp/v2/posts',
			'category' => '/wp/v2/categories',
			'tag'      => '/wp/v2/tags',
			'page'     => '/wp/v2/pages',
			'comment'  => '/wp/v2/comments',
			'taxonomy' => '/wp/v2/taxonomies',
			'media'    => '/wp/v2/media',
			'user'     => '/wp/v2/users',
		);
		$methods = array(
			'GET',
			'POST',
			'PUT',
			'PATCH',
		);

		foreach ( $methods as $method ) {
			foreach ( $routes as $resource => $route ) {
				yield array(
					"{$method} request on '{$route}' route." => array(
						'method'   => $method,
						'resource' => $resource,
						'route'    => $route,
					),
				);
			}
		}
	}

	/**
	 * Sets the language parameter in the given request.
	 *
	 * @param WP_REST_Request $request Request to set the language parameter.
	 * @param string          $lang    Language slug.
	 *
	 * @return WP_REST_Request Request with the language parameter set.
	 */
	private function set_lang_param( $request, $lang ) {
		if ( 'GET' === $request->get_method() ) {
			$request->set_query_params( array( 'lang' => $lang ) );
		} else {
			$request->set_body_params( array( 'lang' => $lang ) );
		}

		return $request;
	}
}
