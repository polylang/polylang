<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

use PLL_Admin;
use PLL_Admin_Site_Health;
use PLL_Context_Admin;
use WP_Syntex\Polylang\Model\Languages;

class Load_Test extends TestCase {

	public function test_when_languages_are_configured_site_health_is_loaded() {
		$polylang = $this->create_admin();

		require POLYLANG_DIR . '/src/modules/site-health/load.php';

		$this->assertInstanceOf(
			PLL_Admin_Site_Health::class,
			$polylang->site_health,
			'Site health should be set when language is configured.'
		);
	}

	public function test_when_no_languages_are_configured_site_health_is_not_loaded() {
		$polylang = $this->create_admin();

		$polylang->model->languages = $this->createMock( Languages::class );
		$polylang->model->languages->method( 'has' )->willReturn( false );

		require POLYLANG_DIR . '/src/modules/site-health/load.php';

		$this->assertFalse(
			property_exists( $polylang, 'site_health' ),
			'Site health should not be set when no language is configured.'
		);
	}

	/**
	 * Creates a PLL_Admin instance.
	 *
	 * @return PLL_Admin The PLL_Admin instance.
	 */
	private function create_admin(): PLL_Admin {
		$context = new PLL_Context_Admin();

		return $context->get();
	}
}
