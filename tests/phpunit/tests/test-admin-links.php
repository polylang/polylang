<?php

use WP_User;
use PLL_Model;
use PLL_Admin;
use PLL_Language;
use PLL_Admin_Links;
use PLL_UnitTestCase;
use ReflectionObject;
use PLL_UnitTest_Factory;
use WP_Syntex\Polylang\Capabilities\User\NOOP;

class Test_Admin_Links extends PLL_UnitTestCase {
	/**
	 * @var PLL_Admin
	 */
	protected $pll_admin;

	/**
	 * @var PLL_Admin_Links
	 */
	protected $links;

	/**
	 * @var \WP_User
	 */
	protected static $editor;

	/**
	 * @var \WP_User
	 */
	protected static $contributor;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 3 );

		self::$editor      = self::factory()->user->create_and_get( array( 'role' => 'editor' ) );
		self::$contributor = self::factory()->user->create_and_get( array( 'role' => 'contributor' ) );
	}

	public static function wpTearDownAfterClass() {
		self::delete_user( self::$editor->ID );
		self::delete_user( self::$contributor->ID );

		parent::wpTearDownAfterClass();
	}

	public function set_up() {
		parent::set_up();

		$options = $this->create_options(
			array(
				'default_lang'  => 'en',
			)
		);
		$model           = new PLL_Model( $options );
		$links_model     = $model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );
	}

	/**
	 * Tests that a translator can only create translations in their allowed language.
	 */
	public function test_translator_cannot_translate_to_unauthorized_language() {
		$this->links = new PLL_Admin_Links( $this->pll_admin );
		$this->mock_user_for_links( array( 'can_translate' => false ) );

		$post     = self::factory()->post->create_and_get( array( 'lang' => 'fr' ) );
		$language = $this->pll_admin->model->get_language( 'en' );

		$link = $this->links->get_new_post_translation_link( $post, $language );

		$this->assertSame( '', $link, 'A French translator should not be able to create a translation in English.' );
	}

	/**
	 * Tests that a non-translator (editor) can translate to any language.
	 *
	 * @testWith ["en", "fr"]
	 *           ["fr", "en"]
	 *
	 * @param string $post_lang   Slug of the lang of the post.
	 * @param string $target_lang Slug of the lang to assign to the post.
	 * @return void
	 */
	public function test_editor_can_translate_post_to_any_language( string $post_lang, string $target_lang ) {
		wp_set_current_user( self::$editor->ID );
		$this->links = new PLL_Admin_Links( $this->pll_admin );

		$post     = self::factory()->post->create_and_get( array( 'lang' => $post_lang ) );
		$language = $this->pll_admin->model->get_language( $target_lang );

		$link = $this->links->get_new_post_translation_link( $post, $language );

		$this->assertStringMatchesFormat( "http://example.org/wp-admin/post-new.php?post_type=post&amp;from_post=%d&amp;new_lang={$target_lang}&amp;_wpnonce=%s", $link, 'An editor should be able to create a translation in any language.' );
	}

	/**
	 * Tests that a user without create_posts capability cannot create translations.
	 */
	public function test_user_without_create_posts_capability_cannot_translate() {
		wp_set_current_user( self::$contributor->ID );
		$this->links = new PLL_Admin_Links( $this->pll_admin );

		// Contributor cannot create pages.
		$page     = self::factory()->post->create_and_get(
			array(
				'post_type' => 'page',
				'lang'      => 'en',
			)
		);
		$language = $this->pll_admin->model->get_language( 'fr' );

		$link = $this->links->get_new_post_translation_link( $page, $language );

		$this->assertSame( '', $link, 'A contributor should not be able to create a page translation.' );
	}

	/**
	 * Tests that a user without manage_privacy_options cannot translate the privacy policy page.
	 */
	public function test_user_without_manage_privacy_options_cannot_translate_privacy_page() {
		wp_set_current_user( self::$editor->ID );
		$this->links = new PLL_Admin_Links( $this->pll_admin );

		// Editor does not have manage_privacy_options by default.
		$privacy_page_en = self::factory()->post->create_and_get(
			array(
				'post_type' => 'page',
				'lang'      => 'en',
			)
		);

		// Set this page as the privacy policy page.
		update_option( 'wp_page_for_privacy_policy', $privacy_page_en->ID );

		$language = $this->pll_admin->model->get_language( 'fr' );

		$link = $this->links->get_new_post_translation_link( $privacy_page_en, $language );

		$this->assertSame( '', $link, 'An editor without manage_privacy_options should not be able to create a translation of the privacy policy page.' );
	}

	/**
	 * Tests that a user without manage_privacy_options can translate a regular page.
	 */
	public function test_user_without_manage_privacy_options_can_translate_regular_page() {
		wp_set_current_user( self::$editor->ID );
		$this->links = new PLL_Admin_Links( $this->pll_admin );

		// Create a regular page (not the privacy policy).
		$regular_page = self::factory()->post->create_and_get(
			array(
				'post_type' => 'page',
				'lang'      => 'en',
			)
		);

		// Make sure this page is NOT the privacy policy.
		update_option( 'wp_page_for_privacy_policy', 0 );

		$language = $this->pll_admin->model->get_language( 'fr' );

		$link = $this->links->get_new_post_translation_link( $regular_page, $language );

		$this->assertStringMatchesFormat( 'http://example.org/wp-admin/post-new.php?post_type=page&amp;from_post=%d&amp;new_lang=fr&amp;_wpnonce=%s', $link, 'An editor without manage_privacy_options should be able to create a translation of a regular page.' );
	}

	/**
	 * Tests that a user without manage_privacy_options cannot translate a translation of the privacy policy page.
	 */
	public function test_user_without_manage_privacy_options_cannot_translate_privacy_page_translation() {
		wp_set_current_user( self::$editor->ID );

		// Create the privacy policy page in EN.
		$privacy_page_en = self::factory()->post->create_and_get(
			array(
				'post_type' => 'page',
				'lang'      => 'en',
			)
		);
		update_option( 'wp_page_for_privacy_policy', $privacy_page_en->ID );

		// Create a French translation of the privacy policy page.
		$privacy_page_fr = self::factory()->post->create_and_get(
			array(
				'post_type' => 'page',
				'lang'      => 'fr',
			)
		);
		$this->pll_admin->model->post->save_translations( $privacy_page_en->ID, array( 'en' => $privacy_page_en->ID, 'fr' => $privacy_page_fr->ID ) );

		$language = $this->pll_admin->model->get_language( 'de' );

		$this->links = new PLL_Admin_Links( $this->pll_admin );

		// Try to create a DE translation from the FR translation of privacy page.
		$link = $this->links->get_new_post_translation_link( $privacy_page_fr, $language );

		$this->assertSame( '', $link, 'An editor without manage_privacy_options should not be able to create a translation from a privacy page translation.' );
	}

	/**
	 * Tests that an admin with manage_privacy_options can translate the privacy policy page.
	 */
	public function test_admin_with_manage_privacy_options_can_translate_privacy_page() {
		wp_set_current_user( 1 ); // Administrator.
		$this->links = new PLL_Admin_Links( $this->pll_admin );

		$privacy_page_en = self::factory()->post->create_and_get(
			array(
				'post_type' => 'page',
				'lang'      => 'en',
			)
		);
		update_option( 'wp_page_for_privacy_policy', $privacy_page_en->ID );

		$language = $this->pll_admin->model->get_language( 'fr' );

		$link = $this->links->get_new_post_translation_link( $privacy_page_en, $language );

		$this->assertStringMatchesFormat( 'http://example.org/wp-admin/post-new.php?post_type=page&amp;from_post=%d&amp;new_lang=fr&amp;_wpnonce=%s', $link, 'An admin with manage_privacy_options should be able to create a translation of the privacy policy page.' );
	}

	/**
	 * Tests the link for attachment post type with display context.
	 */
	public function test_attachment_link_with_display_context() {
		wp_set_current_user( self::$editor->ID );
		$this->links = new PLL_Admin_Links( $this->pll_admin );

		$attachment = self::factory()->attachment->create_object( 'image.jpg' );
		$this->pll_admin->model->post->set_language( $attachment, 'en' );
		$attachment = get_post( $attachment );
		$language   = $this->pll_admin->model->get_language( 'fr' );

		$link = $this->links->get_new_post_translation_link( $attachment, $language, 'display' );

		$this->assertStringMatchesFormat( 'http://example.org/wp-admin/admin.php?action=translate_media&amp;from_media=%d&amp;new_lang=fr&amp;_wpnonce=%s', $link, 'The link should be correct.' );
	}

	/**
	 * Tests the link for attachment post type with non-display context.
	 */
	public function test_attachment_link_with_non_display_context() {
		wp_set_current_user( self::$editor->ID );
		$this->links = new PLL_Admin_Links( $this->pll_admin );

		$attachment = self::factory()->attachment->create_object( 'image.jpg' );
		$this->pll_admin->model->post->set_language( $attachment, 'en' );
		$attachment = get_post( $attachment );
		$language   = $this->pll_admin->model->get_language( 'fr' );

		$link = $this->links->get_new_post_translation_link( $attachment, $language, 'raw' );

		$this->assertStringMatchesFormat( 'http://example.org/wp-admin/admin.php?action=translate_media&from_media=%d&new_lang=fr&_wpnonce=%s', $link, 'The link should be correct.' );
	}

	/**
	 * Tests the link for non-attachment post type with display context.
	 */
	public function test_post_link_with_display_context() {
		wp_set_current_user( self::$editor->ID );
		$this->links = new PLL_Admin_Links( $this->pll_admin );

		$post     = self::factory()->post->create_and_get( array( 'lang' => 'en' ) );
		$language = $this->pll_admin->model->get_language( 'fr' );

		$link = $this->links->get_new_post_translation_link( $post, $language, 'display' );

		$this->assertStringMatchesFormat( 'http://example.org/wp-admin/post-new.php?post_type=post&amp;from_post=%d&amp;new_lang=fr&amp;_wpnonce=%s', $link, 'The link should be correct.' );
	}

	/**
	 * Tests the link for non-attachment post type with non-display context.
	 */
	public function test_post_link_with_non_display_context() {
		wp_set_current_user( self::$editor->ID );
		$this->links = new PLL_Admin_Links( $this->pll_admin );

		$post     = self::factory()->post->create_and_get( array( 'lang' => 'en' ) );
		$language = $this->pll_admin->model->get_language( 'fr' );

		$link = $this->links->get_new_post_translation_link( $post, $language, 'raw' );

		$this->assertStringMatchesFormat( 'http://example.org/wp-admin/post-new.php?post_type=post&from_post=%d&new_lang=fr&_wpnonce=%s', $link, 'The link should be correct.' );
	}

	/**
	 * Tests that the pll_get_new_post_translation_link filter is applied.
	 */
	public function test_filter_is_applied() {
		wp_set_current_user( self::$editor->ID );
		$this->links = new PLL_Admin_Links( $this->pll_admin );

		$post     = self::factory()->post->create_and_get( array( 'lang' => 'en' ) );
		$language = $this->pll_admin->model->get_language( 'fr' );

		add_filter(
			'pll_get_new_post_translation_link',
			function ( $link, $lang, $post_id ) use ( $post ) {
				$this->assertIsString( $link, 'The link should be a string.' );
				$this->assertInstanceOf( PLL_Language::class, $lang, 'The language should be a PLL_Language object.' );
				$this->assertSame( $post->ID, $post_id, 'The post ID should be the same.' );

				return $link . '&custom_param=1';
			},
			10,
			3
		);

		$link = $this->links->get_new_post_translation_link( $post, $language );

		$this->assertStringMatchesFormat( 'http://example.org/wp-admin/post-new.php?post_type=post&amp;from_post=%d&amp;new_lang=fr&amp;_wpnonce=%s&custom_param=1', $link, 'The link should be correct and the filter should be applied.' );
	}

	/**
	 * Tests that a translator cannot create a term translation in an unauthorized language.
	 */
	public function test_translator_cannot_translate_term_to_unauthorized_language() {
		$this->links = new PLL_Admin_Links( $this->pll_admin );

		$term = self::factory()->term->create_and_get( array( 'taxonomy' => 'category' ) );
		$this->pll_admin->model->term->set_language( $term->term_id, 'fr' );
		$language = $this->pll_admin->model->get_language( 'en' );

		$link = $this->links->get_new_term_translation_link( $term, 'post', $language );

		$this->assertSame( '', $link, 'A French translator should not be able to create a term translation in English.' );
	}

	/**
	 * Tests that a translator can create a term translation in their allowed language.
	 */
	public function test_translator_can_translate_term_to_authorized_language() {
		$this->links = new PLL_Admin_Links( $this->pll_admin );
		$this->mock_user_for_links(
			array(
				'can_translate' => true,
				'has_cap'       => true,
			)
		);

		$term = self::factory()->term->create_and_get( array( 'taxonomy' => 'category' ) );
		$this->pll_admin->model->term->set_language( $term->term_id, 'en' );
		$language = $this->pll_admin->model->get_language( 'fr' );

		$link = $this->links->get_new_term_translation_link( $term, 'post', $language );

		$this->assertStringMatchesFormat( 'http://example.org/wp-admin/edit-tags.php?taxonomy=category&post_type=post&from_tag=%d&new_lang=fr', $link, 'A French translator should be able to create a term translation in French.' );
	}

	/**
	 * Tests that an editor can translate a term to any language.
	 *
	 * @testWith ["en", "fr"]
	 *           ["fr", "en"]
	 *
	 * @param string $post_lang   Slug of the lang of the post.
	 * @param string $target_lang Slug of the lang to assign to the post.
	 * @return void
	 */
	public function test_editor_can_translate_term_to_any_language( string $post_lang, string $target_lang ) {
		wp_set_current_user( self::$editor->ID );
		$this->links = new PLL_Admin_Links( $this->pll_admin );

		$term = self::factory()->term->create_and_get( array( 'taxonomy' => 'category' ) );
		$this->pll_admin->model->term->set_language( $term->term_id, $post_lang );
		$language = $this->pll_admin->model->get_language( $target_lang );

		$link = $this->links->get_new_term_translation_link( $term, 'post', $language );

		$this->assertStringMatchesFormat( "http://example.org/wp-admin/edit-tags.php?taxonomy=category&post_type=post&from_tag=%d&new_lang={$target_lang}", $link, 'An editor should be able to create a term translation in any language.' );
	}

	/**
	 * Tests that a user without edit_terms capability cannot create a term translation.
	 */
	public function test_user_without_edit_terms_capability_cannot_translate_term() {
		wp_set_current_user( self::$contributor->ID );
		$this->links = new PLL_Admin_Links( $this->pll_admin );

		$term = self::factory()->term->create_and_get( array( 'taxonomy' => 'category' ) );
		$this->pll_admin->model->term->set_language( $term->term_id, 'en' );
		$language = $this->pll_admin->model->get_language( 'fr' );

		$link = $this->links->get_new_term_translation_link( $term, 'post', $language );

		$this->assertSame( '', $link, 'A contributor without edit_terms capability should not be able to create a term translation.' );
	}

	/**
	 * Tests that the pll_get_new_term_translation_link filter is applied.
	 */
	public function test_term_translation_filter_is_applied() {
		wp_set_current_user( self::$editor->ID );
		$this->links = new PLL_Admin_Links( $this->pll_admin );

		$term = self::factory()->term->create_and_get( array( 'taxonomy' => 'category' ) );
		$this->pll_admin->model->term->set_language( $term->term_id, 'en' );
		$language = $this->pll_admin->model->get_language( 'fr' );

		add_filter(
			'pll_get_new_term_translation_link',
			function ( $link, $lang, $term_id, $taxonomy, $post_type ) use ( $term ) {
				$this->assertIsString( $link, 'The link should be a string.' );
				$this->assertInstanceOf( PLL_Language::class, $lang, 'The language should be a PLL_Language object.' );
				$this->assertSame( $term->term_id, $term_id, 'The term ID should be the same.' );
				$this->assertSame( 'category', $taxonomy, 'The taxonomy should be the same.' );
				$this->assertSame( 'post', $post_type, 'The post type should be the same.' );

				return $link . '&custom_term_param=1';
			},
			10,
			5
		);

		$link = $this->links->get_new_term_translation_link( $term, 'post', $language );

		$this->assertStringMatchesFormat( 'http://example.org/wp-admin/edit-tags.php?taxonomy=category&post_type=post&from_tag=%d&new_lang=fr&custom_term_param=1', $link, 'The link should be correct and the filter should be applied.' );
	}

	/**
	 * Replace `PLL_Admin_Links::$user` with a user mock that can translate.
	 *
	 * @param array $methods An array of methods to mock and their return values.
	 * @return void
	 */
	protected function mock_user_for_links( array $methods = array() ) {
		$translator_mock = $this->getMockBuilder( NOOP::class )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $methods as $method => $return_value ) {
			$translator_mock->method( $method )
				->willReturn( $return_value );
		}

		$this->links   = new PLL_Admin_Links( $this->pll_admin );
		$links         = new ReflectionObject( $this->links );
		$user_property = $links->getProperty( 'user' );
		version_compare( PHP_VERSION, '8.1', '<' ) && $user_property->setAccessible( true );
		$user_property->setValue( $this->links, $translator_mock );
	}
}
