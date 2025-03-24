<?php

namespace WP_Syntex\Polylang\Tests\Integration\Performances;

use PLL_UnitTestCase;
use WP_Syntex\Polylang\Options\Options;
use WP_Syntex\Polylang\Options\Registry;

class Single_Site_Test extends PLL_UnitTestCase {
	/**
	 * @ticket #2557
	 * @see https://github.com/polylang/polylang/issues/2557
	 */
	public function test_active_sitewide_plugins_is_not_called() {
		add_action( 'pll_init_options_for_blog', array( Registry::class, 'register' ) );
		new Options();

		$this->assertSame( 0, did_filter( 'site_option_active_sitewide_plugins' ) );
	}
}
