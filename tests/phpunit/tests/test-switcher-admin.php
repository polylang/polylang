<?php

class Switcher_Admin_Test extends PLL_UnitTestCase {
	private $structure = '/%postname%/';

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once POLYLANG_DIR . '/include/api.php';
		$GLOBALS['polylang'] = &self::$polylang;

		self::$polylang->model->post->register_taxonomy(); // Needed for post counting
	}

	function setUp() {
		global $wp_rewrite;

		parent::setUp();

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );

		self::$polylang->links_model = self::$polylang->model->get_links_model();
		self::$polylang->links_model->init();
		self::$polylang = new PLL_Admin( self::$polylang->links_model );
		self::$polylang->init();

		// flush rules
		$wp_rewrite->flush_rules();

		// De-activate cache for links
		self::$polylang->links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		self::$polylang->links->cache->method( 'get' )->willReturn( false );

	}

	/**
	 * Verify switcher with a API call on admin context.
	 */
	function test_api_on_admin() {

		$args = array( 'echo' => 0, 'hide_if_empty' => 0, 'admin_render' => 1 );

		$switcher = pll_the_languages( $args );
		$this->assertNotEmpty( $switcher );

		$doc = new DomDocument();
		$doc->loadHTML( $switcher );
		$xpath = new DOMXpath( $doc );

		$a = $xpath->query( '//li/a[@lang="en-US"]' );
		$this->assertEquals( pll_home_url( 'en' ), $a->item( 0 )->getAttribute( 'href' ) );

		$a = $xpath->query( '//li/a[@lang="fr-FR"]' );
		$this->assertEquals( pll_home_url( 'fr' ), $a->item( 0 )->getAttribute( 'href' ) );

	}
}
