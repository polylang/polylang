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

		@mkdir( WP_CONTENT_DIR . '/polylang' );
		copy(
			PLL_TEST_DATA_DIR . 'wpml-config.xml',
			WP_CONTENT_DIR . '/polylang/wpml-config.xml'
		);

		// Force a fresh scan: another test class may already have cached
		// an empty file list before this one copied wpml-config.xml.
		$files_reflection = new ReflectionProperty( PLL_WPML_Config::class, 'files' );
		$files_reflection->setValue( PLL_WPML_Config::instance(), null );
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
}
