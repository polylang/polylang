<?php

class Settings_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	function setUp() {
		parent::setUp();

		// Avoid http call
		add_filter( 'pre_transient_available_translations', '__return_empty_array' );
	}

	function tearDown() {
		parent::tearDown();

		$_REQUEST = $_GET = $_POST = array();
		unset( $GLOBALS['hook_suffix'], $GLOBALS['current_screen'] );
	}

	// bug introduced and fixed in 1.9alpha
	function test_edit_language() {
		$lang = self::$polylang->model->get_language( 'fr' );

		// setup globals
		$_GET['page'] = 'mlang';
		$_GET['pll_action'] = $_REQUEST['pll_action'] = 'edit'; // languages_page() tests $_REQUEST
		$_GET['lang'] = $lang->term_id;
		$GLOBALS['hook_suffix'] = 'settings_page_mlang';
		get_admin_page_title();
		set_current_screen();

		ob_start();
		self::$polylang = new PLL_Settings( self::$polylang->links_model );
		self::$polylang->languages_page();
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

	function test_notice_for_objects_with_no_lang() {
		if ( version_compare( $GLOBALS['wp_version'], '4.7', '<' ) ) {
			$this->markTestSkipped(); // For some reason, the test dos not work in previous versions
		}

		$_GET['page'] = 'mlang';
		$GLOBALS['hook_suffix'] = 'settings_page_mlang';
		set_current_screen();

		self::$polylang = new PLL_Settings( self::$polylang->links_model );
		do_action( 'load-toplevel_page_mlang' );

		ob_start();
		$id = $this->factory->post->create();
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		$this->assertNotEmpty( $out );

		ob_start();
		self::$polylang->model->post->set_language( $id, 'en' );
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		$this->assertEmpty( $out );

		ob_start();
		$id = $this->factory->term->create();
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		$this->assertNotEmpty( $out );

		ob_start();
		self::$polylang->model->term->set_language( $id, 'en' );
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		$this->assertEmpty( $out );
	}

	// Bug introduced in 2.1-dev
	function test_display_settings_errors() {
		add_settings_error( 'test', 'test', 'ERROR' );
		self::$polylang = new PLL_Settings( self::$polylang->links_model );

		ob_start();
		self::$polylang->languages_page();
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'ERROR' ) );
	}
}
