<?php

/**
 * Test case for object cache related tests.
 * Takes care of setting up the annihilator and restoring the original object cache as well as deleting object cache files.
 */
abstract class PLL_Object_Cache_TestCase extends PLL_UnitTestCase {
	/**
	 * @var WP_Object_Cache
	 */
	protected static $cache_backup;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		global $wp_object_cache;

		parent::pllSetUpBeforeClass( $factory );

		self::$cache_backup = $wp_object_cache;

		// Register shutdown function to cleanup the annihilator in case of fatal error.
		register_shutdown_function( Closure::fromCallable( array( self::class, 'remove_annihilator' ) ) );
	}


	public function set_up() {
		global $wp_object_cache;

		parent::set_up();

		// Drop in the annihilator.
		require_once $this->get_root_dir() . '/vendor/wpsyntex/object-cache-annihilator/drop-in.php';
		copy( $this->get_root_dir() . '/vendor/wpsyntex/object-cache-annihilator/drop-in.php', WP_CONTENT_DIR . '/object-cache.php' );
		wp_using_ext_object_cache( true );
		$wp_object_cache = new Object_Cache_Annihilator();

		$this->pll_env = $this->get_pll_env();
		$this->pll_env->init();
	}

	public static function wpTearDownAfterClass() {
		self::remove_annihilator();

		parent::wpTearDownAfterClass();
	}

	/**
	 * Removes the Object Cache Annihilator drop-in, its cache files and restores the original object cache.
	 *
	 * @return void
	 */
	protected static function remove_annihilator() {
		global $wp_object_cache;

		// Annihilate the annihilator.
		Object_Cache_Annihilator::instance()->die();

		if ( file_exists( WP_CONTENT_DIR . '/object-cache.php' ) ) {
			unlink( WP_CONTENT_DIR . '/object-cache.php' );
		}

		$wp_object_cache = self::$cache_backup;
	}

	/**
	 * Gets the root directory of the plugin.
	 *
	 * @return string The root directory of the plugin.
	 */
	protected function get_root_dir(): string {
		return POLYLANG_DIR;
	}

	/**
	 * Sets up the environment.
	 * Creates appropriate Polylang objects for the tests.
	 *
	 * @return PLL_Base The Polylang environment.
	 */
	abstract protected function get_pll_env(): PLL_Base;
}
