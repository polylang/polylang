<?php

namespace WP_Syntex\Polylang\Tests\Integration\modules\Capabilities\Create;

use WP_User;
use PLL_Model;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
use PHPUnit\Framework\MockObject\MockObject;
use WP_Syntex\Polylang\Capabilities\User\NOOP;
use WP_Syntex\Polylang\Capabilities\Capabilities;
use WP_Syntex\Polylang\Capabilities\User\Creator;

use function Patchwork\redefine;
use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;

abstract class TestCase extends PLL_UnitTestCase {
	/**
	 * @var WP_User
	 */
	protected static $translator_fr;

	/**
	 * @var WP_User
	 */
	protected static $translator_en;

	/**
	 * @var WP_User
	 */
	protected static $editor;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 3 );

		self::$translator_fr = $factory->user->create_and_get( array( 'role' => 'editor' ) );
		self::$translator_fr->add_cap( 'translate_fr' );

		self::$translator_en = $factory->user->create_and_get( array( 'role' => 'editor' ) );
		self::$translator_en->add_cap( 'translate_en' );

		self::$editor = $factory->user->create_and_get( array( 'role' => 'editor' ) );
	}

	public function set_up() {
		parent::set_up();

		setUp();

		$options         = $this->create_options( array( 'default_lang' => 'en' ) );
		$this->pll_model = new PLL_Model( $options );
	}

	public static function wpTearDownAfterClass() {
		wp_delete_user( self::$translator_fr->ID );
		wp_delete_user( self::$translator_en->ID );
		wp_delete_user( self::$editor->ID );

		parent::wpTearDownAfterClass();
	}

	/**
	 * Mocks a translator object and redefine the `Capabilities::get_user` method to return it.
	 *
	 * @param string $slug The language slug.
	 * @return MockObject
	 */
	protected function mock_translator( string $slug ): MockObject {
		$translator_mock = $this->getMockBuilder( NOOP::class )
			->disableOriginalConstructor()
			->getMock();
		$translator_mock
			->method( 'is_translator' )
			->willReturn( true );
		$translator_mock
			->method( 'can_translate' )
			->willReturnCallback( fn( $language ) => $slug === $language->slug );
		$translator_mock
			->method( 'get_preferred_language_slug' )
			->willReturn( $slug );

		// Brain\Monkey API doesn't support static methods mocking, so we need to use Patchwork.
		redefine( Capabilities::class . '::get_user', fn() => $translator_mock );

		return $translator_mock;
	}
}
