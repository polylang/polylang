<?php

namespace WP_Syntex\Polylang\Tests\Integration\Cache;

use PLL_Base;
use PLL_Model;
use PLL_Admin;
use PLL_UnitTest_Factory;
use Object_Cache_Annihilator;
use PLL_Object_Cache_TestCase;

/**
 * Covers Polylang's object with no lang cache hits.
 */
class Test_Object_With_No_Lang extends PLL_Object_Cache_TestCase {

	/**
	 * @var array<string, int[]>
	 *
	 * Keys are the object types, values are the object ids.
	 */
	protected $object_ids;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
	}

	public function set_up() {
		// Objects with no language must be created **before** the Polylang environment is set up.
		$this->object_ids = array(
			'post' => self::factory()->post->create_many( 5 ),
			'term' => self::factory()->term->create_many( 5 ),
		);

		parent::set_up();
	}

	protected function get_pll_env(): PLL_Base {
		$options = self::create_options( array( 'default_lang' => 'en' ) );
		$model   = new PLL_Model( $options );
		$links   = $model->get_links_model();

		return new PLL_Admin( $links );
	}

	/**
	 * @testWith ["post","posts",-1]
	 *           ["post","posts",25]
	 *           ["term","terms",-1]
	 *           ["term","terms",25]
	 *
	 * @param string $type       The object type.
	 * @param string $cache_type The cache type.
	 * @param int    $limit      The limit.
	 * @return void The void.
	 */
	public function test_get_objects_with_no_lang_hits_cache( string $type, string $cache_type, int $limit ) {
		global $wp_object_cache;

		$lang_ids = array_map(
			function ( $language ) use ( $type ) {
				return $language->get_tax_prop( $this->pll_env->model->$type->get_tax_language(), 'term_taxonomy_id' );
			},
			$this->pll_env->model->languages->get_list()
		);

		$this->assertInstanceOf( Object_Cache_Annihilator::class, $wp_object_cache, 'The custom object cache should be enabled.' );

		$wp_object_cache->flush();

		$object_ids_with_no_lang = $this->pll_env->model->$type->get_objects_with_no_lang( $limit );

		$this->assertLessThanOrEqual( count( $object_ids_with_no_lang ), count( $this->object_ids[ $type ] ), 'The total number of fixtures should be less than or equal to the number of objects with no lang.' );

		foreach ( $this->object_ids [ $type ] as $object_id ) {
			$this->assertContains( $object_id, $object_ids_with_no_lang, "The object id {$object_id} should be in the list of objects with no language." );
		}

		/*
		 * It is usually bad to test using implementation details.
		 * But in this case, it is the only way to test the cache hits since we need to know the cache key...
		 */
		if ( ! function_exists( 'wp_cache_get_salted' ) ) {
			// Backward compatibility with WordPress < 6.9.
			$key          = md5( maybe_serialize( $lang_ids ) . maybe_serialize( array() ) . $limit );
			$last_changed = wp_cache_get_last_changed( $cache_type );
			$cache_key    = "{$cache_type}_no_lang:{$key}:{$last_changed}";
			$object_ids   = $this->pll_env->model->$type->sanitize_int_ids_list(
				wp_cache_get( $cache_key, $cache_type )
			);
		} else {
			$key          = "{$cache_type}_no_lang:" . md5( maybe_serialize( $lang_ids ) . maybe_serialize( array() ) . $limit );
			$last_changed = wp_cache_get_last_changed( $cache_type );
			$object_ids   = $this->pll_env->model->$type->sanitize_int_ids_list(
				wp_cache_get_salted( $key, $cache_type, $last_changed )
			);
		}

		$this->assertSameSets( $object_ids, $object_ids_with_no_lang, 'The object ids should be the same.' );
	}
}
