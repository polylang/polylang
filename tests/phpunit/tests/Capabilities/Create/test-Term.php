<?php

namespace WP_Syntex\Polylang\Tests\Integration\modules\Capabilities\Create;

use PLL_Language;
use WP_Syntex\Polylang\REST\Request;
use WP_Syntex\Polylang\Capabilities\Create\Term;

/**
 * @group capabilities
 */
class Test_Term extends TestCase {
	/**
	 * @testWith ["en"]
	 *           ["fr"]
	 *
	 * @param string $lang The language code.
	 */
	public function test_returns_new_lang_from_get_param( string $lang ) {
		$_GET['new_lang'] = $lang;

		$term   = $this->create_term_capa_object();
		$result = $term->get_language();

		$this->assertSame( $lang, $result->slug );
	}

	public function test_returns_default_when_new_lang_is_invalid() {
		$_GET['new_lang'] = 'invalid';

		$term   = $this->create_term_capa_object();
		$result = $term->get_language();

		$this->assertSame( 'en', $result->slug );
	}

	/**
	 * @testWith ["en"]
	 *           ["fr"]
	 *
	 * @param string $lang The language code.
	 */
	public function test_returns_lang_from_term_lang_choice_post_param( string $lang ) {
		$_POST['term_lang_choice'] = $lang;

		$term   = $this->create_term_capa_object();
		$result = $term->get_language();

		$this->assertSame( $lang, $result->slug );
	}

	public function test_returns_default_when_term_lang_choice_is_invalid() {
		$_POST['term_lang_choice'] = 'invalid';

		$term   = $this->create_term_capa_object();
		$result = $term->get_language();

		$this->assertSame( 'en', $result->slug );
	}

	/**
	 * @testWith ["en"]
	 *           ["fr"]
	 *
	 * @param string $lang The language code.
	 */
	public function test_returns_lang_from_inline_lang_choice_post_param( string $lang ) {
		$_POST['inline_lang_choice'] = $lang;

		$term   = $this->create_term_capa_object();
		$result = $term->get_language();

		$this->assertSame( $lang, $result->slug );
	}

	public function test_returns_default_when_inline_lang_choice_is_invalid() {
		$_POST['inline_lang_choice'] = 'invalid';

		$term   = $this->create_term_capa_object();
		$result = $term->get_language();

		$this->assertSame( 'en', $result->slug );
	}

	/**
	 * @testWith ["en"]
	 *           ["fr"]
	 *
	 * @param string $lang The language code.
	 */
	public function test_returns_lang_from_request_param_on_frontend( string $lang ) {
		$_REQUEST['lang'] = $lang;

		// pref_lang is null to simulate frontend context.
		$term   = $this->create_term_capa_object();
		$result = $term->get_language();

		$this->assertSame( $lang, $result->slug );
	}

	public function test_returns_language_from_rest_request() {
		$request = $this->createMock( Request::class );
		$request->method( 'get_language' )
			->willReturn( self::$german );

		$term   = $this->create_term_capa_object( $request, null, null );
		$result = $term->get_language();

		$this->assertSame( 'de', $result->slug );
	}

	public function test_returns_parent_language() {
		$parent_id = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		$this->pll_model->term->set_language( $parent_id, 'fr' );

		$child_id = self::factory()->term->create( array( 'taxonomy' => 'category', 'parent' => $parent_id ) );

		$term   = $this->create_term_capa_object();
		$result = $term->get_language( $child_id, 'category' );

		$this->assertSame( 'fr', $result->slug );
	}

	public function test_returns_curlang_on_frontend() {
		$term   = $this->create_term_capa_object( null, null, self::$german );
		$result = $term->get_language();

		$this->assertSame( 'de', $result->slug );
	}

	public function test_returns_default_language_for_translator_allowed_to_translate_default() {
		$this->mock_translator( 'en' );

		$term   = $this->create_term_capa_object();
		$result = $term->get_language();

		$this->assertSame( 'en', $result->slug );
	}

