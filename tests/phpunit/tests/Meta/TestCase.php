<?php

namespace WP_Syntex\Polylang_Pro\Tests\Integration\modules\Meta;

use PLL_Sync;
use PLL_Model;
use PLL_Admin;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
use Exploit_Object_Serialization;

abstract class TestCase extends PLL_UnitTestCase {
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
	}

	public function set_up() {
		parent::set_up();

		$options             = self::create_options( array( 'default_lang' => 'en' ) );
		$model               = new PLL_Model( $options );
		$links_model         = $model->get_links_model();
		$this->pll_env       = new PLL_Admin( $links_model );
		$this->pll_env->sync = new PLL_Sync( $this->pll_env );
	}

	public function meta_values_provider() {
		return array(
			'string'  => array( 'the_value' ),
			'list'    => array( array( 'the_value', 'the_value_2' ) ),
			'map'     => array( array( 'key' => 'the_value', 'key_2' => 'the_value_2' ) ),
			'nested'  => array( array( 'key' => array( 'subkey' => 'the_value', 'subkey_2' => 'the_value_2' ) ) ),
			'object'  => array( (object) array( 'key' => 'the_value', 'key_2' => 'the_value_2' ) ),
			'number'  => array( 42 ),
			'boolean' => array( true ),
			'null'    => array( null ),
			'empty'   => array( '' ),
			'exploit' => array( serialize( new Exploit_Object_Serialization() ) ),
		);
	}
}
