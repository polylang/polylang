<?php

namespace WP_Syntex\Polylang\Tests\Integration\modules\Capabilities\User;

use WPDieException;
use WP_Syntex\Polylang\Capabilities\User\NOOP;

/**
 * @group capabilities
 * @group user
 */
class Test_Can_Translate_Or_Die extends TestCase {
	/**
	 * @testWith ["en"]
	 *           ["fr"]
	 *
	 * @param string $lang_slug The language slug.
	 */
	public function test_non_translator_does_not_die( string $lang_slug ) {
		$language = $this->pll_model->get_language( $lang_slug );
		$user     = new NOOP( self::$editor );

		$this->expectNotToPerformAssertions();
		try {
			$user->can_translate_or_die( $language );
		} catch ( WPDieException $e ) {
			$this->fail( "The user should not die :'(." );
		}
	}
}
