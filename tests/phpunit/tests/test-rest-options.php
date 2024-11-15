<?php

use WP_Syntex\Polylang\REST\API;

class REST_Options_Test extends PLL_UnitTestCase {
	/**
	 * @var Spy_REST_Server
	 */
	private $server;

	protected static $administrator;
	protected static $author;

	/**
	 * @param PLL_UnitTest_Factory $factory
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		self::$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		self::$author        = self::factory()->user->create( array( 'role' => 'author' ) );
	}

	public function set_up() {
		parent::set_up();

		add_action(
			'pll_init',
			function ( $polylang ) {
				$polylang->rest = new API( $polylang->model );
				add_action( 'rest_api_init', array( $polylang->rest, 'init' ) );
			}
		);

		$this->pll_env = ( new PLL_Context_Rest() )->get();
		$this->server  = $GLOBALS['wp_rest_server'];
	}

	public function tear_down() {
		_unregister_post_type( 'custom1' );
		_unregister_post_type( 'custom2' );

		parent::tear_down();
	}

	public function test_get_options_list() {
		wp_set_current_user( self::$administrator );

		$response = $this->server->dispatch( $this->get_request() );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertSameSetsWithIndex( $this->pll_env->model->options->get_all(), $data );
	}

	/**
	 * @testWith ["PATCH"]
	 *           ["PUT"]
	 *
	 * @param string $method
	 */
	public function test_update_options( string $method ) {
		wp_set_current_user( self::$administrator );
		register_post_type( 'custom1', array( 'public' => true ) );
		register_post_type( 'custom2', array( 'public' => true ) );

		$this->pll_env->model->options->merge(
			array(
				'force_lang'    => 1,
				'hide_default'  => true,
				'media_support' => true,
				'post_types'    => array(),
				'sync'          => array(),
			)
		);

		$values = array(
			'force_lang'    => 2,
			'hide_default'  => false,
			'media_support' => false,
			'post_types'    => 'custom1,custom2',
			'sync'          => 'taxonomies,post_meta',
		);

		$response = $this->server->dispatch( $this->get_request( $method, $values ) );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$values['post_types'] = explode( ',', $values['post_types'] );
		$values['sync']       = explode( ',', $values['sync'] );

		foreach ( $values as $name => $value ) {
			$this->assertSame( $value, $data[ $name ] );
		}
	}

	public function test_invalid_format() {
		wp_set_current_user( self::$administrator );

		$this->pll_env->model->options->set( 'force_lang', 1 );

		$response = $this->server->dispatch( $this->get_request( 'PATCH', array( 'force_lang' => 'test' ) ) );
		$this->assertSame( 400, $response->get_status() );

		$this->assertIsArray( $response->get_data() );
		$this->assertSame( 1, $this->pll_env->model->options->get( 'force_lang' ) );
	}

	/**
	 * @testWith ["0", 401]
	 *           ["author", 403]
	 *
	 * @param string $user
	 * @param int    $status
	 * @return void
	 */
	public function test_permissions( string $user, int $status ) {
		if ( '0' === $user ) {
			wp_set_current_user( 0 );
		} else {
			wp_set_current_user( self::$author );
		}

		$this->pll_env->model->options->set( 'redirect_lang', true );

		// Get options.
		$response = $this->server->dispatch( $this->get_request() );
		$this->assertSame( $status, $response->get_status() );

		// Update options.
		$response = $this->server->dispatch( $this->get_request( 'PATCH', array( 'redirect_lang' => false ) ) );
		$this->assertSame( $status, $response->get_status() );
		$this->assertNotFalse( $this->pll_env->model->options->get( 'redirect_lang' ) );
	}

	/**
	 * @testWith ["GET"]
	 *           ["PUT"]
	 *           ["PATCH"]
	 *           ["POST"]
	 *
	 * @param string $method
	 * @return void
	 */
	public function test_wrong_context( string $method ) {
		wp_set_current_user( self::$administrator );

		// Context 'view'.
		$response = $this->server->dispatch( $this->get_request( $method, array( 'context' => 'view' ) ) );
		$this->assertSame( 400, $response->get_status() );

		// Context 'embed'.
		$response = $this->server->dispatch( $this->get_request( $method, array( 'context' => 'embed' ) ) );
		$this->assertSame( 400, $response->get_status() );
	}

	public function test_unknown_method() {
		wp_set_current_user( self::$administrator );

		$response = $this->server->dispatch( $this->get_request( 'DELETE' ) );
		$this->assertSame( 404, $response->get_status() );
	}

	/**
	 * Returns a request after setting the context.
	 *
	 * @param string $method Optional. The method. Default is `GET`.
	 * @param array  $params Optional. The params. Default is an empty array.
	 * @return WP_REST_Request
	 */
	private function get_request( string $method = 'GET', array $params = array() ): WP_REST_Request {
		$request = new WP_REST_Request( $method, '/pll/v1/settings' );

		foreach ( $params as $name => $value ) {
			if ( isset( $value ) ) {
				$request->set_param( $name, $value );
			}
		}

		return $request;
	}
}
