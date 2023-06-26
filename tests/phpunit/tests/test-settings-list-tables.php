<?php

class Settings_List_Tables_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	public function test_display_languages_table() {
		wp_set_current_user( 1 );

		$GLOBALS['hook_suffix'] = 'settings_page_mlang';
		$_GET['page'] = 'mlang';

		$links_model = self::$model->get_links_model();
		$pll_env = new PLL_Settings( $links_model );

		ob_start();
		do_action( 'admin_menu' );
		set_current_screen();
		do_action( 'load-toplevel_page_mlang' );
		get_admin_page_title();
		get_current_screen()->render_screen_meta();
		do_action( 'toplevel_page_mlang' );
		$out = ob_get_clean();

		$doc = new DomDocument();
		$doc->loadHTML( $out );
		$xpath = new DOMXpath( $doc );

		// All column headers.
		$this->assertSame( 7, $xpath->query( '//thead/tr/th' )->length );

		$th = $xpath->query( '//th[@id="name"]/a/span' );
		$this->assertSame( 'Full name', $th->item( 0 )->nodeValue );

		$th = $xpath->query( '//th[@id="locale"]/a/span' );
		$this->assertSame( 'Locale', $th->item( 0 )->nodeValue );

		$th = $xpath->query( '//th[@id="slug"]/a/span' );
		$this->assertSame( 'Code', $th->item( 0 )->nodeValue );

		$th = $xpath->query( '//th[@id="default_lang"]/span/span' );
		$this->assertSame( 'Default language', $th->item( 0 )->nodeValue );

		$th = $xpath->query( '//th[@id="term_group"]/a/span' );
		$this->assertSame( 'Order', $th->item( 0 )->nodeValue );

		$th = $xpath->query( '//th[@id="flag"]' );
		$this->assertSame( 'Flag', $th->item( 0 )->nodeValue );

		$th = $xpath->query( '//th[@id="count"]/a/span' );
		$this->assertSame( 'Posts', $th->item( 0 )->nodeValue );

		// The rows ( 1 per language ).
		$this->assertSame( 2, $xpath->query( '//tbody/tr' )->length );

		// Just check 1 language name.
		$td = $xpath->query( '//tbody/tr/td/a' );
		$this->assertSame( 'English', $td->item( 0 )->nodeValue );
	}

	public function test_display_strings_table() {
		wp_set_current_user( 1 );

		$GLOBALS['hook_suffix'] = 'settings_page_mlang';
		$_GET['page'] = 'mlang_strings';

		$links_model = self::$model->get_links_model();
		$pll_env = new PLL_Settings( $links_model );

		PLL_Admin_Strings::register_string( 'Test', 'Some string' );

		ob_start();
		do_action( 'admin_menu' );
		set_current_screen();
		do_action( 'load-languages_page_mlang_strings' );
		get_admin_page_title();
		get_current_screen()->render_screen_meta();
		do_action( 'languages_page_mlang_strings' );
		$out = ob_get_clean();

		$doc = new DomDocument();
		$doc->loadHTML( $out );
		$xpath = new DOMXpath( $doc );

		// All column headers.
		$this->assertSame( 4, $xpath->query( '//thead/tr/th' )->length ); // Doesn't count the checkbox.

		$th = $xpath->query( '//thead/tr/td[@id="cb"]/input' ); // Curiously, this a <td>.
		$this->assertSame( 'checkbox', $th->item( 0 )->getAttribute( 'type' ) );

		$th = $xpath->query( '//th[@id="string"]/a/span' );
		$this->assertSame( 'String', $th->item( 0 )->nodeValue );

		$th = $xpath->query( '//th[@id="name"]/a/span' );
		$this->assertSame( 'Name', $th->item( 0 )->nodeValue );

		$th = $xpath->query( '//th[@id="context"]/a/span' );
		$this->assertSame( 'Group', $th->item( 0 )->nodeValue );

		$th = $xpath->query( '//th[@id="translations"]' );
		$this->assertSame( 'Translations', $th->item( 0 )->nodeValue );

		// The rows.
		$this->assertSame( 1, $xpath->query( '//tbody/tr' )->length ); // 1 per string.

		$td = $xpath->query( '//tbody/tr/td' );
		// $this->assertSame( 'Some string', $td->item( 0 )->nodeValue ); // This cell displays a parasitic child button.
		$this->assertSame( 'Test', $td->item( 1 )->nodeValue );
		$this->assertSame( 'Polylang', $td->item( 2 )->nodeValue );

		$this->assertSame( 2, $xpath->query( '//tbody/tr/td/div/input' )->length ); // 1 per language.
	}

	public function test_display_settings_table() {
		wp_set_current_user( 1 );

		$GLOBALS['hook_suffix'] = 'settings_page_mlang';
		$_GET['page'] = 'mlang_settings';

		$links_model = self::$model->get_links_model();
		$pll_env = new PLL_Settings( $links_model );
		$pll_env->register_settings_modules(); // Manually register modules to avoid firing the 'admin_init' action.

		ob_start();
		do_action( 'admin_menu' );
		set_current_screen();
		do_action( 'load-languages_page_mlang_settings' );
		get_admin_page_title();
		get_current_screen()->render_screen_meta();
		do_action( 'languages_page_mlang_settings' );
		$out = ob_get_clean();

		$doc = new DomDocument();
		$doc->loadHTML( $out );
		$xpath = new DOMXpath( $doc );

		// All column headers.
		$this->assertSame( 2, $xpath->query( '//thead/tr/th' )->length ); // Doesn't count the empty cb.

		$th = $xpath->query( '//thead/tr/td[@id="cb"]' ); // Curiously, this a <td>.
		$this->assertEmpty( $th->item( 0 )->nodeValue );

		$th = $xpath->query( '//th[@id="plugin-title"]' );
		$this->assertSame( 'Module', $th->item( 0 )->nodeValue );

		$th = $xpath->query( '//th[@id="description"]' );
		$this->assertSame( 'Description', $th->item( 0 )->nodeValue );

		// The rows.
		$trs = $xpath->query( '//tbody/tr' );
		$this->assertSame( 6, $trs->length ); // Only core modules (5 + Hidden URL modifications form).

		$this->assertSame( 'pll-module-url', $trs->item( 0 )->getAttribute( 'id' ) );
		$this->assertSame( 'pll-configure-url', $trs->item( 1 )->getAttribute( 'id' ) );
		$this->assertSame( 'display: none;', $trs->item( 1 )->getAttribute( 'style' ) );
		$this->assertSame( 'pll-module-browser', $trs->item( 2 )->getAttribute( 'id' ) );
		$this->assertSame( 'pll-module-media', $trs->item( 3 )->getAttribute( 'id' ) );
		$this->assertSame( 'pll-module-cpt', $trs->item( 4 )->getAttribute( 'id' ) );
		$this->assertSame( 'pll-module-licenses', $trs->item( 5 )->getAttribute( 'id' ) );
	}
}
