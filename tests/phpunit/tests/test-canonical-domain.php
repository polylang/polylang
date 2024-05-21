<?php

class Canonical_Domain_Test extends PLL_UnitTestCase {

	protected $canonical;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	protected function init( $domain ) {
		global $wp_rewrite;

		$options = array_merge(
			self::$model->options->get_all(),
			array(
				'hide_default' => true,
				'force_lang'   => 3,
				'domains'      => array(
					'en' => 'http://example.org',
					'fr' => $domain,
				),
			)
		);
		update_option( 'polylang', $options );
		$options = self::create_options();

		// Switch to pretty permalinks.
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '/%postname%/' );

		$model                   = new PLL_Model( $options );
		$this->links_model       = $model->get_links_model();
		$frontend                = new PLL_Frontend( $this->links_model );
		$frontend->links         = new PLL_Frontend_Links( $frontend );
		$frontend->curlang       = $model->get_language( 'fr' );
		$frontend->filters_links = new PLL_Frontend_Filters_Links( $frontend );
		$this->canonical         = new PLL_Canonical( $frontend );

		$GLOBALS['wp_actions']['template_redirect'] = 1; // For the home_url filter.
	}

	/**
	 * Bug fixed in 2.3.5.
	 */
	public function test_redirect_to_www() {
		$this->init( 'http://www.example.fr' );
		$_SERVER['HTTP_HOST'] = 'example.fr';
		$this->assertEquals( 'http://www.example.fr/test/', $this->canonical->check_canonical_url( 'http://' . $_SERVER['HTTP_HOST'] . '/test/', false ) );
	}

	/**
	 * Bug fixed in 2.3.5.
	 */
	public function test_redirect_to_non_www() {
		$this->init( 'http://example.fr' );
		$_SERVER['HTTP_HOST'] = 'www.example.fr';
		$this->assertEquals( 'http://example.fr/test/', $this->canonical->check_canonical_url( 'http://' . $_SERVER['HTTP_HOST'] . '/test/', false ) );
	}
}
