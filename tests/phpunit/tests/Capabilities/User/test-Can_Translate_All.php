<?php

namespace WP_Syntex\Polylang\Tests\Integration\modules\Capabilities\User;

use PLL_UnitTest_Factory;
use WP_Syntex\Polylang\Capabilities\User\NOOP;

/**
 * @group capabilities
 * @group user
 */
class Test_Can_Translate_All extends TestCase {
	/**
	 * @var \WP_User
	 */
	private static $translator_fr_de;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		self::$translator_fr_de = $factory->user->create_and_get( array( 'role' => 'editor' ) );
		self::$translator_fr_de->add_cap( 'translate_fr' );
		self::$translator_fr_de->add_cap( 'translate_de' );
	}

	public static function wpTearDownAfterClass() {
		wp_delete_user( self::$translator_fr_de->ID );

		parent::wpTearDownAfterClass();
	}

	/**
	 * @testWith [["en", "fr", "de"]]
	 *           [["en"]]
	 *           [[]]
	 *
	 * @param string[] $languages The language slugs.
	 */
	public function test_non_translator_can_translate_all( array $languages ) {
		$user = new NOOP( self::$editor );

		$this->assertTrue( $user->can_translate_all( $languages ) );
	}
}
