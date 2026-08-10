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

		// Assert :
		$this->assertSame( array( 'post', 'page' ), array_keys( $result ), 'Result should be grouped by post type.' );
		$this->assertEqualsCanonicalizing(
			$post_no_lang_ids,
			explode( ',', $result['post'] ),
			'Result should contain posts IDs without language.'
		);
		$this->assertEqualsCanonicalizing(
			$page_no_lang_ids,
			explode( ',', $result['page'] ),
			'Result should contain pages IDs without language.'
		);
		$this->assertStringNotContainsString( (string) $post_en, $result['post'], 'Result should not contain posts that already have a language.' );
	}

	public function test_get_post_ids_without_lang_respects_default_limit() {
		// Arrange
		$post_no_lang_ids = $this->create_posts_without_lang( 7, 'post' );

		// Act
		$result = $this->site_health->get_post_ids_without_lang();
		$result_ids = explode( ',', $result['post'] );

		// Assert
		$this->assertCount( 5, $result_ids, 'Result should be limited to 5 post IDs by default.' );
		$this->assertEmpty(
			array_diff( $result_ids, $post_no_lang_ids ),
			'All returned IDs should be among the created posts without language.'
		);
	}

	public function test_get_post_ids_without_lang_returns_all_with_limit_minus_one() {
		// Arrange
		$post_no_lang_ids = $this->create_posts_without_lang( 7, 'post' );

		// Act
		$result = $this->site_health->get_post_ids_without_lang( -1 );
		$result_ids = explode( ',', $result['post'] );

		// Assert
		$this->assertCount( 7, $result_ids, 'Result should contain all 7 post IDs when limit is -1.' );
		$this->assertEmpty(
			array_diff( $result_ids, $post_no_lang_ids ),
			'Result should contain exactly the created post IDs when limit is -1.'
		);
	}

	public function test_get_post_ids_without_lang_respects_custom_limit() {
		// Arrange
		$post_no_lang_ids = $this->create_posts_without_lang( 7, 'post' );

		// Act
		$result = $this->site_health->get_post_ids_without_lang( 2 );
		$result_ids = explode( ',', $result['post'] );

		// Assert
		$this->assertCount( 2, $result_ids, 'Result should be limited to 2 post IDs when a custom limit is passed.' );
		$this->assertEmpty(
			array_diff( $result_ids, $post_no_lang_ids ),
			'2 returned IDs should be among the created posts without language.'
		);
	}

	public function test_get_post_ids_without_lang_treats_limit_below_one_as_unlimited() {
		// Arrange
		$this->create_posts_without_lang( 7, 'post' );

		// Act
		$result = $this->site_health->get_post_ids_without_lang( 0 );
		$result_ids = explode( ',', $result['post'] );

		// Assert
		$this->assertCount( 7, $result_ids, 'A limit below 1 (other than the documented -1) should be treated as unlimited.' );
	}

	public function test_get_post_ids_without_lang_returns_empty_array_when_none_missing() {
		// Arrange
		self::factory()->post->create( array( 'post_type' => 'post', 'lang' => 'en' ) );

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
		$this->assertEqualsCanonicalizing(
			$category_no_lang_ids,
			explode( ',', $result['category'] ),
			'Result should contain category IDs without language.'
		);
		$this->assertEqualsCanonicalizing(
			$post_tag_no_lang_ids,
			explode( ',', $result['post_tag'] ),
			'Result should contain post_tag IDs without language.'
		);
		$this->assertStringNotContainsString( (string) $term_en, $result['category'], 'Result should not contain terms that already have a language.' );
	}

	public function test_get_term_ids_without_lang_respects_default_limit() {
		// Arrange
		$category_no_lang_ids = $this->create_terms_without_lang( 7, 'category' );

		// Act
		$result = $this->site_health->get_term_ids_without_lang();
		$result_ids = explode( ',', $result['category'] );

		// Assert
		$this->assertCount( 5, $result_ids, 'Result should be limited to 5 term IDs by default.' );
		$this->assertEmpty(
			array_diff( $result_ids, $category_no_lang_ids ),
			'All returned IDs should be among the created terms without language.'
		);
	}

	public function test_get_term_ids_without_lang_returns_all_with_limit_minus_one() {
		// Arrange
		$category_no_lang_ids = $this->create_terms_without_lang( 7, 'category' );

		// Act
		$result = $this->site_health->get_term_ids_without_lang( -1 );
		$result_ids = explode( ',', $result['category'] );

		// Assert
		$this->assertCount( 7, $result_ids, 'Result should contain all 7 term IDs when limit is -1.' );
		$this->assertEmpty(
			array_diff( $result_ids, $category_no_lang_ids ),
			'Result should contain exactly the created term IDs when limit is -1.'
		);
	}

	public function test_get_term_ids_without_lang_treats_limit_below_one_as_unlimited() {
		// Arrange
		$this->create_terms_without_lang( 7, 'category' );

		// Act
		$result = $this->site_health->get_term_ids_without_lang( 0 );
		$result_ids = explode( ',', $result['category'] );

		// Assert
		$this->assertCount( 7, $result_ids, 'A limit below 1 (other than the documented -1) should be treated as unlimited.' );
	}

	public function test_get_term_ids_without_lang_returns_empty_array_when_none_missing() {
		// Arrange
		self::factory()->term->create( array( 'taxonomy' => 'category', 'lang' => 'en' ) );

		// Act
		$result = $this->site_health->get_term_ids_without_lang();

		// Assert
		$this->assertEmpty( $result, 'Result should contain an empty array when all terms have lang.' );
	}

	public function test_info_languages_contains_expected_fields() {
		// Arrange
		$en = $this->pll_admin->model->get_language( 'en' );

		// Act
		$debug_info = $this->site_health->info_languages( array() );
		$fields = $debug_info['pll_language_en']['fields'];

		// Assert
		$this->assertSame( 'name', $fields['name']['label'], 'Label should equal the key name.' );
		$this->assertSame( $en->name, $fields['name']['value'], 'Name value should match the language object.' );
		$this->assertSame( (string) $en->term_id, (string) $fields['term_id']['value'], 'Term_id value should match the language object.' );
		$this->assertSame( $en->slug, $fields['slug']['value'], 'Slug value should match the language object.' );
		$this->assertSame( 'order', $fields['term_group']['label'], 'Label should equal the key name.' );
		$this->assertSame( (string) $en->term_group, (string) $fields['term_group']['value'], 'Term_group value should match the language object.' );
		foreach ( array( 'flag', 'host', 'taxonomy', 'description', 'parent', 'filter', 'custom_flag' ) as $excluded_key ) {
			$this->assertArrayNotHasKey( $excluded_key, $fields, "Excluded key \"$excluded_key\" should not be present." );
		}
	}

	public function test_info_languages_returns_one_entry_per_language() {
		// Act
		$debug_info = $this->site_health->info_languages( array() );

		// Assert
		$this->assertCount( 2, $debug_info, 'Result should contain one entry per configured language.' );
		$this->assertArrayHasKey( 'pll_language_en', $debug_info, 'Result should contain an entry for English.' );
		$this->assertArrayHasKey( 'pll_language_fr', $debug_info, 'Result should contain an entry for French.' );
	}

	public function test_info_languages_returns_empty_array_when_no_language_is_set() {
		// Arrange
		self::delete_all_languages();

		try {
			// Act
			$debug_info = $this->site_health->info_languages( array() );

			// Assert
			$this->assertEmpty( $debug_info, 'Result should be empty when no language is set.' );
		} finally {
			// Cleanup: always restore languages, even if the assertion above fails, so subsequent tests in the class aren't affected.
			self::create_language( 'en_US' );
			self::create_language( 'fr_FR' );
		}
	}

	public function test_info_languages_preserves_existing_debug_info() {
		// Arrange
		$debug_info = array(
			'pre_existing_data' => array(
				'label'       => 'Title of this data',
				'description' => 'Description',
				'fields'      => array(
					'name' => array(
						'label' => 'Name',
						'value' => 'Field name',
					),
				),
			),
		);

		// Act
		$result = $this->site_health->info_languages( $debug_info );

		// Assert
		$this->assertCount( 3, $result, 'Result should contain one entry per configured language, plus the pre-existing data.' );
		$this->assertSame(
			$debug_info['pre_existing_data'],
			$result['pre_existing_data'],
			'Pre-existing data should be preserved unchanged.'
		);
		$this->assertSame( 'Language: English - en', $result['pll_language_en']['label'], 'New language entry should be added correctly.' );
	}

	public function test_info_adds_post_no_lang_field_when_posts_are_missing_lang() {
		// Arrange
		$this->create_posts_without_lang( 3, 'post' );

		// Act
		$debug_info = $this->site_health->info( array() );
		$fields = $debug_info['pll_warnings']['fields'];

		// Assert
		$this->assertArrayHasKey( 'post-no-lang', $fields, 'Should contain a "post-no-lang" key.' );
		$this->assertSame( 'Posts without language', $fields['post-no-lang']['label'], 'Should have the expected label.' );
		$this->assertSame(
			$this->site_health->get_post_ids_without_lang(),
			$fields['post-no-lang']['value'],
			'Value should list the posts without a language.'
		);
	}

	public function test_info_does_not_add_post_no_lang_field_when_all_posts_have_lang() {
		// Arrange
		self::factory()->post->create( array( 'post_type' => 'post', 'lang' => 'en' ) );

		// Act
		$debug_info = $this->site_health->info( array() );

		// Assert
		$this->assertArrayNotHasKey( 'pll_warnings', $debug_info, 'Should not contain the "pll_warnings" section when all posts have a language.' );
	}

	public function test_info_does_not_add_post_no_lang_field_when_only_terms_are_missing_lang() {
		// Arrange
		self::factory()->post->create( array( 'post_type' => 'post', 'lang' => 'en' ) );
		$this->create_terms_without_lang( 1, 'category' );

		// Act
		$debug_info = $this->site_health->info( array() );

		// Assert
		$this->assertArrayHasKey( 'pll_warnings', $debug_info, 'Should contain the "pll_warnings" section because terms are missing a language.' );
		$this->assertArrayNotHasKey(
			'post-no-lang',
			$debug_info['pll_warnings']['fields'],
			'Should not contain "post-no-lang" when only terms are missing a language.'
		);
		$this->assertArrayHasKey(
			'term-no-lang',
			$debug_info['pll_warnings']['fields'],
			'Should contain "term-no-lang" since a term is missing a language.'
		);
	}

	public function test_info_adds_term_no_lang_field_when_terms_are_missing_lang() {
		// Arrange
		$this->create_terms_without_lang( 3, 'category' );

		// Act
		$debug_info = $this->site_health->info( array() );
		$fields = $debug_info['pll_warnings']['fields'];

		// Assert
		$this->assertArrayHasKey( 'term-no-lang', $fields, 'Should contain a "term-no-lang" key.' );
		$this->assertSame( 'Terms without language', $fields['term-no-lang']['label'], 'Should have the expected label.' );
		$this->assertSame(
			$this->site_health->get_term_ids_without_lang(),
			$fields['term-no-lang']['value'],
			'Value should list the terms without a language.'
		);
	}

	public function test_info_does_not_add_term_no_lang_field_when_all_terms_have_lang() {
		// Arrange
		self::factory()->term->create( array( 'taxonomy' => 'category', 'lang' => 'en' ) );

		// Act
		$debug_info = $this->site_health->info( array() );

		// Assert
		$this->assertArrayNotHasKey( 'pll_warnings', $debug_info, 'Should not contain the "pll_warnings" section when all terms have a language.' );
	}

	public function test_info_does_not_add_term_no_lang_field_when_only_posts_are_missing_lang() {
		// Arrange
		self::factory()->term->create( array( 'taxonomy' => 'category', 'lang' => 'en' ) );
		$this->create_posts_without_lang( 1, 'post' );

		// Act
		$debug_info = $this->site_health->info( array() );

		// Assert
		$this->assertArrayHasKey( 'pll_warnings', $debug_info, 'Should contain the "pll_warnings" section because posts are missing a language.' );
		$this->assertArrayNotHasKey(
			'term-no-lang',
			$debug_info['pll_warnings']['fields'],
			'Should not contain "term-no-lang" when only posts are missing a language.'
		);
		$this->assertArrayHasKey(
			'post-no-lang',
			$debug_info['pll_warnings']['fields'],
			'Should contain "post-no-lang" since a post is missing a language.'
		);
	}
}
