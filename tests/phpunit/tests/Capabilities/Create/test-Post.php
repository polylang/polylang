<?php

namespace WP_Syntex\Polylang\Tests\Integration\modules\Capabilities\Create;

use WP_User;
use PLL_Model;
use PLL_Language;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
use WP_Syntex\Polylang\REST\Request;
use WP_Syntex\Polylang\Capabilities\Create\Post;
use WP_Syntex\Polylang\Capabilities\Capabilities;
use WP_Syntex\Polylang\Capabilities\User\Creator;
use WP_Syntex\Polylang\Tests\Includes\Mockery\Mock_Translator;

/**
 * @group capabilities
 */
class Test_Post extends PLL_UnitTestCase {
	/**
	 * @var \WP_User
	 */
	private static $translator_fr;

	/**
	 * @var \WP_User
	 */
	private static $translator_en;

	/**
	 * @var \WP_User
	 */
	private static $editor;

	/**
	 * @var Mock_Translator
	 */
	private static $mock_translator;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 3 );

		self::$translator_fr = $factory->user->create_and_get( array( 'role' => 'editor' ) );
		self::$translator_fr->add_cap( 'translate_fr' );

		self::$translator_en = $factory->user->create_and_get( array( 'role' => 'editor' ) );
		self::$translator_en->add_cap( 'translate_en' );

		self::$editor = $factory->user->create_and_get( array( 'role' => 'editor' ) );

		self::$mock_translator = new Mock_Translator( new WP_User() );
	}

	public function set_up() {
		parent::set_up();

		$options         = $this->create_options( array( 'default_lang' => 'en' ) );
		$this->pll_model = new PLL_Model( $options );
	}

	public function tear_down() {
		// Reset user creator after each tests to avoid state bleeding.
		Capabilities::set_user_creator( new Creator() );

		parent::tear_down();
	}

	public static function wpTearDownAfterClass() {
		wp_delete_user( self::$translator_fr->ID );
		wp_delete_user( self::$translator_en->ID );
		wp_delete_user( self::$editor->ID );

		parent::wpTearDownAfterClass();
	}

	/**
	 * @testWith ["en"]
	 *           ["fr"]
	 *           ["de"]
	 *
	 * @param string $lang The language code.
	 */
	public function test_returns_new_lang_from_get_param( string $lang ) {
		$_GET['new_lang'] = $lang;

		wp_set_current_user( self::$editor->ID );

		$post   = $this->create_post_capa_object();
		$result = $post->get_language( 0 );

		$this->assertSame( $lang, $result->slug );
	}

	public function test_returns_default_when_new_lang_is_invalid() {
		$_GET['new_lang'] = 'invalid';

		wp_set_current_user( self::$editor->ID );

		$post   = $this->create_post_capa_object();
		$result = $post->get_language( 0 );

		$this->assertSame( 'en', $result->slug );
	}

	/**
	 * @testWith ["en"]
	 *           ["fr"]
	 *           ["de"]
	 *
	 * @param string $lang The language code.
	 */
	public function test_returns_lang_from_request_param_on_frontend( string $lang ) {
		$_REQUEST['lang'] = $lang;

		// pref_lang is null to simulate frontend context.
		wp_set_current_user( self::$editor->ID );

		$post   = $this->create_post_capa_object();
		$result = $post->get_language( 0 );

		$this->assertSame( $lang, $result->slug );
	}

	public function test_ignores_request_lang_when_pref_lang_is_set() {
		$_REQUEST['lang'] = 'de';

		// pref_lang is set to simulate admin context.
		wp_set_current_user( self::$editor->ID );

		$post   = $this->create_post_capa_object( null, $this->pll_model->languages->get( 'fr' ), null );
		$result = $post->get_language( 0 );

		$this->assertSame( 'fr', $result->slug );
	}

	public function test_returns_language_from_rest_request() {
		$request = $this->createMock( Request::class );
		$request->method( 'get_language' )
			->willReturn( $this->pll_model->languages->get( 'de' ) );

		wp_set_current_user( self::$editor->ID );

		$post   = $this->create_post_capa_object( $request, null, null );
		$result = $post->get_language( 0 );

		$this->assertSame( 'de', $result->slug );
	}

	public function test_returns_parent_language() {
		$parent_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->pll_model->post->set_language( $parent_id, 'fr' );

		$child_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_parent' => $parent_id ) );

		wp_set_current_user( self::$editor->ID );

		$post   = $this->create_post_capa_object();
		$result = $post->get_language( $child_id );

		$this->assertSame( 'fr', $result->slug );
	}

	public function test_returns_pref_lang_when_user_can_translate() {
		wp_set_current_user( self::$editor->ID );

		$post   = $this->create_post_capa_object( null, $this->pll_model->languages->get( 'fr' ), null );
		$result = $post->get_language( 0 );

		$this->assertSame( 'fr', $result->slug );
	}

	public function test_returns_curlang_on_frontend() {
		wp_set_current_user( self::$editor->ID );

		$post   = $this->create_post_capa_object( null, null, $this->pll_model->languages->get( 'de' ) );
		$result = $post->get_language( 0 );

		$this->assertSame( 'de', $result->slug );
	}

	public function test_returns_default_language_for_translator_allowed_to_translate_default() {
		wp_set_current_user( self::$translator_en->ID );

		Capabilities::set_user_creator( self::$mock_translator );

		$post   = $this->create_post_capa_object();
		$result = $post->get_language( 0 );

		$this->assertSame( 'en', $result->slug );
	}

	public function test_returns_preferred_language_for_translator_not_allowed_to_translate_default() {
		wp_set_current_user( self::$translator_fr->ID );

		Capabilities::set_user_creator( self::$mock_translator );

		$post   = $this->create_post_capa_object();
		$result = $post->get_language( 0 );

		$this->assertSame( 'fr', $result->slug );
	}

	public function test_returns_default_language_for_non_translator() {
		wp_set_current_user( self::$editor->ID );

		$post   = $this->create_post_capa_object();
		$result = $post->get_language( 0 );

		$this->assertSame( 'en', $result->slug );
	}

	public function test_pref_lang_is_ignored_when_translator_cannot_translate_it() {
		wp_set_current_user( self::$translator_fr->ID );

		Capabilities::set_user_creator( self::$mock_translator );

		$post   = $this->create_post_capa_object( null, $this->pll_model->languages->get( 'de' ), null );
		$result = $post->get_language( 0 );

		$this->assertSame( 'fr', $result->slug );
	}

	public function test_parent_language_takes_priority_over_pref_lang() {
		$parent_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->pll_model->post->set_language( $parent_id, 'de' );

		$child_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_parent' => $parent_id ) );

		wp_set_current_user( self::$editor->ID );

		$post   = $this->create_post_capa_object( null, $this->pll_model->languages->get( 'fr' ), null );
		$result = $post->get_language( $child_id );

		$this->assertSame( 'de', $result->slug );
	}

	public function test_new_lang_takes_priority_over_parent_language() {
		$_GET['new_lang'] = 'fr';

		$parent_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->pll_model->post->set_language( $parent_id, 'de' );

		$child_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_parent' => $parent_id ) );

		wp_set_current_user( self::$editor->ID );

		$post   = $this->create_post_capa_object( null, $this->pll_model->languages->get( 'en' ), null );
		$result = $post->get_language( $child_id );

		$this->assertSame( 'fr', $result->slug );
	}

	public function test_rest_request_takes_priority_over_parent_language() {
		$request = $this->createMock( Request::class );
		$request->method( 'get_language' )
			->willReturn( $this->pll_model->languages->get( 'fr' ) );

		$parent_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->pll_model->post->set_language( $parent_id, 'de' );

		$child_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_parent' => $parent_id ) );

		wp_set_current_user( self::$editor->ID );

		$post   = $this->create_post_capa_object( $request, null, null );
		$result = $post->get_language( $child_id );

		$this->assertSame( 'fr', $result->slug );
	}

	/**
	 * Creates a Post object for testing.
	 *
	 * @param Request|null       $request   The request mock or null. Default will create a mock with `Request::get_language` method returning null.
	 * @param \PLL_Language|null $pref_lang The preferred language. Default null.
	 * @param \PLL_Language|null $curlang   The current language. Default null.
	 * @return Post
	 */
	private function create_post_capa_object(
		?Request $request = null,
		?PLL_Language $pref_lang = null,
		?PLL_Language $curlang = null
	): Post {
		if ( null === $request ) {
			$request = $this->createMock( Request::class );
			$request->method( 'get_language' )
				->willReturn( null );
		}

		return new Post( $this->pll_model, $request, $pref_lang, $curlang );
	}
}
