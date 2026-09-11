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
		$polylang = new PLL_Admin( $links_model );

		require POLYLANG_DIR . '/src/modules/site-health/load.php';

		$this->assertInstanceOf( PLL_Admin_Site_Health::class, $polylang->site_health, 'Site health should be set when language is configured.' );
	}

	public function test_when_no_languages_are_configured_site_health_is_not_loaded() {
		$options     = new Options();
		$model       = new PLL_Model( $options );
		$model->languages = $this->createMock( Languages::class );
		$model->languages->method( 'has' )->willReturn( false );

		$links_model = $model->get_links_model();
		$polylang    = new PLL_Admin( $links_model );

		require POLYLANG_DIR . '/src/modules/site-health/load.php';

		$this->assertFalse(
			property_exists( $polylang, 'site_health' ),
			'Site health should not be set when no language is configured.'
		);
	}
}
