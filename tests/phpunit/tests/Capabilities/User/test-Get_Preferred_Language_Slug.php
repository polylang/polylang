<?php

namespace WP_Syntex\Polylang\Tests\Integration\modules\Capabilities\User;

use PLL_UnitTest_Factory;
use WP_Syntex\Polylang\Capabilities\User\NOOP;

/**
 * @group capabilities
 * @group user
 */
class Test_Get_Preferred_Language_Slug extends TestCase {
	/**
	 * @var \WP_User
	 */
	private static $translator_de_en;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		self::$translator_de_en = $factory->user->create_and_get( array( 'role' => 'editor' ) );
		self::$translator_de_en->add_cap( 'translate_de' );
		self::$translator_de_en->add_cap( 'translate_en' );
	}

	public static function wpTearDownAfterClass() {
		wp_delete_user( self::$translator_de_en->ID );

		parent::wpTearDownAfterClass();
	}

	public function test_returns_empty_string_when_user_has_no_language_caps() {
		$user = new NOOP( self::$editor );

		$this->assertSame( '', $user->get_preferred_language_slug(), 'The preferred language slug should be empty when the user has no language caps.' );
	}
}
