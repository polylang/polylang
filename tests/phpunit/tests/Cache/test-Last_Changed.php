<?php

namespace WP_Syntex\Polylang\Tests\Integration\Cache;

use PLL_Base;
use PLL_Model;
use PLL_Admin;
use PLL_UnitTest_Factory;
use PLL_Object_Cache_TestCase;

/**
 * Covers Polylang's last changed cache occurrences.
 */
class Test_Last_Changed extends PLL_Object_Cache_TestCase {
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
	}

	protected function get_pll_env(): PLL_Base {
		$options = self::create_options( array( 'default_lang' => 'en' ) );
		$model   = new PLL_Model( $options );
		$links   = $model->get_links_model();

		return new PLL_Admin( $links );
	}

	/**
	 * @testWith ["post","posts"]
	 *           ["term","terms"]
	 *
	 * @param string $type       The object type.
	 * @param string $cache_type The cache type.
	 * @return void The void.
	 */
	public function test_set_language_sets_last_changed( string $type, string $cache_type ) {
		$object_id = self::factory()->$type->create();

		$last_changed = wp_cache_get_last_changed( $cache_type );

		$this->assertIsString( $last_changed, 'The last changed time should be a string.' );

		$this->pll_env->model->$type->set_language( $object_id, 'fr' );

		$last_changed_after = wp_cache_get_last_changed( $cache_type );

		$this->assertGreaterThan(
			number_format( (float) $last_changed, 8, '.', '' ),
			number_format( (float) $last_changed_after, 8, '.', '' ),
			'The last changed time should be greater than the previous one.'
		);
	}

	/**
	 * @testWith ["post","posts"]
	 *           ["term","terms"]
	 *
	 * @param string $type       The object type.
	 * @param string $cache_type The cache type.
	 * @return void The void.
	 */
	public function test_set_language_in_mass_sets_last_changed( string $type, string $cache_type ) {
		$object_ids = self::factory()->$type->create_many( 5 );

		$last_changed = wp_cache_get_last_changed( $cache_type );

		$this->assertIsString( $last_changed, 'The last changed time should be a string.' );

		$this->pll_env->model->$type->set_language_in_mass(
			$object_ids,
			$this->pll_env->model->get_language( 'fr' )
		);

		$last_changed_after = wp_cache_get_last_changed( $cache_type );

		$this->assertGreaterThan(
			number_format( (float) $last_changed, 8, '.', '' ),
			number_format( (float) $last_changed_after, 8, '.', '' ),
			'The last changed time should be greater than the previous one.'
		);
	}
}
