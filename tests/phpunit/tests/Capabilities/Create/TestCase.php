<?php

namespace WP_Syntex\Polylang\Tests\Integration\modules\Capabilities\Create;

use PLL_Model;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
use PHPUnit\Framework\MockObject\MockObject;
use WP_Syntex\Polylang\Capabilities\User\NOOP;
use WP_Syntex\Polylang\Capabilities\Capabilities;

use function Patchwork\redefine;
use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;

abstract class TestCase extends PLL_UnitTestCase {
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 3 );
	}

	public function set_up() {
		parent::set_up();

		setUp();

		$options         = $this->create_options( array( 'default_lang' => 'en' ) );
		$this->pll_model = new PLL_Model( $options );
	}

	public function test_tear_down() {
		tearDown();

		parent::tear_down();
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
