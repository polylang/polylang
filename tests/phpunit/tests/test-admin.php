<?php

class Admin_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	function test_admin_bar_menu() {
		global $wp_admin_bar;
		add_filter( 'show_admin_bar', '__return_true' ); // Make sure to show admin bar

		$this->go_to( home_url( '/wp-admin/edit.php' ) );
		self::$polylang = new PLL_Admin( self::$polylang->links_model );
		self::$polylang->init();

		_wp_admin_bar_init();
		do_action_ref_array( 'admin_bar_menu', array( &$wp_admin_bar ) );

		$languages = $wp_admin_bar->get_node( 'languages' );
		$this->assertEmpty( $languages->parent );
		$this->assertEquals( '/wp-admin/edit.php?lang=all', $languages->href );

		$en = $wp_admin_bar->get_node( 'en' );
		$this->assertEquals( 'languages', $en->parent );
		$this->assertEquals( '/wp-admin/edit.php?lang=en', $en->href );

		$fr = $wp_admin_bar->get_node( 'fr' );
		$this->assertEquals( 'languages', $fr->parent );
		$this->assertEquals( '/wp-admin/edit.php?lang=fr', $fr->href );
	}
}
