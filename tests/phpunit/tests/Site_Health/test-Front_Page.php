<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

use PLL_Admin_Links;
use WP_Site_Health;

class Front_Page_Test extends TestCase {

	public function test_homepage_test_all_languages_translated() {
		$home_pages = self::factory()->post->create_translated(
			array( 'post_title' => 'home', 'post_type' => 'page', 'lang' => 'en' ),
			array( 'post_title' => 'accueil', 'post_type' => 'page', 'lang' => 'fr' )
		);
		$this->set_page_on_front( $home_pages['en'] );

		$expected = array(
			'label' => 'All languages have a translated homepage',
			'status' => 'good',
			'badge' => array(
				'label' => POLYLANG,
				'color' => 'blue',
			),
			'description' => '<p>It is mandatory to translate the static front page in all languages.</p>',
			'actions' => '',
			'test' => 'pll_homepage',
		);

		$result = $this->site_health->homepage_test();

		$this->assertSameSetsWithIndex( $expected, $result, 'homepage_test() should return the expected array.' );
	}

	public function test_homepage_test_missing_translation() {
		// Translation URLs require a logged-in user with the correct capabilities.
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$home_en = self::factory()->post->create( array( 'post_title' => 'home', 'post_type' => 'page', 'lang' => 'en' ) );
		$this->set_page_on_front( $home_en );

		/**
		 * Homepage_test() calls get_must_translate_message() which, only when a language is missing,
		 * builds a translation link via $this->links->get_new_post_translation_link().
		 * PLL_Admin::links is only set in init(), which is never called in our set_up(),
		 * so it must be initialized manually here to avoid a fatal error on this code path.
		 */
		$this->pll_admin->links = new PLL_Admin_Links( $this->pll_admin );

		$expected = array(
			'label' => 'The homepage is not translated in all languages',
			'status' => 'critical',
			'badge' => array(
				'label' => POLYLANG,
				'color' => 'blue',
			),
			'actions' => '',
			'test' => 'pll_homepage',
		);

		$result = $this->site_health->homepage_test();

		$this->assertSameSetsWithIndex(
			$expected,
			array_diff_key( $result, array( 'description' => true ) ),
			'homepage_test() should return the expected array.'
		);
		$this->assertStringContainsString(
			'You must translate your static front page in',
			$result['description'],
			'Description should mention the untranslated languages.'
		);
		$this->assertStringContainsString(
			'>Français</a>',
			$result['description'],
			'Description should contain a translation link for the missing language.'
		);
		$this->assertMatchesRegularExpression(
			'/href="[^"]*new_lang=fr[^"]*"/',
			$result['description'],
			'Description should contain a link targeting the French translation.'
		);
	}

	public function test_homepage_test_when_front_page_does_not_exist() {
		$deleted_page_id = self::factory()->post->create( array( 'post_type' => 'page', 'lang' => 'en' ) );
		wp_delete_post( $deleted_page_id, true );
		$this->set_page_on_front( $deleted_page_id );

		$test_result = $this->site_health->homepage_test();

		// TODO : Waiting answer about that
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

		$site_health = new WP_Site_Health();
		$expected = array();
		add_filter(
			'site_status_tests',
			function ( $tests ) use ( &$expected ) {
				$expected = $tests;
				return $tests;
			},
			1
		);
		$result = $site_health->get_tests();

		$this->assertSameSetsWithIndex( $expected['direct'], array_diff_key( $result['direct'], array( 'pll_homepage' => true ) ), 'Existing Site Health tests should be preserved unchanged.' );
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
