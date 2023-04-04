<?php
/**
 * @package Polylang
 */

/**
 * Class Admin_Site_Health_Test
 */
class Admin_Site_Health_Test extends PLL_UnitTestCase {

	/**
	 * @var PLL_Admin_Site_Health
	 */
	private $site_health;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	/**
	 * Performs setup tasks for every test.
	 */
	public function set_up() {
		parent::set_up();

		$links_model     = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );

		$this->site_health = new PLL_Admin_Site_Health( $this->pll_admin );
	}

	public function test_info_languages_term_props() {
		$info = $this->site_health->info_languages( array() );

		$this->assertIsArray( $info, 'Info should be an array.' );
		$this->assertCount( 2, $info, 'Info should contain two elements.' );

		$this->assertArrayHasKey( 'pll_language_en', $info, 'Info should have an entry with pll_language_en key.' );
		$this->assertArrayHasKey( 'pll_language_fr', $info, 'Info should have an entry with pll_language_fr key.' );
		$this->assertArrayHasKey( 'term_props', $info['pll_language_en']['fields'], 'Info should have an entry with term_props key.' );

		$info = $info['pll_language_en']['fields'];
		$this->assertSame( 'term_props', $info['term_props']['label'], 'The label of the term_props entry should be term_props' );

		$this->assertIsArray( $info['term_props']['value'], 'This should be an array' );
		$this->assertCount( 6, $info['term_props']['value'], 'This should contain 6 elements.' );

		$this->assertArrayHasKey( 'term_language/term_id', $info['term_props']['value'], 'The value of the term_props entry should have an entry with term_language/term_id key.' );
		$this->assertArrayHasKey( 'term_language/term_taxonomy_id', $info['term_props']['value'], 'The value of the term_props entry should have an entry with term_language/term_taxonomy_id key.' );
		$this->assertArrayHasKey( 'term_language/count', $info['term_props']['value'], 'The value of the term_props entry should have an entry with term_language/count key.' );
		$this->assertArrayHasKey( 'language/term_id', $info['term_props']['value'], 'The value of the term_props entry should have an entry with language/term_id key.' );
		$this->assertArrayHasKey( 'language/term_taxonomy_id', $info['term_props']['value'], 'The value of the term_props entry should have an entry with language/term_taxonomy_id key.' );
		$this->assertArrayHasKey( 'language/count', $info['term_props']['value'], 'The value of the term_props entry should have an entry with language/count key.' );

		$en = $this->pll_admin->model->get_language( 'en' );
		$this->assertSame( $en->get_tax_prop( 'language', 'term_id' ), $info['term_props']['value']['language/term_id'] );
		$this->assertSame( $en->get_tax_prop( 'language', 'term_taxonomy_id' ), $info['term_props']['value']['language/term_taxonomy_id'] );
		$this->assertSame( $en->get_tax_prop( 'language', 'count' ), $info['term_props']['value']['language/count'] );
		$this->assertSame( $en->get_tax_prop( 'term_language', 'term_id' ), $info['term_props']['value']['term_language/term_id'] );
		$this->assertSame( $en->get_tax_prop( 'term_language', 'term_taxonomy_id' ), $info['term_props']['value']['term_language/term_taxonomy_id'] );
		$this->assertSame( $en->get_tax_prop( 'term_language', 'count' ), $info['term_props']['value']['term_language/count'] );
	}
}
