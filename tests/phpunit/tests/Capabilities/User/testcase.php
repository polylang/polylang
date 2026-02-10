<?php

namespace WP_Syntex\Polylang\Tests\Integration\modules\Capabilities\User;

use PLL_Model;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;

abstract class TestCase extends PLL_UnitTestCase {
	/**
	 * @var \WP_User
	 */
	protected static $editor;

	/**
	 * @var \WP_User
	 */
	protected static $translator_fr;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 3 );

		self::$editor = $factory->user->create_and_get( array( 'role' => 'editor' ) );

		self::$translator_fr = $factory->user->create_and_get( array( 'role' => 'editor' ) );
		self::$translator_fr->add_cap( 'translate_fr' );
	}

	public function set_up() {
		parent::set_up();

		$options         = $this->create_options( array( 'default_lang' => 'en' ) );
		$this->pll_model = new PLL_Model( $options );
	}

	public static function wpTearDownAfterClass() {
		wp_delete_user( self::$editor->ID );
		wp_delete_user( self::$translator_fr->ID );

		parent::wpTearDownAfterClass();
	}
}
