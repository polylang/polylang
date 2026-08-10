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

		// Assign a language to WordPress' default category ("Uncategorized"), so it doesn't interfere with tests checking terms without a language.
		$this->pll_admin->model->term->set_language( (int) get_option( 'default_category' ), 'en' );
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
		// Translation URLs require a logged-in user with the correct capabilities.
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
			'homepage_test() should have the expected alert label when not all languages are translated.'
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
		$this->assertSame( '', $test_result['actions'], 'homepage_test() should have empty actions when a language is not translated.' );
		$this->assertSame( 'pll_homepage', $test_result['test'], 'homepage_test() should have the expected test identifier.' );
	}

	public function test_homepage_test_when_front_page_does_not_exist() {
		// Arrange
		$deleted_page_id = self::factory()->post->create( array( 'post_type' => 'page', 'lang' => 'en' ) );
		wp_delete_post( $deleted_page_id, true );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $deleted_page_id );

		// Act
		$test_result = $this->site_health->homepage_test();

		// Assert
		$this->assertSame( 'good', $test_result['status'], 'homepage_test() should return "good" when the front page no longer exists.' );
	}

	public function test_status_tests_adds_pll_homepage_test_when_static_front_page_is_set() {
		// Arrange
		$home_en = self::factory()->post->create( array( 'post_title' => 'home', 'post_type' => 'page', 'lang' => 'en' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_en );

		// Act
		$result = $this->site_health->status_tests( array() );

		// Assert
		$this->assertArrayHasKey( 'direct', $result, 'status_tests() should add a "direct" key.' );
		$this->assertArrayHasKey( 'pll_homepage', $result['direct'], 'status_tests() should add a "pll_homepage" entry when a static front page is set.' );
		$this->assertSame(
			'Homepage translated',
			$result['direct']['pll_homepage']['label'],
			'The "pll_homepage" test should have the expected label.'
		);
		$this->assertSame(
			array( $this->site_health, 'homepage_test' ),
			$result['direct']['pll_homepage']['test'],
			'The "pll_homepage" test should reference the homepage_test() callback.'
		);
	}

	public function test_status_tests_preserves_existing_tests_when_static_front_page_is_set() {
		// Arrange
		$home_en = self::factory()->post->create( array( 'post_title' => 'home', 'post_type' => 'page', 'lang' => 'en' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_en );

		$existing_tests = array(
			'direct' => array(
				'other_test' => array(
					'label' => 'Some other test',
					'test'  => '__return_true',
				),
			),
		);

		// Act
		$result = $this->site_health->status_tests( $existing_tests );

		// Assert
		$this->assertArrayHasKey( 'other_test', $result['direct'], 'Existing tests should not be overwritten.' );
		$this->assertSame( $existing_tests['direct']['other_test'], $result['direct']['other_test'], 'Existing test data should remain unchanged.' );
		$this->assertArrayHasKey( 'pll_homepage', $result['direct'], 'pll_homepage should still be added alongside existing tests.' );
	}

	public function test_status_tests_does_not_add_pll_homepage_test_when_static_front_page_is_not_set() {
		// Arrange
		update_option( 'show_on_front', 'posts' );

		// Act
		$result = $this->site_health->status_tests( array() );

		// Assert
		$this->assertSame( array(), $result, 'status_tests() should not modify $tests when there is no static front page.' );
	}

	public function test_status_tests_does_not_add_pll_homepage_test_when_page_on_front_is_empty() {
		// Arrange
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', 0 );

		// Act
		$result = $this->site_health->status_tests( array() );

		// Assert
		$this->assertSame( array(), $result, 'status_tests() should not modify $tests when no page is set as front page.' );
	}

	/**
	 * Creates a given number of posts without any language assigned.
	 *
	 * @param int    $number    Number of posts to create.
	 * @param string $post_type Post type to use. Default 'post'.
	 * @return int[] The created post IDs.
	 */
	private function create_posts_without_lang( int $number, string $post_type = 'post' ): array {
		$ids = array();

		for ( $i = 0; $i < $number; $i++ ) {
			$ids[] = self::factory()->post->create( array( 'post_type' => $post_type ) );
		}

		return $ids;
	}

	public function test_get_post_ids_without_lang_returns_posts_grouped_by_post_type() {
		// Arrange
		$post_no_lang_ids = $this->create_posts_without_lang( 2, 'post' );
		$page_no_lang_ids = $this->create_posts_without_lang( 2, 'page' );
		$post_en = self::factory()->post->create( array( 'post_type' => 'post', 'lang' => 'en' ) );

		// Act
		$result = $this->site_health->get_post_ids_without_lang();

		// Assert
		$this->assertSame( array( 'post', 'page' ), array_keys( $result ), 'Result should be grouped by post type.' );
		$this->assertSameSets(
			array_map( 'strval', $post_no_lang_ids ),
			explode( ',', $result['post'] ),
			'Result should contain posts IDs without language.'
		);
		$this->assertSameSets(
			array_map( 'strval', $page_no_lang_ids ),
			explode( ',', $result['page'] ),
			'Result should contain pages IDs without language.'
		);
		$this->assertStringNotContainsString( (string) $post_en, $result['post'], 'Result should not contain posts that already have a language.' );
	}

	/**
	 * @testWith [-1, 7]
	 *           [2, 2]
	 *           [0, 7]
	 *
	 * @param int $limit          The limit to pass to get_post_ids_without_lang().
	 * @param int $expected_count The expected number of returned post IDs.
	 */
	public function test_get_post_ids_without_lang_respects_limit( int $limit, int $expected_count ) {
		// Arrange
		$post_no_lang_ids = $this->create_posts_without_lang( 7, 'post' );

		// Act
		$result = $this->site_health->get_post_ids_without_lang( $limit );
		$result_ids = explode( ',', $result['post'] );

		// Assert
		$this->assertCount( $expected_count, $result_ids, "Result should contain $expected_count post IDs for limit $limit." );
		$this->assertEmpty(
			array_diff( $result_ids, $post_no_lang_ids ),
			'All returned IDs should be among the created posts without language.'
		);
	}

	public function test_get_post_ids_without_lang_uses_default_limit_when_no_argument_passed() {
		// Arrange
		$this->create_posts_without_lang( 7, 'post' );

		// Act
		$result = $this->site_health->get_post_ids_without_lang(); // pas d'argument

		// Assert
		$this->assertCount( 5, explode( ',', $result['post'] ), 'Should default to a limit of 5 when no argument is passed.' );
	}

	public function test_get_post_ids_without_lang_returns_empty_array_when_none_missing() {
		// Arrange
		self::factory()->post->create( array( 'lang' => 'en' ) );

		// Act
		$result = $this->site_health->get_post_ids_without_lang();

		// Assert
		$this->assertEmpty( $result, 'Result should contain an empty array when all posts have lang.' );
	}

	/**
	 * Creates a given number of terms without any language assigned.
	 *
	 * @param int    $number   Number of terms to create.
	 * @param string $taxonomy Taxonomy to use. Default 'category'.
	 * @return int[] The created term IDs.
	 */
	private function create_terms_without_lang( int $number, string $taxonomy = 'category' ): array {
		$ids = array();

		for ( $i = 0; $i < $number; $i++ ) {
			$ids[] = self::factory()->term->create( array( 'taxonomy' => $taxonomy ) );
		}

		return $ids;
	}

	public function test_get_term_ids_without_lang_returns_terms_grouped_by_taxonomy() {
		// Arrange
		$category_no_lang_ids = $this->create_terms_without_lang( 2, 'category' );
		$post_tag_no_lang_ids = $this->create_terms_without_lang( 2, 'post_tag' );
		$term_en = self::factory()->term->create( array( 'taxonomy' => 'category', 'lang' => 'en' ) );

		// Act
		$result = $this->site_health->get_term_ids_without_lang();

		// Assert :
		$this->assertSame( array( 'category', 'post_tag' ), array_keys( $result ), 'Result should be grouped by taxonomy.' );
		$this->assertSameSets(
			array_map( 'strval', $category_no_lang_ids ),
			explode( ',', $result['category'] ),
			'Result should contain category IDs without language.'
		);
		$this->assertSameSets(
			array_map( 'strval', $post_tag_no_lang_ids ),
			explode( ',', $result['post_tag'] ),
			'Result should contain post_tag IDs without language.'
		);
		$this->assertStringNotContainsString( (string) $term_en, $result['category'], 'Result should not contain terms that already have a language.' );
	}

	/**
	 * @testWith [-1, 7]
	 *           [0, 7]
	 *
	 * @param int $limit          The limit to pass to get_term_ids_without_lang().
	 * @param int $expected_count The expected number of returned term IDs.
	 */
	public function test_get_term_ids_without_lang_respects_limit( int $limit, int $expected_count ) {
		// Arrange
		$category_no_lang_ids = $this->create_terms_without_lang( 7, 'category' );

		// Act
		$result = $this->site_health->get_term_ids_without_lang( $limit );
		$result_ids = explode( ',', $result['category'] );

		// Assert
		$this->assertCount( $expected_count, $result_ids, "Result should contain $expected_count term IDs for limit $limit." );
		$this->assertEmpty(
			array_diff( $result_ids, $category_no_lang_ids ),
			'All returned IDs should be among the created terms without language.'
		);
	}

	public function test_get_term_ids_without_lang_uses_default_limit_when_no_argument_passed() {
		// Arrange
		$this->create_terms_without_lang( 7, 'category' );

		// Act
		$result = $this->site_health->get_term_ids_without_lang();

		// Assert
		$this->assertCount( 5, explode( ',', $result['category'] ), 'Should default to a limit of 5 when no argument is passed.' );
	}

	public function test_get_term_ids_without_lang_returns_empty_array_when_none_missing() {
		// Arrange
		self::factory()->term->create( array( 'taxonomy' => 'category', 'lang' => 'en' ) );

		// Act
		$result = $this->site_health->get_term_ids_without_lang();

		// Assert
		$this->assertEmpty( $result, 'Result should contain an empty array when all terms have lang.' );
	}
}
