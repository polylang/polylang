<?php

use WP_Syntex\Polylang\Model\Languages;
use WP_Syntex\Polylang\REST\API;

class REST_Settings_Test extends PLL_UnitTestCase {
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

		$factory->language->create_many( 2 );
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

		$response = $this->dispatch_request();
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

		$response = $this->dispatch_request( $method, $values );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );

		$expected = array_merge( $this->pll_env->model->options->get_all(), $values );
		$expected['post_types'] = explode( ',', $expected['post_types'] );
		$expected['sync']       = explode( ',', $expected['sync'] );

		$this->assertSameSetsWithIndex( $expected, $data );
	}

	public function test_invalid_format() {
		wp_set_current_user( self::$administrator );

		$this->pll_env->model->options->set( 'force_lang', 1 );

		$response = $this->dispatch_request( 'PATCH', array( 'force_lang' => 'test' ) );
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
		$response = $this->dispatch_request();
		$this->assertSame( $status, $response->get_status() );

		// Update options.
		$response = $this->dispatch_request( 'PATCH', array( 'redirect_lang' => false ) );
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
	public function test_context_param_should_be_ignored( string $method ) {
		wp_set_current_user( self::$administrator );

		$response = $this->dispatch_request( $method, array( 'context' => 'view' ) );
		$this->assertSame( 200, $response->get_status() );

		$response = $this->dispatch_request( $method, array( 'context' => 'embed' ) );
		$this->assertSame( 200, $response->get_status() );

		$response = $this->dispatch_request( $method, array( 'context' => 'edit' ) );
		$this->assertSame( 200, $response->get_status() );
	}

	public function test_unknown_method() {
		wp_set_current_user( self::$administrator );

		$response = $this->dispatch_request( 'DELETE' );
		$this->assertSame( 404, $response->get_status() );
	}

	public function test_should_not_update_default_language_with_bad_value() {
		wp_set_current_user( self::$administrator );

		$response = $this->dispatch_request( 'PATCH', array( 'default_lang' => 'bad-lang' ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'pll_invalid_language', $response->get_data()['code'] );
	}

	public function test_should_flush_rewrite_rules_when_updating_rewrite_option() {
		global $wp_rewrite;

		self::factory()->language->create_many( 2 );

		wp_set_current_user( self::$administrator );

		$this->assertTrue( $this->pll_env->model->options->get( 'rewrite' ) );

		// Init rewrite rules.
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$links_model = $this->pll_env->model->get_links_model();
		$links_model->init();
		$wp_rewrite->flush_rules();
		$frontend = new PLL_Frontend( $links_model );
		$frontend->init();
		$rules_prior_to_update = $wp_rewrite->rewrite_rules();

		$this->assertArrayHasKey( '(en|fr)/?$', $rules_prior_to_update );

		$response = $this->dispatch_request( 'POST', array( 'rewrite' => false ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $this->pll_env->model->options->get( 'rewrite' ) );

		// Redo frontend to get new rules.
		$frontend->links_model->remove_filters();
		$links_model = $this->pll_env->model->get_links_model();
		$links_model->init();
		$frontend = new PLL_Frontend( $links_model );
		$frontend->init();
		$rules_after_update = $wp_rewrite->rewrite_rules();

		$this->assertArrayHasKey( 'language/(en|fr)/?$', $rules_after_update );
	}

	public function test_update_default_language_should_update_default_language() {
		wp_set_current_user( self::$administrator );

		$this->assertSame( 'en', $this->pll_env->model->options['default_lang'] ); // Make sure the default language is not the one we want to set.
		$this->assertCount( 2, $this->pll_env->model->languages->get_list() ); // Put languages in cache.
		$this->assertIsArray( get_transient( Languages::TRANSIENT_NAME ) ); // Make sure the cache is not empty.

		$response = $this->dispatch_request( 'PATCH', array( 'default_lang' => 'fr' ) );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );

		// Make sure the option has been updated.
		$this->assertSame( 'fr', $data['default_lang'] );
		$this->assertSame( 'fr', $this->pll_env->model->options['default_lang'] );

		// Make sure the cache has been cleared.
		$this->assertFalse( get_transient( Languages::TRANSIENT_NAME ) );

		// Make sure the default language has changed.
		$lang_fr = $this->pll_env->model->languages->get( 'fr' );
		$this->assertInstanceOf( PLL_Language::class, $lang_fr );
		$this->assertTrue( $lang_fr->is_default );
	}

	/**
	 * Dispatches a request after setting some params.
	 *
	 * @param string $method Optional. The method. Default is `GET`.
	 * @param array  $params Optional. The params. Default is an empty array.
	 * @return WP_REST_Response
	 */
	private function dispatch_request( string $method = 'GET', array $params = array() ): WP_REST_Response {
		$request = new WP_REST_Request( $method, '/pll/v1/settings' );

		foreach ( $params as $name => $value ) {
			if ( isset( $value ) ) {
				$request->set_param( $name, $value );
			}
		}

		return $this->server->dispatch( $request );
	}
}
