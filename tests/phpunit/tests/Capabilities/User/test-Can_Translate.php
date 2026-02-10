<?php

namespace WP_Syntex\Polylang\Tests\Integration\modules\Capabilities\User;

use PLL_UnitTest_Factory;
use WP_Syntex\Polylang\Capabilities\User\NOOP;

/**
 * @group capabilities
 * @group user
 */
class Test_Can_Translate extends TestCase {
	/**
	 * @var \WP_User
	 */
	private static $translator_en;

	/**
	 * @var \WP_User
	 */
	private static $translator_fr_de;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		self::$translator_en = $factory->user->create_and_get( array( 'role' => 'editor' ) );
		self::$translator_en->add_cap( 'translate_en' );

		self::$translator_fr_de = $factory->user->create_and_get( array( 'role' => 'editor' ) );
		self::$translator_fr_de->add_cap( 'translate_fr' );
		self::$translator_fr_de->add_cap( 'translate_de' );
	}

	public static function wpTearDownAfterClass() {
		wp_delete_user( self::$translator_en->ID );
		wp_delete_user( self::$translator_fr_de->ID );

		parent::wpTearDownAfterClass();
	}

	/**
	 * @testWith ["en"]
	 *           ["fr"]
	 *           ["de"]
	 *
	 * @param string $lang_slug The language slug.
	 */
	public function test_non_translator_can_translate_to_any_language( string $lang_slug ) {
		$language = $this->pll_model->get_language( $lang_slug );
		$user     = new NOOP( self::$editor );

		$this->assertTrue( $user->can_translate( $language ) );
	}
}
