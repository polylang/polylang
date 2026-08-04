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

	public function test_homepage_test_all_languages_translated() {
		// Arrange
		$home_pages = self::factory()->post->create_translated(
			array( 'post_title' => 'home', 'post_type' => 'page', 'lang' => 'en' ),
			array( 'post_title' => 'accueil', 'post_type' => 'page', 'lang' => 'fr' )
		);
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_pages['en'] );

		// Act
		$test_result = $this->site_health->homepage_test();

		// Assert
		$this->assertSame( array( 'label', 'status', 'badge', 'description', 'actions', 'test' ), array_keys( $test_result ), 'homepage_test() should return the expected array structure.' );
		$this->assertSame(
			'All languages have a translated homepage',
			$test_result['label'],
			'homepage_test() should have the expected label when all languages are translated.'
		);
		$this->assertSame( 'good', $test_result['status'], 'homepage_test() should have a "status" key set to "good".' );
		$this->assertSame(
			array(
				'label' => POLYLANG,
				'color' => 'blue',
			),
			$test_result['badge'],
			'homepage_test() should have the expected badge.'
		);
		$this->assertSame( '<p>It is mandatory to translate the static front page in all languages.</p>', $test_result['description'], 'homepage_test() should have the expected description when all languages are translated.' );
		$this->assertSame( '', $test_result['actions'], 'homepage_test() should have empty actions when all languages are translated.' );
		$this->assertSame( 'pll_homepage', $test_result['test'], 'homepage_test() should have the expected test identifier.' );
	}

	public function test_homepage_test_missing_translation() {
		// Arrange
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$home_en = self::factory()->post->create( array( 'post_title' => 'home', 'post_type' => 'page', 'lang' => 'en' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_en );

		$this->pll_admin->links = new PLL_Admin_Links( $this->pll_admin );

		// Act
		$test_result = $this->site_health->homepage_test();

		// Assert
		$this->assertSame( array( 'label', 'status', 'badge', 'description', 'actions', 'test' ), array_keys( $test_result ), 'homepage_test() should return the expected array structure.' );
		$this->assertSame(
			'The homepage is not translated in all languages',
			$test_result['label'],
			'homepage_test() should have the expected alert label when a languages isn\'t translated.'
		);
		$this->assertSame( 'critical', $test_result['status'], 'homepage_test() should have a "status" key set to "critical".' );
		$this->assertSame(
			array(
				'label' => POLYLANG,
				'color' => 'blue',
			),
			$test_result['badge'],
			'homepage_test() should have the expected badge.'
		);
		$this->assertStringContainsString(
			'You must translate your static front page in',
			$test_result['description'],
			'Description should mention the untranslated languages.'
		);
		$this->assertStringContainsString(
			'>Français</a>',
			$test_result['description'],
			'Description should contain a translation link for the missing language.'
		);
		$this->assertMatchesRegularExpression(
			'/href="[^"]*new_lang=fr[^"]*"/',
			$test_result['description'],
			'Description should contain a link targeting the French translation.'
		);
		$this->assertSame( '', $test_result['actions'], 'homepage_test() should have empty actions when all languages are translated.' );
		$this->assertSame( 'pll_homepage', $test_result['test'], 'homepage_test() should have the expected test identifier.' );
	}
}
