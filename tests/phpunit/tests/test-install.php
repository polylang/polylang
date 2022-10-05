<?php

class Install_Test extends PLL_UnitTestCase {

	public function test_activate() {
		delete_option( 'polylang' );
		do_action( 'activate_' . POLYLANG_BASENAME );

		// Check a few options
		$options = get_option( 'polylang' );
		$this->assertEquals( 1, $options['hide_default'] );
		$this->assertEquals( 1, $options['force_lang'] );
		$this->assertEquals( 0, $options['uninstall'] );
		$this->assertEmpty( $options['sync'] );
		$this->assertEquals( POLYLANG_VERSION, $options['version'] );
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

		include_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/uninstall.php';
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
		$this->assertEmpty( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d", $english->term_taxonomy_id ) ) );
		$this->assertEmpty( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d", $english->tl_term_taxonomy_id ) ) );

		// No translations for posts and terms
		$this->assertEmpty( get_terms( array( 'taxonomy' => 'post_translations' ) ) );
		$this->assertEmpty( get_terms( array( 'taxonomy' => 'term_translations' ) ) );

		$this->assertEmpty( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d", $post_group->term_taxonomy_id ) ) );
		$this->assertEmpty( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d", $term_group->term_taxonomy_id ) ) );

		// No strings translations, bug fixed in 2.2.1
		$this->assertEmpty( get_post( $english->mo_id ) );

		// Users metas
		$this->assertEmpty( $wpdb->get_results( "SELECT * FROM {$wpdb->usermeta} WHERE meta_key='pll_filter_content'" ) );
		$this->assertEmpty( $wpdb->get_results( "SELECT * FROM {$wpdb->usermeta} WHERE meta_key='description_fr'" ) );

		// Language switcher menu items
		$this->assertEmpty( get_post( $item_id ) );
	}
}
