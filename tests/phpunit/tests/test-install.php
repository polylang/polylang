<?php

class Install_Test extends PLL_UnitTestCase {

	function test_activate() {

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

	function test_uninstall() {
		global $wpdb;

		do_action( 'activate_' . POLYLANG_BASENAME );

		self::create_language( 'en_US' );
		$en = self::$polylang->model->get_language( 'en' );

		self::create_language( 'fr_FR' );

		// Posts and terms
		$post_id = $this->factory->post->create();
		self::$polylang->model->post->set_language( $post_id, 'en' );

		$term_id = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $term_id, 'en' );

		// User metas
		update_user_meta( 1, 'pll_filter_content', 'en' );
		update_user_meta( 1, 'pll_duplicate_content', array( 'post' => true ) );
		update_user_meta( 1, 'description_fr', 'Biographie' );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}

		include( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/uninstall.php' );

		// Option Uninstall = false => nothing deleted
		$options = get_option( 'polylang' );
		$this->assertNotEmpty( $options );

		// Option Uninstall = true
		$options['uninstall'] = 1;
		update_option( 'polylang', $options );
		new PLL_Uninstall(); // Fire uninstall process again

		// No options
		$this->assertEmpty( get_option( 'polylang' ) );

		// No languages
		$this->assertEmpty( $wpdb->get_results( "SELECT * FROM {$wpdb->term_taxonomy} WHERE taxonomy='language'" ) );
		$this->assertEmpty( $wpdb->get_results( "SELECT * FROM {$wpdb->term_taxonomy} WHERE taxonomy='term_language'" ) );

		// No languages for posts and terms
		$this->assertEmpty( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d", $en->term_taxonomy_id ) ) );
		$this->assertEmpty( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d", $en->tl_term_taxonomy_id ) ) );

		// No strings translations, bug fixed in 2.2.1
		$this->assertEmpty( get_post( $en->mo_id ) );

		// Users metas
		$this->assertEmpty( $wpdb->get_results( "SELECT * FROM {$wpdb->usermeta} WHERE meta_key='pll_filter_content'" ) );
		$this->assertEmpty( $wpdb->get_results( "SELECT * FROM {$wpdb->usermeta} WHERE meta_key='pll_duplicate_content'" ) );
		$this->assertEmpty( $wpdb->get_results( "SELECT * FROM {$wpdb->usermeta} WHERE meta_key='description_fr'" ) );
	}
}
