<?php

class WPML_Test extends PLL_UnitTestCase {
	public $structure = '/%postname%/';

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE_formal' );

		require_once POLYLANG_DIR . '/include/api.php';
	}

	public function set_up() {
		parent::set_up();

		$this->links_model = self::$model->get_links_model();

		PLL_WPML_Compat::instance()->api = new PLL_WPML_API(); // Loads the WPML API
	}

	public function tear_down() {
		parent::tear_down();

		// Cleaning the previous registered strings translations that were added
		foreach ( PLL_WPML_Compat::instance()->get_strings( array() ) as $string ) {
			PLL_WPML_Compat::instance()->unregister_string( $string['context'], $string['name'] );
		}

		unset( $GLOBALS['polylang'] );
	}

	/**
	 * Notice sent when ACF calls icl_object_id  with a non translated post type
	 *
	 * @see https://wordpress.org/support/topic/after-update-apeared-warnings
	 */
	public function test_acf() {
		$frontend = new PLL_Frontend( $this->links_model );
		$GLOBALS['polylang'] = $frontend;

		register_post_type( 'acf' );

		$id = self::factory()->post->create( array( 'post_type' => 'acf' ) );
		$this->assertEquals( icl_object_id( $id, 'acf', true, 'en' ), $id );

		_unregister_post_type( 'acf' );
	}

	public function test_wpml_active_languages() {
		self::$model->post->register_taxonomy(); // Needed otherwise posts are not counted

		$en = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->clean_languages_cache(); // For some reason (global state?) we need to reset the posts count

		$frontend = new PLL_Frontend( $this->links_model );
		$GLOBALS['polylang'] = $frontend;
		$frontend->init();

		$frontend->curlang = self::$model->get_language( 'fr' );
		$this->go_to( home_url( '?page_id=' . $fr ) );

		$languages = apply_filters( 'wpml_active_languages', null );

		$expected = array(
			'id'               => self::$model->get_language( 'fr' )->term_id,
			'active'           => 1,
			'native_name'      => 'Français',
			'missing'          => 0,
			'translated_name'  => '',
			'language_code'    => 'fr',
			'country_flag_url' => plugins_url( '/flags/fr.png', POLYLANG_FILE ),
			'url'              => home_url( "?page_id={$fr}&lang=fr" ),
		);

		$this->assertCount( 3, $languages ); // All languages are returned, even German which has no content
		$this->assertEqualSets( $expected, $languages['fr'] );
		$this->assertEquals( 1, $languages['en']['missing'] );

		$languages = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 1 ) );

		$this->assertCount( 1, $languages );
		$this->assertNotEmpty( $languages['fr'] );

		$languages = apply_filters( 'wpml_active_languages', null, 'orderby=code&order=asc' );
		$this->assertEqualSetsWithIndex( array( 'de', 'en', 'fr' ), array_keys( $languages ) );

		$languages = apply_filters( 'wpml_active_languages', null, 'orderby=code&order=desc' );
		$this->assertEqualSetsWithIndex( array( 'fr', 'en', 'de' ), array_keys( $languages ) );
	}

	public function test_wpml_current_language() {
		$frontend = new PLL_Frontend( $this->links_model );
		$GLOBALS['polylang'] = $frontend;

		$frontend->curlang = self::$model->get_language( 'en' );
		$this->assertEquals( 'en', apply_filters( 'wpml_current_language', null ) );

		$frontend->curlang = self::$model->get_language( 'fr' );
		$this->assertEquals( 'fr', apply_filters( 'wpml_current_language', null ) );
	}

	public function test_wpml_default_language() {
		$frontend = new PLL_Frontend( $this->links_model );
		$GLOBALS['polylang'] = $frontend;

		self::$model->options['default_lang'] = 'fr';
		$this->assertEquals( 'fr', apply_filters( 'wpml_default_language', null ) );
		$this->assertEquals( 'fr', icl_get_default_language() ); // Legacy
		$this->assertEquals( 'fr', wpml_get_default_language() ); // Legacy
	}

	public function test_wpml_add_language_form_field() {
		$frontend = new PLL_Frontend( $this->links_model );
		$GLOBALS['polylang'] = $frontend;

		$frontend->curlang = self::$model->get_language( 'fr' );
		ob_start();
		do_action( 'wpml_add_language_form_field' );

		$this->assertEquals( '<input type="hidden" name="lang" value="fr" />', ob_get_clean() );
	}

	public function test_wpml_post_language_details() {
		$frontend = new PLL_Frontend( $this->links_model );
		$GLOBALS['polylang'] = $frontend;

		$id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $id, 'en' );

		$frontend->curlang = self::$model->get_language( 'fr' );
		$lang = apply_filters( 'wpml_post_language_details', null, $id );

		$this->assertEquals( 'en', $lang['language_code'] );
		$this->assertEquals( 'en_US', $lang['locale'] );
		$this->assertFalse( $lang['text_direction'] );
		$this->assertEquals( 'English', $lang['native_name'] );
		$this->assertTrue( $lang['different_language'] );

		$GLOBALS['post'] = get_post( $id );
		$frontend->curlang = self::$model->get_language( 'en' );
		$lang = apply_filters( 'wpml_post_language_details', null );

		$this->assertEquals( 'en', $lang['language_code'] );
		$this->assertFalse( $lang['different_language'] );
	}

	public function test_wpml_language_code() {
		$frontend = new PLL_Frontend( $this->links_model );
		$GLOBALS['polylang'] = $frontend;

		$id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $id, 'en' );

		$this->assertEquals( 'en', apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $id, 'element_type' => 'page' ) ) );

		$id = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $id, 'en' );

		$this->assertEquals( 'en', apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $id, 'element_type' => 'category' ) ) );
	}

	public function test_wpml_home_url() {
		$frontend = new PLL_Frontend( $this->links_model );
		$GLOBALS['polylang'] = $frontend;
		$frontend->links = new PLL_Frontend_Links( $frontend );
		$frontend->curlang = self::$model->get_language( 'fr' );

		$this->assertEquals( 'http://example.org/?lang=fr', apply_filters( 'wpml_home_url', null ) );
		$this->assertEquals( 'http://example.org/?lang=fr', icl_get_home_url() ); // Legacy
	}

	public function test_wpml_element_link() {
		global $wp_rewrite;

		// Switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // Brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );
		create_initial_taxonomies(); // Needed for catery links

		self::$model->options['force_lang'] = 1;
		$links_model = self::$model->get_links_model();
		$links_model->init();

		$frontend = new PLL_Frontend( $links_model );
		$GLOBALS['polylang'] = $frontend;
		$frontend->init();

		// De-activate cache for links
		$frontend->links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		$frontend->links->cache->method( 'get' )->willReturn( false );

		$frontend->filters_links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		$frontend->filters_links->cache->method( 'get' )->willReturn( false );

		$en = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'test' ) );
		self::$model->post->set_language( $en, 'en' );

		$tag = self::factory()->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'test' ) );
		self::$model->term->set_language( $tag, 'en' );

		ob_start();
		$link = apply_filters( 'wpml_element_link', $en );
		$this->assertEquals( '<a href="http://example.org/en/test/">test</a>', ob_get_clean() ); // echo parameter defaults to true
		$this->assertEquals( '<a href="http://example.org/en/test/">test</a>', $link );

		ob_start();
		apply_filters( 'wpml_element_link', $en, 'page', 'Custom link' );
		$this->assertEquals( '<a href="http://example.org/en/test/">Custom link</a>', ob_get_clean() );

		ob_start();
		apply_filters( 'wpml_element_link', $en, 'page', '', array( 'category' => 'foo', 'bar' => 'baz' ) );
		$this->assertEquals( '<a href="http://example.org/en/test/?category=foo&#038;bar=baz">test</a>', ob_get_clean() );

		ob_start();
		apply_filters( 'wpml_element_link', $en, 'page', '', '', 'contact' );
		$this->assertEquals( '<a href="http://example.org/en/test/#contact">test</a>', ob_get_clean() );

		ob_start();
		$link = apply_filters( 'wpml_element_link', $en, '', '', '', false );
		$this->assertEquals( '', ob_get_clean() ); // echo parameter is false

		ob_start();
		apply_filters( 'wpml_element_link', $tag, 'tag' );
		$this->assertEquals( '<a href="http://example.org/en/tag/test/">test</a>', ob_get_clean() );

		$frontend->curlang = self::$model->get_language( 'fr' );

		ob_start();
		$link = apply_filters( 'wpml_element_link', $en );
		$this->assertEquals( '<a href="http://example.org/en/test/">test</a>', ob_get_clean() ); // return_original_if_missing true

		$link = apply_filters( 'wpml_element_link', $en, 'page', '', '', '', false, false );
		$this->assertEquals( '', $link ); // return_original_if_missing false

		$fr = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'test fr' ) );
		self::$model->post->set_language( $fr, 'fr' );
		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		ob_start();
		apply_filters( 'wpml_element_link', $en, 'page', '', '', '' ); // Translation
		$this->assertEquals( '<a href="http://example.org/fr/test-fr/">test fr</a>', ob_get_clean() );
	}

	public function test_wpml_object_id() {
		$frontend = new PLL_Frontend( $this->links_model );
		$GLOBALS['polylang'] = $frontend;

		$frontend->curlang = self::$model->get_language( 'fr' );

		$en = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'fr' ) );

		$this->assertEquals( $fr, apply_filters( 'wpml_object_id', $fr, 'page' ) );
		$this->assertEquals( $fr, apply_filters( 'wpml_object_id', $en, 'page' ) );
		$this->assertEquals( $en, apply_filters( 'wpml_object_id', $fr, 'page', false, 'en' ) );

		$cat = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $cat, 'en' );

		$this->assertNull( apply_filters( 'wpml_object_id', $cat, 'category' ) );
		$this->assertEquals( $cat, apply_filters( 'wpml_object_id', $cat, 'category', true ) );
	}

	public function test_wpml_object_id_nav_menu() {
		$pll_admin = new PLL_Admin( $this->links_model );
		$GLOBALS['polylang'] = $pll_admin;

		self::$model->options['default_lang'] = 'en';

		$registered_nav_menus = get_registered_nav_menus();

		if ( empty( $registered_nav_menus ) ) {
			register_nav_menu( 'primary', 'Primary menu' );
		}

		// Create 2 menus
		$menu_en = wp_create_nav_menu( 'menu_en' );
		$menu_fr = wp_create_nav_menu( 'menu_fr' );

		$this->assertEmpty( apply_filters( 'wpml_object_id', $menu_en, 'nav_menu' ) ); // Just to test the PHP notice fixed in 2.2.7

		// Assign our menus to locations
		$nav_menu = new PLL_Admin_Nav_Menu( $pll_admin );
		$nav_menu->create_nav_menu_locations();

		$locations = array_keys( get_registered_nav_menus() );
		$nav_menu_locations = array(
			$locations[0] => $menu_en,
			$locations[1] => $menu_fr,
		);

		set_theme_mod( 'nav_menu_locations', $nav_menu_locations );
		$nav_menu->update_nav_menu_locations( $nav_menu_locations ); // Our filter update_nav_menu_locations does not run due to security checks

		$pll_admin->curlang = self::$model->get_language( 'en' );
		$this->assertEquals( $menu_en, apply_filters( 'wpml_object_id', $menu_en, 'nav_menu' ) );
		$this->assertEquals( $menu_en, apply_filters( 'wpml_object_id', $menu_fr, 'nav_menu' ) );

		$pll_admin->curlang = self::$model->get_language( 'fr' );
		$this->assertEquals( $menu_fr, apply_filters( 'wpml_object_id', $menu_en, 'nav_menu' ) );
		$this->assertEquals( $menu_fr, apply_filters( 'wpml_object_id', $menu_fr, 'nav_menu' ) );
	}

	public function test_wpml_element_has_translations() {
		$frontend = new PLL_Frontend( $this->links_model );
		$GLOBALS['polylang'] = $frontend;

		$en = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'fr' ) );

		$id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $id, 'en' );

		$this->assertTrue( apply_filters( 'wpml_element_has_translations', null, $en, 'page' ) );
		$this->assertTrue( apply_filters( 'wpml_element_has_translations', null, $fr, 'page' ) );
		$this->assertFalse( apply_filters( 'wpml_element_has_translations', null, $id, 'page' ) );

		$en = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'fr' ) );

		$id = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $id, 'en' );

		$this->assertTrue( apply_filters( 'wpml_element_has_translations', null, $en, 'category' ) );
		$this->assertTrue( apply_filters( 'wpml_element_has_translations', null, $fr, 'category' ) );
		$this->assertFalse( apply_filters( 'wpml_element_has_translations', null, $id, 'category' ) );
	}

	public function test_strings_translations() {
		add_action( 'pll_get_strings', array( PLL_WPML_Compat::instance(), 'get_strings' ) ); // Add filter as it is removed at the end of fisrt test (singleton!)

		// Register
		do_action( 'wpml_register_single_string', 'wpml_string_context', 'wpml_string_name', 'wpml_string_test' );

		$str = wp_list_filter( PLL_Admin_Strings::get_strings(), array( 'icl' => 1 ) );
		$str = reset( $str );

		$this->assertEquals( 'wpml_string_context', $str['context'] );
		$this->assertEquals( 'wpml_string_name', $str['name'] );
		$this->assertEquals( 'wpml_string_test', $str['string'] );

		// Translate
		foreach ( array( 'en', 'fr' ) as $lang ) {
			$language = self::$model->get_language( $lang );
			$mo = new PLL_MO();
			$mo->import_from_db( $language );
			$mo->add_entry( $mo->make_entry( 'wpml_string_test', "wpml_string_test_$lang" ) );
			$mo->export_to_db( $language );
		}

		$frontend = new PLL_Frontend( $this->links_model );
		$GLOBALS['polylang'] = $frontend;
		$frontend->curlang = self::$model->get_language( 'fr' );
		do_action( 'pll_language_defined' );

		$this->assertEquals( 'wpml_string_test_en', apply_filters( 'wpml_translate_single_string', 'wpml_string_test', 'wpml_string_context', 'wpml_string_name', 'en' ) );
		$this->assertEquals( 'wpml_string_test_fr', apply_filters( 'wpml_translate_single_string', 'wpml_string_test', 'wpml_string_context', 'wpml_string_name' ) );

		// Legacy
		$this->assertEquals( 'wpml_string_test_fr', icl_t( 'wpml_string_context', 'wpml_string_name' ) );
	}

	/**
	 * Bug fixed in version 2.2
	 */
	public function test_wpml_permalink() {
		global $wp_rewrite;

		// Switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // Brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );

		self::$model->options['force_lang'] = 1;
		$links_model = self::$model->get_links_model();
		$links_model->init();

		$frontend = new PLL_Frontend( $links_model );
		$GLOBALS['polylang'] = $frontend; // The WPML API uses PLL().

		$this->assertEquals( home_url( '/fr/test/' ), apply_filters( 'wpml_permalink', home_url( '/test/' ), 'fr' ) );

		$frontend->curlang = self::$model->get_language( 'fr' );
		$this->assertEquals( home_url( '/fr/test/' ), apply_filters( 'wpml_permalink', home_url( '/test/' ) ) );
	}

	/**
	 * Test behavior of wpml_register_single_string when duplicate name and context
	 */
	public function test_duplicate_string_translation() {
		$pll_admin = new PLL_Admin( $this->links_model );
		$GLOBALS['polylang'] = $pll_admin;

		// Register single string.
		do_action( 'wpml_register_single_string', 'Context', 'Name', 'My text to translate' );

		// Translate the first single string.
		foreach ( array( 'en', 'fr' ) as $lang ) {
			$language = self::$model->get_language( $lang );
			$mo = new PLL_MO();
			$mo->import_from_db( $language );
			$mo->add_entry( $mo->make_entry( 'My text to translate', "My text to translate_$lang" ) );
			$mo->export_to_db( $language );
		}

		// Add duplicate string.
		do_action( 'wpml_register_single_string', 'Context', 'Name', 'My text to translate 2' );

		// Get translations of the registered string to test it later.
		$string_translation = array();
		foreach ( array( 'en', 'fr' ) as $lang ) {
			$language = self::$model->get_language( $lang );
			$mo = new PLL_MO();
			$mo->import_from_db( $language );
			$string_translation[ $lang ] = $mo->translate( 'My text to translate 2' );
		}

		$str = wp_list_filter( PLL_WPML_Compat::instance()->get_strings( array() ), array() );
		$str = reset( $str );

		$this->assertEquals( 'Context', $str['context'] );
		$this->assertEquals( 'Name', $str['name'] );
		$this->assertEquals( 'My text to translate 2', $str['string'] );
		$this->assertEquals( 'My text to translate 2', $string_translation['en'] ); // The updated source string should be used for the default language.
		$this->assertEquals( 'My text to translate_fr', $string_translation['fr'] );
	}

	public function test_wpml_switch_language() {
		$frontend = new PLL_Frontend( $this->links_model );
		$GLOBALS['polylang'] = $frontend;
		PLL()->curlang = self::$model->get_language( 'en' );

		do_action( 'wpml_switch_language', 'all' );
		$this->assertNull( PLL()->curlang );

		do_action( 'wpml_switch_language', 'fr' );
		$this->assertEquals( 'fr', PLL()->curlang->slug );

		// Restore to the original language.
		do_action( 'wpml_switch_language' );
		$this->assertEquals( 'en', PLL()->curlang->slug );
	}
}
