<?php

namespace WP_Syntex\Polylang\Tests\SiteHealth;

use PLL_Admin_Links;

class Front_Page_Test extends TestCase {

	public function test_homepage_test_all_languages_translated() {
		$home_pages = self::factory()->post->create_translated(
			array( 'post_title' => 'home', 'post_type' => 'page', 'lang' => 'en' ),
			array( 'post_title' => 'accueil', 'post_type' => 'page', 'lang' => 'fr' )
		);
		$this->set_page_on_front( $home_pages['en'] );

		$test_result = $this->site_health->homepage_test();

		$this->assertSame( array( 'label', 'status', 'badge', 'description', 'actions', 'test' ), array_keys( $test_result ), 'homepage_test() should return the expected array structure.' );
		$this->assertSame( 'All languages have a translated homepage', $test_result['label'], 'homepage_test() should have the expected label when all languages are translated.' );
		$this->assertSame( 'good', $test_result['status'], 'homepage_test() should have a "status" key set to "good".' );
		$this->assertSame( array( 'label' => POLYLANG, 'color' => 'blue' ), $test_result['badge'], 'homepage_test() should have the expected badge.' );
		$this->assertSame( '<p>It is mandatory to translate the static front page in all languages.</p>', $test_result['description'], 'homepage_test() should have the expected description when all languages are translated.' );
		$this->assertSame( '', $test_result['actions'], 'homepage_test() should have empty actions when all languages are translated.' );
		$this->assertSame( 'pll_homepage', $test_result['test'], 'homepage_test() should have the expected test identifier.' );
	}

	public function test_homepage_test_missing_translation() {
		// Translation URLs require a logged-in user with the correct capabilities.
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$home_en = self::factory()->post->create( array( 'post_title' => 'home', 'post_type' => 'page', 'lang' => 'en' ) );
		$this->set_page_on_front( $home_en );

		$this->pll_admin->links = new PLL_Admin_Links( $this->pll_admin );

		$test_result = $this->site_health->homepage_test();

		$this->assertSame( array( 'label', 'status', 'badge', 'description', 'actions', 'test' ), array_keys( $test_result ), 'homepage_test() should return the expected array structure.' );
		$this->assertSame( 'The homepage is not translated in all languages', $test_result['label'], 'homepage_test() should have the expected alert label when not all languages are translated.' );
		$this->assertSame( 'critical', $test_result['status'], 'homepage_test() should have a "status" key set to "critical".' );
		$this->assertSame( array( 'label' => POLYLANG, 'color' => 'blue' ), $test_result['badge'], 'homepage_test() should have the expected badge.' );
		$this->assertStringContainsString( 'You must translate your static front page in', $test_result['description'], 'Description should mention the untranslated languages.' );
		$this->assertStringContainsString( '>Français</a>', $test_result['description'], 'Description should contain a translation link for the missing language.' );
		$this->assertMatchesRegularExpression( '/href="[^"]*new_lang=fr[^"]*"/', $test_result['description'], 'Description should contain a link targeting the French translation.' );
		$this->assertSame( '', $test_result['actions'], 'homepage_test() should have empty actions when a language is not translated.' );
		$this->assertSame( 'pll_homepage', $test_result['test'], 'homepage_test() should have the expected test identifier.' );
	}

	public function test_homepage_test_when_front_page_does_not_exist() {
		$deleted_page_id = self::factory()->post->create( array( 'post_type' => 'page', 'lang' => 'en' ) );
		wp_delete_post( $deleted_page_id, true );
		$this->set_page_on_front( $deleted_page_id );

		$test_result = $this->site_health->homepage_test();

		$this->assertSame( 'good', $test_result['status'], 'homepage_test() should return "good" when the front page no longer exists.' );
	}

	public function test_status_tests_adds_pll_homepage_test_when_static_front_page_is_set() {
		$home_en = self::factory()->post->create( array( 'post_title' => 'home', 'post_type' => 'page', 'lang' => 'en' ) );
		$this->set_page_on_front( $home_en );

		$result = $this->site_health->status_tests( array() );

		$this->assertArrayHasKey( 'direct', $result, 'status_tests() should add a "direct" key.' );
		$this->assertArrayHasKey( 'pll_homepage', $result['direct'], 'status_tests() should add a "pll_homepage" entry when a static front page is set.' );
		$this->assertSame( 'Homepage translated', $result['direct']['pll_homepage']['label'], 'The "pll_homepage" test should have the expected label.' );
		$this->assertSame( array( $this->site_health, 'homepage_test' ), $result['direct']['pll_homepage']['test'], 'The "pll_homepage" test should reference the homepage_test() callback.' );
	}

	public function test_status_tests_preserves_existing_tests_when_static_front_page_is_set() {
		$home_en = self::factory()->post->create( array( 'post_title' => 'home', 'post_type' => 'page', 'lang' => 'en' ) );
		$this->set_page_on_front( $home_en );

		$existing_tests = array(
			'direct' => array(
				'other_test' => array(
					'label' => 'Some other test',
					'test'  => '__return_true',
				),
			),
		);

		$result = $this->site_health->status_tests( $existing_tests );

		$this->assertArrayHasKey( 'other_test', $result['direct'], 'Existing tests should not be overwritten.' );
		$this->assertSame( $existing_tests['direct']['other_test'], $result['direct']['other_test'], 'Existing test data should remain unchanged.' );
		$this->assertArrayHasKey( 'pll_homepage', $result['direct'], 'pll_homepage should still be added alongside existing tests.' );
	}

	public function test_status_tests_does_not_add_pll_homepage_test_when_static_front_page_is_not_set() {
		update_option( 'show_on_front', 'posts' );

		$result = $this->site_health->status_tests( array() );

		$this->assertSame( array(), $result, 'status_tests() should not modify $tests when there is no static front page.' );
	}

	public function test_status_tests_does_not_add_pll_homepage_test_when_page_on_front_is_empty() {
		$this->set_page_on_front( 0 );

		$result = $this->site_health->status_tests( array() );

		$this->assertSame( array(), $result, 'status_tests() should not modify $tests when no page is set as front page.' );
	}
}
