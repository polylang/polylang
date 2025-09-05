<?php

use Brain\Monkey;
use WP_Syntex\Polylang\Options\Options;

class Install_Test extends PLL_UnitTestCase {
	use PLL_Mocks_Trait;

	private $orig_wp_version;

	public function set_up() {
		parent::set_up();
		Monkey\setUp();
	}

	public function tear_down() {
		global $wp_version;

		if ( ! empty( $this->orig_wp_version ) ) {
			$wp_version = $this->orig_wp_version;
			$this->orig_wp_version = null;
		}

		Monkey\tearDown();
		parent::tear_down();
	}

	/**
	 * @testWith ["100.6.3", true]
	 *           ["5.6.0", false]
	 *
	 * @param string $cur_php_version Current php version.
	 * @param bool   $pass            Tells if the check is expected to pass.
	 */
	public function test_php_version_check( string $cur_php_version, bool $pass ): void {
		$this->mock_constants(
			array(
				'PHP_VERSION' => $cur_php_version,
			)
		);

		// Test if pass.
		if ( $pass ) {
			$this->assertTrue( PLL_Usable::can_activate() );
		} else {
			$this->assertFalse( PLL_Usable::can_activate() );
		}

		// Test the admin notice.
		ob_start();
		do_action( 'admin_notices' );
		$buffer = ob_get_clean();

		if ( $pass ) {
			$this->assertStringNotContainsString( PLL_MIN_PHP_VERSION, $buffer );
			$this->assertStringNotContainsString( $cur_php_version, $buffer );
		} else {
			$this->assertStringContainsString( PLL_MIN_PHP_VERSION, $buffer );
			$this->assertStringContainsString( $cur_php_version, $buffer );
		}
	}

	/**
	 * @testWith ["100.6.3", true]
	 *           ["4.2.0", false]
	 *
	 * @param string $cur_wp_version Current WP version.
	 * @param bool   $pass           Tells if the check is expected to pass.
	 */
	public function test_wp_version_check( string $cur_wp_version, bool $pass ): void {
		global $wp_version;
		$this->orig_wp_version = $wp_version;
		$wp_version            = $cur_wp_version;

		// Test if pass.
		if ( $pass ) {
			$this->assertTrue( PLL_Usable::can_activate() );
		} else {
			$this->assertFalse( PLL_Usable::can_activate() );
		}

		ob_start();
		do_action( 'admin_notices' );
		$buffer = ob_get_clean();

		// Test the admin notice.
		if ( $pass ) {
			$this->assertStringNotContainsString( PLL_MIN_WP_VERSION, $buffer );
			$this->assertStringNotContainsString( $cur_wp_version, $buffer );
		} else {
			$this->assertStringContainsString( PLL_MIN_WP_VERSION, $buffer );
			$this->assertStringContainsString( $cur_wp_version, $buffer );
		}
	}

	public function test_is_deactivation(): void {
		$_GET['action'] = 'deactivate';
		$_GET['plugin'] = 'mew/mew.php';

		$this->assertFalse( PLL_Deactivate::is_deactivation() );

		$_GET['plugin'] = POLYLANG_BASENAME;

		$this->assertTrue( PLL_Deactivate::is_deactivation() );
	}

	public function test_activate() {
		delete_option( 'polylang' );
		do_action( 'activate_' . POLYLANG_BASENAME );

		// Check a few options
		$options = get_option( 'polylang' );
		$this->assertIsArray( $options );
		$this->assertSame( true, $options['hide_default'] );
		$this->assertSame( 1, $options['force_lang'] );
		$this->assertEmpty( $options['sync'] );
		$this->assertSame( POLYLANG_VERSION, $options['version'] );
	}

	public function test_should_hide_set_language_from_content_on_activation() {
		delete_option( 'polylang' );
		do_action( 'activate_' . POLYLANG_BASENAME );

		$options = new Options();

		$this->assertSame( 'no', get_option( 'pll_language_from_content_available' ) );
		$this->assertInstanceOf( WP_Error::class, $options->set( 'force_lang', 0 ) );
	}

