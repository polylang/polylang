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
	 * @var Spy_REST_Server
	 */
	private $server;

	protected static $administrator;

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

		self::$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * @global $wp_rest_server
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		global $wp_rest_server;
		$this->server = $wp_rest_server = new Spy_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );

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
		self::$model->options['default_lang'] = 'en';
		$this->pll_rest->init();

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
		self::$model->options['default_lang'] = 'es';
		$this->pll_rest->init();

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
		self::$model->options['default_lang'] = 'en';
		$this->pll_rest->init();

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
		self::$model->options['default_lang'] = 'en';
		$this->pll_rest->init();

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
