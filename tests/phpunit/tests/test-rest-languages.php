<?php

use WP_Syntex\Polylang\REST\API;

class REST_Languages_Test extends PLL_UnitTestCase {
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

				$polylang->default_term = new PLL_Default_Term( $polylang );
				$polylang->default_term->add_hooks();
			}
		);

		$this->pll_env = ( new PLL_Context_Rest() )->get();
		$this->server  = $GLOBALS['wp_rest_server'];
	}

	public function test_get_languages_list() {
		$locales = array(
			'en_US'        => 'en_US',
			'fr_FR'        => 'fr_FR',
			'de_DE_formal' => 'de_DE_formal',
		);

		foreach ( $locales as $locale ) {
			self::factory()->language->create( array( 'locale' => $locale ) );
		}

		$request = new WP_REST_Request( 'GET', '/pll/v1/languages' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertCount( 3, $data );

		foreach ( $data as $response_data ) {
			$this->assertInstanceOf( WP_REST_Response::class, $response_data );
			$response_data = $response_data->get_data();
			$this->assertIsArray( $response_data );
			$this->assertArrayHasKey( 'locale', $response_data );
			$this->assertContains( $response_data['locale'], $locales );
			unset( $locales[ $response_data['locale'] ] );
		}
	}

	public function test_create_language() {
		wp_set_current_user( self::$administrator );
		$def_cat_id = (int) get_option( 'default_category' );

		// 1- Create a language without custom values.
		$request = new WP_REST_Request( 'POST', '/pll/v1/languages' );
		$request->set_param( 'locale', 'es_ES' ); // Required.
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		// Check for default values from `languages.php`.
		$data      = $response->get_data();
		$languages = include POLYLANG_DIR . '/settings/languages.php';
		$language  = $languages['es_ES'];

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'slug', $data );
		$this->assertSame( $language['code'], $data['slug'] );
		$this->assertArrayHasKey( 'name', $data );
		$this->assertSame( $language['name'], $data['name'] );
		$this->assertArrayHasKey( 'is_rtl', $data );
		$this->assertSame( 'rtl' === $language['dir'], $data['is_rtl'] );
		$this->assertArrayHasKey( 'flag_code', $data );
		$this->assertSame( $language['flag'], $data['flag_code'] );
		$this->assertArrayHasKey( 'facebook', $data );
		$this->assertSame( $language['facebook'], $data['facebook'] );

		// Check for fields with a custom name.
		$language = $this->pll_env->model->get_language( 'es_ES' );

		$this->assertArrayHasKey( 'term_id', $data );
		$this->assertSame( $language->term_id, $data['term_id'] );
		$this->assertArrayHasKey( 'term_group', $data );
		$this->assertSame( $language->term_group, $data['term_group'] );
		$this->assertArrayHasKey( 'flag', $data );
		$this->assertSame( $language->flag, $data['flag'] );
		$this->assertArrayHasKey( 'custom_flag', $data );
		$this->assertSame( $language->custom_flag, $data['custom_flag'] );
		$this->assertArrayHasKey( 'active', $data );
		$this->assertSame( $language->active, $data['active'] );

		// Single check to make sure other fields are not missing.
		$this->assertArrayHasKey( 'term_props', $data );
		$this->assertSame( $language->get_tax_props(), $data['term_props'] );

		// Check the default category.
		$def_cat_lang = $this->pll_env->model->term->get_language( $def_cat_id );
		$this->assertInstanceOf( PLL_Language::class, $def_cat_lang );
		$this->assertSame( $data['slug'], $def_cat_lang->slug );

		// 2- Create a language with custom values.
		$values = array(
			'name'       => 'François',
			'slug'       => 'fra',
			'is_rtl'     => true,
			'flag_code'  => 'be',
			'term_group' => 22,
		);
		$request = new WP_REST_Request( 'POST', '/pll/v1/languages' );
		$request->set_param( 'locale', 'fr_FR' ); // Required.
		$request->set_param( 'w3c', 'foo' ); // Should not be set.
		$request->set_param( 'no_default_cat', true ); // Do not create the default category for this language.

		foreach ( $values as $name => $value ) {
			$request->set_param( $name, $value );
		}
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		// Check the values match.
		$data = $response->get_data();
		$this->assertIsArray( $data );

		foreach ( $values as $name => $value ) {
			$this->assertSame( $value, $data[ $name ] );
		}

		$this->assertNotSame( 'foo', $data['w3c'] );

		// Check the default category is not created.
		$def_cat_id_fr = $this->pll_env->model->term->get( $def_cat_id, $data['slug'] );
		$this->assertSame( 0, $def_cat_id_fr );
	}

	public function test_get_language() {
		$fr_id = self::factory()->language->create( array( 'locale' => 'fr_FR', 'slug' => 'fra' ) );
		self::factory()->language->create( array( 'locale' => 'en_US' ) );

		// By `term_id`.
		$request = new WP_REST_Request( 'GET', "/pll/v1/languages/{$fr_id}" );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'slug', $data );
		$this->assertSame( 'fra', $data['slug'] );

		// By `slug`.
		$request = new WP_REST_Request( 'GET', '/pll/v1/languages/fra' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * @testWith ["PATCH"]
	 *           ["PUT"]
	 *
	 * @param string $method
	 */
	public function test_update_language( string $method ) {
		wp_set_current_user( self::$administrator );
		$fr_id = self::factory()->language->create( array( 'locale' => 'fr_FR' ) );

		$values = array(
			'locale'     => 'fr_BE',
			'name'       => 'François',
			'slug'       => 'fra',
			'is_rtl'     => true,
			'flag_code'  => 'be',
			'term_group' => 22,
		);
		$request = new WP_REST_Request( $method, "/pll/v1/languages/{$fr_id}" );

		foreach ( $values as $name => $value ) {
			$request->set_param( $name, $value );
		}
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );

		foreach ( $values as $name => $value ) {
			$this->assertSame( $value, $data[ $name ] );
		}
	}

	public function test_delete_language() {
		wp_set_current_user( self::$administrator );
		$fr_id = self::factory()->language->create( array( 'locale' => 'fr_FR' ) );

		$request  = new WP_REST_Request( 'DELETE', "/pll/v1/languages/{$fr_id}" );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'deleted', $data );
		$this->assertTrue( $data['deleted'] );
		$this->assertArrayHasKey( 'previous', $data );
		$this->assertIsArray( $data['previous'] );

		$this->assertFalse( $this->pll_env->model->get_language( 'fr_FR' ) );
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

		$fr_id = self::factory()->language->create( array( 'locale' => 'fr_FR' ) );

		// Get languages.
		$request = new WP_REST_Request( 'GET', '/pll/v1/languages' );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( $status, $response->get_status() );

		// Get language.
		$request = new WP_REST_Request( 'GET', "/pll/v1/languages/{$fr_id}" );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( $status, $response->get_status() );

		// Delete language.
		$request  = new WP_REST_Request( 'DELETE', "/pll/v1/languages/{$fr_id}" );
		$response = $this->server->dispatch( $request );

		$this->assertSame( $status, $response->get_status() );
		$this->assertInstanceOf( PLL_Language::class, $this->pll_env->model->get_language( 'fr_FR' ) );

		// Update language.
		$request = new WP_REST_Request( 'PATCH', "/pll/v1/languages/{$fr_id}" );
		$request->set_param( 'name', 'François' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( $status, $response->get_status() );
		$this->assertNotSame( 'François', $this->pll_env->model->get_language( 'fr_FR' )->name );

		// Create language.
		$request = new WP_REST_Request( 'POST', '/pll/v1/languages' );
		$request->set_param( 'locale', 'es_ES' ); // Required.
		$response = $this->server->dispatch( $request );

		$this->assertSame( $status, $response->get_status() );
		$this->assertFalse( $this->pll_env->model->get_language( 'es_ES' ) );
	}

	/**
	 * @testWith ["PUT"]
	 *           ["PATCH"]
	 *           ["DELETE"]
	 *
	 * @param string $method
	 * @return void
	 */
	public function test_missing_params( string $method ) {
		wp_set_current_user( self::$administrator );
		self::factory()->language->create( array( 'locale' => 'fr_FR' ) );

		$request  = new WP_REST_Request( $method, '/pll/v1/languages' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 404, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertSame( 'rest_no_route', $data['code'] );
	}

	/**
	 * @testWith ["PUT"]
	 *           ["DELETE"]
	 *
	 * @param string $method
	 * @return void
	 */
	public function test_invalid_param( string $method ) {
		wp_set_current_user( self::$administrator );
		self::factory()->language->create( array( 'locale' => 'fr_FR' ) );

		$request  = new WP_REST_Request( $method, '/pll/v1/languages/1876458' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 404, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertSame( 'rest_invalid_id', $data['code'] );
	}

	/**
	 * @testWith ["", "rest_invalid_param"]
	 *           ["fr_FR", "pll_non_unique_slug"]
	 *
	 * @param string $locale
	 * @param string $code
	 * @return void
	 */
	public function test_invalid_param_create( string $locale, string $code ) {
		wp_set_current_user( self::$administrator );
		self::factory()->language->create( array( 'locale' => 'fr_FR' ) );

		$request = new WP_REST_Request( 'POST', '/pll/v1/languages' );
		$request->set_param( 'locale', $locale );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertSame( $code, $data['code'] );
	}
}
