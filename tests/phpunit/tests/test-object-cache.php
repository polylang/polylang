<?php

use WP_Syntex\Object_Cache_Annihilator;

class Test_Object_Cache extends PLL_UnitTestCase {
	/**
	 * @var WP_Object_Cache
	 */
	private $cache_backup;

	public function set_up() {
		global $wp_object_cache;

		parent::set_up();

		$this->cache_backup = $wp_object_cache;

		self::factory()->language->create_many( 2 );

		// Drop in the annihilator.
		copy( PLL_TEST_DATA_DIR . 'object-cache-annihilator.php', WP_CONTENT_DIR . '/object-cache.php' );
		wp_using_ext_object_cache( true );
		$wp_object_cache = new Object_Cache_Annihilator();

		$options = self::create_options( array( 'default_lang' => 'en' ) );
		$model   = new PLL_Model( $options );
		$links   = $model->get_links_model();
		$this->pll_env = new PLL_Frontend( $links );
		$this->pll_env->init();
	}

	public function tear_down() {
		global $wp_object_cache;

		// Annihilate the annihilator.
		$wp_object_cache->die();
		unlink( WP_CONTENT_DIR . '/object-cache.php' );
		$wp_object_cache = $this->cache_backup;

		parent::tear_down();
	}

	public function test_object_cache_unavailable() {
		global $wp_object_cache;

		$this->assertInstanceOf( Object_Cache_Annihilator::class, $wp_object_cache, 'The custom object cache should be enabled.' );

		/*
		 * We must setup the model like in production and tell the languages model that it's ready.
		 * Otherwise the transient is not set and internal cache is not set... Leading to a false positive.
		 */
		$this->pll_env->model->languages->set_ready();

		// Populate cache.
		$this->assertTrue( $wp_object_cache->flush() );
		$this->pll_env->model->languages->clean_cache();

		$this->assertCount( 2, $this->pll_env->model->languages->get_list(), 'All 2 languages should be available.' );

		$object_cache_languages = $wp_object_cache->get( 'pll_languages_list', 'transient' );

		$this->assertNotFalse( $object_cache_languages, 'The transient should be available from the cache.' );
		$this->assertIsArray( $object_cache_languages, 'The transient should be an array.' );
		$this->assertSame( 'en', $object_cache_languages[0]['slug'], 'The first language should be en.' );
		$this->assertSame( 'fr', $object_cache_languages[1]['slug'], 'The second language should be fr.' );

		// Let's mess around and create a new language. We have to manually create the language to use only one instance of the model.
		$languages        = include POLYLANG_DIR . '/settings/languages.php';
		$de               = $languages['de_DE'];
		$de['slug']       = $de['code'];
		$de['rtl']        = 0;
		$de['term_group'] = 0;
		$this->assertTrue( $this->pll_env->model->languages->add( $de ) );
		$this->assertCount( 3, $this->pll_env->model->languages->get_list(), 'All 3 languages should be available.' );

		$object_cache_languages = $wp_object_cache->get( 'pll_languages_list', 'transient' );

		$this->assertNotFalse( $object_cache_languages );
		$this->assertIsArray( $object_cache_languages );
		$this->assertSame( 'en', $object_cache_languages[0]['slug'], 'The first language should be en.' );
		$this->assertSame( 'fr', $object_cache_languages[1]['slug'], 'The second language should be fr.' );
		$this->assertSame( 'de', $object_cache_languages[2]['slug'], 'The third language should be de.' );

		// Another request, another day, another cache. Let's remove the custom object cache and use the core one.
		$cache_backup = $wp_object_cache;
		$wp_object_cache->die();
		$wp_object_cache = new WP_Object_Cache();

		/**
		 * This one in particular should fail if we don't force transient deletion in options table.
		 */
		$this->assertCount( 3, get_option( '_transient_pll_languages_list' ), 'The transient should be deleted from the options table.' );
		$this->assertCount( 3, $this->pll_env->model->languages->get_list(), 'All 3 languages should be available.' );

		// Surprise, surprise, the annihilator is back. Ensure data is not lost.
		$cache_backup->resurrect();
		$this->pll_env->model->languages->clean_cache();

		$this->assertInstanceOf( Object_Cache_Annihilator::class, $wp_object_cache, 'The custom object cache should be enabled.' );

		$this->assertCount( 3, $this->pll_env->model->languages->get_list(), 'All 3 languages should be available.' );

		$object_cache_languages = $wp_object_cache->get( 'pll_languages_list', 'transient' );

		$this->assertNotFalse( $object_cache_languages, 'The transient should be available from the cache.' );
		$this->assertIsArray( $object_cache_languages, 'The transient should be an array.' );
		$this->assertSame( 'en', $object_cache_languages[0]['slug'], 'The first language should be en.' );
		$this->assertSame( 'fr', $object_cache_languages[1]['slug'], 'The second language should be fr.' );
	}
}
