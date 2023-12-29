<?php

class Rest_Request_Test extends PLL_UnitTestCase {
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

		self::$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
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
		$request = new WP_REST_Request( $data['method'], $data['route'] );
		$request->set_param( 'lang', 'fr' );
		$response = $this->server->dispatch( $request );

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
		$request = new WP_REST_Request( $data['method'], $data['route'] );
		$request->set_param( 'lang', 'it' );
		$response = $this->server->dispatch( $request );

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
		$this->pll_rest->model->options['default_lang'] = 'es';

		$request = new WP_REST_Request( $data['method'], $data['route'] );
		$request->set_param( 'lang', 'it' );
		$response = $this->server->dispatch( $request );

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
		$request  = new WP_REST_Request( $data['method'], $data['route'] );
		$response = $this->server->dispatch( $request );

		$this->assertNotEmpty( $response );
		$this->assertNull( $this->pll_rest->curlang );
	}

	/**
	 * @testWith ["post", "/wp/v2/posts", "title", "Post"]
	 *           ["term", "/wp/v2/categories", "name", "Category term"]
	 *
	 * @param string $type  Type of content.
	 * @param string $route REST route.
	 * @param string $field Required field to create the content.
	 * @param string $value Value of the required field.
	 */
	public function test_should_assign_default_language_when_no_language_sent( $type, $route, $field, $value ) {
		wp_set_current_user( self::$administrator );

		$request = new WP_REST_Request( 'POST', $route );
		$request->set_param( $field, $value );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 201, $response->get_status() );

		$data     = $response->get_data();
		$language = $this->pll_rest->model->{$type}->get_language( $data['id'] );
		$this->assertInstanceOf( PLL_Language::class, $language, "A language should be assigned by default to the {$type}." );
		$this->assertEquals( $this->pll_rest->options['default_lang'], $language->slug, "When no language is sent, the default one should be assigned to the {$type}." );
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
}