	/**
	 * This test requires the definition of the constants WP_UNINSTALL_PLUGIN
	 * The constant PLL_REMOVE_ALL_DATA must not be defined.
	 * This test must be executed before all uninstall tests.
	 */
	public function test_uninstall_without_removing_data() {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}

		do_action( 'activate_' . POLYLANG_BASENAME );

		include_once dirname( __DIR__, 3 ) . '/uninstall.php';
		new PLL_Uninstall();

		// Constant PLL_REMOVE_ALL_DATA undefined => nothing deleted
		$options = get_option( 'polylang' );
		$this->assertNotEmpty( $options );
	}

	/**
	 * This test requires the definition of the constants WP_UNINSTALL_PLUGIN and PLL_REMOVE_ALL_DATA
	 */
	public function test_uninstall_removing_data() {
		global $wpdb;

		do_action( 'activate_' . POLYLANG_BASENAME );

		self::create_language( 'en_US' );
		$english = self::$model->get_language( 'en' );

		self::create_language( 'fr_FR' );
		$french = self::$model->get_language( 'fr' );

		// Posts and terms
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );

		$post_translations_groups = get_terms( array( 'taxonomy' => 'post_translations' ) );
		$post_group = reset( $post_translations_groups );

		$term_translations_groups = get_terms( array( 'taxonomy' => 'term_translations' ) );
		$term_group = reset( $term_translations_groups );

		// User metas
		update_user_meta( 1, 'pll_filter_content', 'en' );
		update_user_meta( 1, 'description_fr', 'Biographie' );

		// A menu with a language switcher
		$menu_en = wp_create_nav_menu( 'menu_en' );
		$item_id = wp_update_nav_menu_item(
			$menu_en,
			0,
			array(
				'menu-item-type'   => 'custom',
				'menu-item-title'  => 'Language switcher',
				'menu-item-url'    => '#pll_switcher',
				'menu-item-status' => 'publish',
			)
		);

		update_post_meta( $item_id, '_pll_menu_item', array() );

		// Strings translations
		$_mo = new PLL_MO();
		$_mo->add_entry( $_mo->make_entry( 'test', 'test fr' ) );
		$_mo->export_to_db( self::$model->get_language( 'fr' ) );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}

		// Constant PLL_REMOVE_ALL_DATA = true
		if ( ! defined( 'PLL_REMOVE_ALL_DATA' ) ) {
			define( 'PLL_REMOVE_ALL_DATA', true );
		}

		new PLL_Uninstall(); // Fire uninstall process again

		// No options
		$this->assertEmpty( get_option( 'polylang' ) );

		// No languages
		$this->assertEmpty( get_terms( array( 'taxonomy' => 'language', 'hide_empty' => false ) ) );
		$this->assertEmpty( get_terms( array( 'taxonomy' => 'term_language' ) ) );

		// No languages for posts and terms
		$this->assertEmpty( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d", $english->get_tax_prop( 'language', 'term_taxonomy_id' ) ) ) );
		$this->assertEmpty( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d", $english->get_tax_prop( 'term_language', 'term_taxonomy_id' ) ) ) );

		// No translations for posts and terms
		$this->assertEmpty( get_terms( array( 'taxonomy' => 'post_translations' ) ) );
		$this->assertEmpty( get_terms( array( 'taxonomy' => 'term_translations' ) ) );

		$this->assertEmpty( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d", $post_group->term_taxonomy_id ) ) );
		$this->assertEmpty( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d", $term_group->term_taxonomy_id ) ) );

		// Users metas
		$this->assertEmpty( $wpdb->get_results( "SELECT * FROM {$wpdb->usermeta} WHERE meta_key='pll_filter_content'" ) );
		$this->assertEmpty( $wpdb->get_results( "SELECT * FROM {$wpdb->usermeta} WHERE meta_key='description_fr'" ) );

		// Language switcher menu items
		$this->assertEmpty( get_post( $item_id ) );

		// Strings translations
		$this->assertEmpty( get_term_meta( $french->term_id ) );
	}
}
