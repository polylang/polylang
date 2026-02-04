<?php

class Test_Object_Cache extends PLL_Object_Cache_TestCase {
	protected function get_pll_env(): PLL_Base {
		$options = self::create_options( array( 'default_lang' => 'en' ) );
		$model   = new PLL_Model( $options );
		$links   = $model->get_links_model();

		return new PLL_Frontend( $links );
	}

	public function test_object_cache_unavailable() {
		global $wp_object_cache;

		$this->assertInstanceOf( Object_Cache_Annihilator::class, $wp_object_cache, 'The custom object cache should be enabled.' );

		// Let's create languages and ensure they are found in database.
		$wp_object_cache->die();

		$this->assertInstanceOf( WP_Object_Cache::class, $wp_object_cache, 'The custom object cache should be enabled.' );

		self::factory()->language->create_many( 2 );

		/*
		 * We must setup the model like in production and tell the languages model that it's ready.
		 * Otherwise the transient is not set and internal cache is not set... Leading to a false positive.
		 */
		$this->pll_env->model->languages->set_ready();

		$this->pll_env->model->languages->clean_cache();
		$this->assertCount( 2, $this->pll_env->model->languages->get_list(), 'All 2 languages should be available.' );
		$transient_db_value = get_option( '_transient_pll_languages_list' );
		$this->assertIsArray( $transient_db_value );
		$this->assertCount( 2, $transient_db_value, 'The transient should be found in the options table.' );

		// Populate object cache.
		Object_Cache_Annihilator::instance()->resurrect();
		$this->pll_env->model->languages->clean_cache();

		$this->assertCount( 2, $this->pll_env->model->languages->get_list(), 'All 2 languages should be available.' );

		$object_cache_languages = $wp_object_cache->get( 'pll_languages_list', 'transient' );

		$this->assertNotFalse( $object_cache_languages, 'The transient should be available from the cache.' );
		$this->assertIsArray( $object_cache_languages, 'The transient should be an array.' );
		$this->assertSame( 'en', $object_cache_languages[0]['slug'], 'The first language should be en.' );
		$this->assertSame( 'fr', $object_cache_languages[1]['slug'], 'The second language should be fr.' );

		// Let's mess around and create a new language. We have to manually create the language to use only one instance of the model.
		$languages        = include POLYLANG_DIR . '/src/settings/languages.php';
		$de               = $languages['de_DE'];
		$de['slug']       = $de['code'];
		$de['rtl']        = 0;
		$de['term_group'] = 0;
		$this->assertInstanceOf( PLL_Language::class, $this->pll_env->model->languages->add( $de ) );
		$this->assertCount( 3, $this->pll_env->model->languages->get_list(), 'All 3 languages should be available.' );

		$object_cache_languages = $wp_object_cache->get( 'pll_languages_list', 'transient' );

		$this->assertNotFalse( $object_cache_languages );
		$this->assertIsArray( $object_cache_languages );
		$this->assertSame( 'en', $object_cache_languages[0]['slug'], 'The first language should be en.' );
		$this->assertSame( 'fr', $object_cache_languages[1]['slug'], 'The second language should be fr.' );
		$this->assertSame( 'de', $object_cache_languages[2]['slug'], 'The third language should be de.' );

		// Another request, another day, another cache. Let's remove the custom object cache and use the core one.
		// $this->pll_env->model->languages->clean_cache();
		$cache_backup = $wp_object_cache;
		$wp_object_cache->die();
		$wp_object_cache = new WP_Object_Cache();

		/**
		 * This one in particular should fail if we don't force transient deletion in options table.
		 */
		$this->assertFalse( get_option( '_transient_pll_languages_list' ), 'The transient should be deleted from the options table.' );
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
