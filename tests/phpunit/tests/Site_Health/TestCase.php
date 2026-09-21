<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

use PLL_Admin;
use WP_Debug_Data;
use PLL_WPML_Config;
use PLL_UnitTestCase;
use ReflectionProperty;
use PLL_UnitTest_Factory;
use PLL_Admin_Site_Health;

use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;

abstract class TestCase extends PLL_UnitTestCase {

	/**
	 * @var PLL_Admin_Site_Health
	 */
	protected $site_health;

	/**
	 * @var PLL_Admin
	 */
	protected $pll_admin;

	/**
	 * @param PLL_UnitTest_Factory $factory
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );

		// Ensure the fixture exists and force PLL_WPML_Config to rescan the file.
		self::restore_wpml_config();
	}

	public function set_up() {
		parent::set_up();

		setUp();

		$links_model            = self::$model->get_links_model();
		$this->pll_admin        = new PLL_Admin( $links_model );
		$this->site_health      = new PLL_Admin_Site_Health( $this->pll_admin );

		// Assign a language to WordPress' default category ("Uncategorized"), so it doesn't interfere with tests checking terms without a language.
		$this->pll_admin->model->term->set_language( (int) get_option( 'default_category' ), 'en' );
	}

	public function tear_down() {
		tearDown();

		parent::tear_down();
	}

	public static function wpTearDownAfterClass() {
		parent::wpTearDownAfterClass();

		unlink( WP_CONTENT_DIR . '/polylang/wpml-config.xml' );
		rmdir( WP_CONTENT_DIR . '/polylang' );
	}

	/**
	 * Configures WordPress to use a static front page.
	 *
	 * @param int    $page ID of the page to use as front page.
	 * @param string $show Value for 'show_on_front'. Default 'page'.
	 * @return void
	 */
	protected function set_page_on_front( int $page, string $show = 'page' ): void {
		update_option( 'show_on_front', $show );
		update_option( 'page_on_front', $page );
	}

	/**
	 * Retrieves WordPress debug information.
	 *
	 * @return array The debug information.
	 */
	protected function get_debug_info() {
		require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';

		return WP_Debug_Data::debug_data();
	}

	/**
	 * Resets the cached wpml-config.xml file list.
	 *
	 * @return void
	 */
	protected static function reset_wpml_files_cache() {
		$files_reflection = new ReflectionProperty( PLL_WPML_Config::class, 'files' );

		// `setAccessible()` is required before PHP 8.1 to access non-public properties,
		// but is deprecated in recent PHP versions where properties are accessible by default.
		if ( PHP_VERSION_ID < 80100 ) {
			$files_reflection->setAccessible( true );
		}

		$files_reflection->setValue( PLL_WPML_Config::instance(), null );
	}

	/**
	 * Removes the wpml-config.xml file used by the test fixtures and resets
	 * `PLL_WPML_Config`'s internal file cache so it rescans the disk.
	 *
	 * @return void
	 */
	protected static function remove_wpml_config() {
		unlink( WP_CONTENT_DIR . '/polylang/wpml-config.xml' );
		rmdir( WP_CONTENT_DIR . '/polylang' );

		self::reset_wpml_files_cache();
	}

	/**
	 * Restores the wpml-config.xml file previously removed by `remove_wpml_config()`
	 * and resets the cached file list so the next call to `PLL_WPML_Config::get_files()`
	 * sees the restored file instead of the stale cached result.
	 *
	 * @return void
	 */
	protected static function restore_wpml_config() {
		if ( file_exists( WP_CONTENT_DIR . '/polylang/wpml-config.xml' ) ) {
			return;
		}

		@mkdir( WP_CONTENT_DIR . '/polylang' );
		copy(
			PLL_TEST_DATA_DIR . 'wpml-config.xml',
			WP_CONTENT_DIR . '/polylang/wpml-config.xml'
		);

		self::reset_wpml_files_cache();
	}
}
