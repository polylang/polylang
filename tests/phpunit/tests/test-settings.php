<?php

class Settings_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	function tearDown() {
		parent::tearDown();

		$_REQUEST = $_GET = $_POST = array();
		unset( $GLOBALS['hook_suffix'], $GLOBALS['current_screen'] );
	}

	// allows to convert some html entities to xml entities to avoid breaking simplexml_load_string
	function convert_html_to_xml( $str ) {
		$chars = array(
			'&laquo;'  => '&#171;',
			'&raquo;'  => '&#187;',
			'&lsaquo;' => '&#8249;',
			'&rsaquo;' => '&#8250;',
		);

		return str_replace( array_keys( $chars ), $chars, $str );
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
		$out = $this->convert_html_to_xml( $out );
		$xml = simplexml_load_string( "<root>$out</root>" ); // add a root xml tag to get a valid xml doc

		$this->assertEquals( $lang->term_id, (int) $xml->xpath( '//input[@name="lang_id"]' )[0]->attributes()['value'] ); // hidden field
		$this->assertEquals( 'Français', $xml->xpath( '//input[@name="name"]' )[0]->attributes()['value'] );
		$this->assertEquals( 'fr_FR', $xml->xpath( '//input[@name="locale"]' )[0]->attributes()['value'] );
		$this->assertEquals( 'fr', $xml->xpath( '//input[@name="slug"]' )[0]->attributes()['value'] );
		$this->assertEquals( 'selected', $xml->xpath( '//select[@name="flag"]/option[.="France"]' )[0]->attributes()['selected'] );
	}

	function test_notice_for_objects_with_no_lang() {
		// FIXME this test works in standalone but not in the serie
		// Admin_Filters_Post_Test and Admin_Filters_Term_Test are braking it
		$this->markTestSkipped();

		$_GET['page'] = 'mlang';
		$GLOBALS['hook_suffix'] = 'settings_page_mlang';
		set_current_screen();

		self::$polylang = new PLL_Settings( self::$polylang->links_model );
		do_action( 'load-settings_page_mlang' );

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
