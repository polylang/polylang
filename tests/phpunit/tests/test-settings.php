<?php

class Settings_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	function setUp() {
		parent::setUp();

		// Avoid http call
		add_filter( 'pre_transient_available_translations', '__return_empty_array' );
	}

	// bug introduced and fixed in 1.9alpha
	function test_edit_language() {
		$lang = self::$model->get_language( 'fr' );

		// setup globals
		$_GET['page'] = 'mlang';
		$_GET['pll_action'] = $_REQUEST['pll_action'] = 'edit'; // languages_page() tests $_REQUEST
		$_GET['lang'] = $lang->term_id;
		$GLOBALS['hook_suffix'] = 'settings_page_mlang';
		get_admin_page_title();
		set_current_screen();

		// languages_pages() calls wp_get_available_translations() which triggers a wp.org api request
		// if the transient 'available_translations' is empty, so let's fill it with a dummy value.
		set_site_transient( 'available_translations', array( 'fr_FR' => '' ) );

		ob_start();
		$links_model = self::$model->get_links_model();
		$pll_env = new PLL_Settings( $links_model );
		$pll_env->languages_page();
		$out = ob_get_clean();
		$out = mb_convert_encoding( $out, 'HTML-ENTITIES', 'UTF-8' ); // Due to "Français"
		$doc = new DomDocument();
		$doc->loadHTML( $out );
		$xpath = new DOMXpath( $doc );

		$input = $xpath->query( '//input[@name="lang_id"]' );
		$this->assertEquals( $lang->term_id, $input->item( 0 )->getAttribute( 'value' ) ); // hidden field

		$input = $xpath->query( '//input[@name="name"]' );
		$this->assertEquals( 'Français', $input->item( 0 )->getAttribute( 'value' ) );

		$input = $xpath->query( '//input[@name="locale"]' );
		$this->assertEquals( 'fr_FR', $input->item( 0 )->getAttribute( 'value' ) );

		$input = $xpath->query( '//input[@name="slug"]' );
		$this->assertEquals( 'fr', $input->item( 0 )->getAttribute( 'value' ) );

		$option = $xpath->query( '//select[@name="flag"]/option[.="France"]' );
		$this->assertEquals( 'selected', $option->item( 0 )->getAttribute( 'selected' ) );
	}

	// Bug introduced in 2.1-dev
	function test_display_settings_errors() {
		add_settings_error( 'test', 'test', 'ERROR' );
		$links_model = self::$model->get_links_model();
		$pll_env = new PLL_Settings( $links_model );

		ob_start();
		$pll_env->languages_page();
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'ERROR' ) );
	}
}
