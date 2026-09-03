<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

use PLL_Admin;
use PLL_Admin_Site_Health;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;

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
	}

	public function set_up() {
		parent::set_up();

		$links_model       = self::$model->get_links_model();
		$this->pll_admin   = new PLL_Admin( $links_model );
		$this->site_health = new PLL_Admin_Site_Health( $this->pll_admin );

		// Assign a language to WordPress' default category ("Uncategorized"), so it doesn't interfere with tests checking terms without a language.
		$this->pll_admin->model->term->set_language( (int) get_option( 'default_category' ), 'en' );
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
}
