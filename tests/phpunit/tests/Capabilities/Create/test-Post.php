<?php

namespace WP_Syntex\Polylang\Tests\Integration\modules\Capabilities\Create;

use PLL_Language;
use WP_Syntex\Polylang\REST\Request;
use PHPUnit\Framework\MockObject\MockObject;
use WP_Syntex\Polylang\Capabilities\User\NOOP;
use WP_Syntex\Polylang\Capabilities\Create\Post;
use WP_Syntex\Polylang\Capabilities\Capabilities;

use function Patchwork\redefine;

/**
 * @group capabilities
 */
class Test_Post extends TestCase {

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
		$result = $post->get_language();

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

		$this->mock_translator( 'en' );

		$post   = $this->create_post_capa_object();
		$result = $post->get_language( 0 );

		$this->assertSame( 'en', $result->slug );
	}

	public function test_returns_preferred_language_for_translator_not_allowed_to_translate_default() {
		wp_set_current_user( self::$translator_fr->ID );

		$this->mock_translator( 'fr' );

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

		$this->mock_translator( 'fr' );

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
