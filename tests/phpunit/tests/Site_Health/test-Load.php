<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

use PLL_Model;
use PLL_Admin;
use PLL_Admin_Site_Health;
use WP_Syntex\Polylang\Options\Options;
use WP_Syntex\Polylang\Model\Languages;

class Load_Test extends TestCase {

	public function test_when_languages_are_configured_site_health_is_loaded() {
		$links_model     = self::$model->get_links_model();
		$polylang = $this->create_admin_and_run_load_script( $links_model );

		$this->assertInstanceOf( PLL_Admin_Site_Health::class, $polylang->site_health, 'Site health should be set when language is configured.' );
	}

	public function test_when_no_languages_are_configured_site_health_is_not_loaded() {
		$options     = new Options();
		$model       = new PLL_Model( $options );
		$model->languages = $this->createMock( Languages::class );
		$model->languages->method( 'has' )->willReturn( false );

		$links_model = $model->get_links_model();
		$polylang = $this->create_admin_and_run_load_script( $links_model );

		$this->assertFalse(
			property_exists( $polylang, 'site_health' ),
			'Site health should not be set when no language is configured.'
		);
	}

	/**
	 * Creates a PLL_Admin instance from the given links model and runs the real
	 * `load.php` script against it, so Site Health is initialized (or not) exactly as it would be in production.
	 *
	 * @param PLL_Model $links_model The links model to build the PLL_Admin instance from.
	 * @return PLL_Admin The PLL_Admin instance, with `site_health` set if `load.php`'s condition was met.
	 */
	private function create_admin_and_run_load_script( $links_model ) {
		$polylang = new PLL_Admin( $links_model );

		require POLYLANG_DIR . '/src/modules/site-health/load.php';

		return $polylang;
	}
}
