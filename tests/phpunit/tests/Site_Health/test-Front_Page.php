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
			'label'  => 'All languages have a translated homepage',
			'status' => 'good',
			'badge'  => array(
				'label' => POLYLANG,
				'color' => 'blue',
			),
			'description' => '<p>It is mandatory to translate the static front page in all languages.</p>',
			'actions'     => '',
			'test'        => 'pll_homepage',
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
			'label'  => 'The homepage is not translated in all languages',
			'status' => 'critical',
			'badge'  => array(
				'label' => POLYLANG,
				'color' => 'blue',
			),
			'actions' => '',
			'test'    => 'pll_homepage',
		);

		$result = $this->site_health->homepage_test();

		$this->assertSameSetsWithIndex(
			$expected,
			array_diff_key( $result, array( 'description' => true ) ),
			'homepage_test() should return the expected array.'
		);
		$this->assertMatchesRegularExpression(
			'/^<p>You must translate your static front page in <a href="[^"]*post-new\.php\?post_type=page&#038;from_post=' . $home_en . '&#038;new_lang=fr&#038;_wpnonce=[^"]+">Français<\/a>\.<\/p>$/',
			$result['description'],
			'homepage_test() should return the expected description.'
		);
	}

	public function test_status_tests_does_not_add_homepage_test_when_front_page_does_not_exist() {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', 999999 ); // Intentionally big not to match an existing page.

		$result = WP_Site_Health::get_tests();

		$this->assertArrayNotHasKey(
			'pll_homepage',
			$result['direct'],
			'The homepage test should not be added when the front page does not exist.'
		);
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

		$result_with_pll = WP_Site_Health::get_tests();
		remove_filter( 'site_status_tests', array( $this->site_health, 'status_tests' ) );
		$result_without_pll = WP_Site_Health::get_tests();

		$this->assertArrayHasKey( 'pll_homepage', $result_with_pll['direct'], 'pll_homepage should still be added alongside existing tests.' );
		unset( $result_with_pll['direct']['pll_homepage'] );
		$this->assertSameSetsWithIndex( $result_without_pll['direct'], $result_with_pll['direct'], 'Existing Site Health tests should be preserved unchanged.' );
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