	public function test_returns_preferred_language_for_translator_not_allowed_to_translate_default() {
		$this->mock_translator( 'fr' );

		$term   = $this->create_term_capa_object();
		$result = $term->get_language();

		$this->assertSame( 'fr', $result->slug );
	}

	public function test_returns_default_language_for_non_translator() {
		$term   = $this->create_term_capa_object();
		$result = $term->get_language();

		$this->assertSame( 'en', $result->slug );
	}

	public function test_new_lang_takes_priority_over_parent_language() {
		$_GET['new_lang'] = 'fr';

		$parent_id = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		$this->pll_model->term->set_language( $parent_id, 'de' );

		$child_id = self::factory()->term->create( array( 'taxonomy' => 'category', 'parent' => $parent_id ) );

		$term   = $this->create_term_capa_object( null, self::$english, null );
		$result = $term->get_language( $child_id, 'category' );

		$this->assertSame( 'fr', $result->slug );
	}

	public function test_rest_request_takes_priority_over_parent_language() {
		$request = $this->createMock( Request::class );
		$request->method( 'get_language' )
			->willReturn( self::$french );

		$parent_id = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		$this->pll_model->term->set_language( $parent_id, 'de' );

		$child_id = self::factory()->term->create( array( 'taxonomy' => 'category', 'parent' => $parent_id ) );

		$term   = $this->create_term_capa_object( $request, null, null );
		$result = $term->get_language( $child_id, 'category' );

		$this->assertSame( 'fr', $result->slug );
	}

	public function test_term_lang_choice_takes_priority_over_inline_lang_choice() {
		$_POST['term_lang_choice']   = 'fr';
		$_POST['inline_lang_choice'] = 'de';

		$term   = $this->create_term_capa_object();
		$result = $term->get_language();

		$this->assertSame( 'fr', $result->slug );
	}

	public function test_new_lang_takes_priority_over_term_lang_choice() {
		$_GET['new_lang']          = 'de';
		$_POST['term_lang_choice'] = 'fr';

		$term   = $this->create_term_capa_object();
		$result = $term->get_language();

		$this->assertSame( 'de', $result->slug );
	}

	public function test_returns_pref_lang_when_user_can_translate() {
		$this->mock_translator( 'fr' );

		$term   = $this->create_term_capa_object( null, self::$french, null );
		$result = $term->get_language();

		$this->assertSame( 'fr', $result->slug );
	}

	public function test_pref_lang_is_ignored_when_translator_cannot_translate_it() {
		$this->mock_translator( 'fr' );

		$term   = $this->create_term_capa_object( null, self::$german, null );
		$result = $term->get_language();

		$this->assertSame( 'fr', $result->slug );
	}

	public function test_parent_language_takes_priority_over_pref_lang() {
		$parent_id = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		$this->pll_model->term->set_language( $parent_id, 'de' );

		$child_id = self::factory()->term->create( array( 'taxonomy' => 'category', 'parent' => $parent_id ) );

		$term   = $this->create_term_capa_object( null, self::$french, null );
		$result = $term->get_language( $child_id, 'category' );

		$this->assertSame( 'de', $result->slug );
	}

	/**
	 * Creates a Term object for testing.
	 *
	 * @param Request|null       $request   The request mock or null. Default will create a mock with `Request::get_language` method returning null.
	 * @param \PLL_Language|null $pref_lang The preferred language. Default null.
	 * @param \PLL_Language|null $curlang   The current language. Default null.
	 * @return Term
	 */
	private function create_term_capa_object(
		?Request $request = null,
		?PLL_Language $pref_lang = null,
		?PLL_Language $curlang = null
	): Term {
		if ( null === $request ) {
			$request = $this->createMock( Request::class );
			$request->method( 'get_language' )
				->willReturn( null );
		}

		return new Term( $this->pll_model, $request, $pref_lang, $curlang );
	}
}
