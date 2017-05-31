<?php

class WPML_Test extends PLL_UnitTestCase {
	public $structure = '/%postname%/';

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once PLL_INC . '/api.php';
		$GLOBALS['polylang'] = &self::$polylang; // The WPML API uses the global $polylang
	}

	static function wpTearDownAfterClass() {
		parent::wpTearDownAfterClass();

		unset( $GLOBALS['polylang'] );
	}

	function setUp() {
		parent::setUp();

		PLL_WPML_Compat::instance()->api = new PLL_WPML_API(); // Loads the WPML API
	}

	// Notice sent when ACF calls icl_object_id  with a non translated post type
	// @see https://wordpress.org/support/topic/after-update-apeared-warnings
	function test_acf() {
		register_post_type( 'acf' );

		$id = $this->factory->post->create( array( 'post_type' => 'acf' ) );
		$this->assertEquals( icl_object_id( $id, 'acf', true, 'en' ), $id );

		_unregister_post_type( 'acf' );
	}

	function test_wpml_active_languages() {
		self::$polylang->model->post->register_taxonomy(); // Needed otherwise posts are not counted

		$en = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->clean_languages_cache(); // For some reason (global state?) we need to reset the posts count

		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		self::$polylang->init();

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		$this->go_to( home_url( '?page_id=' . $fr ) );

		$languages = apply_filters( 'wpml_active_languages', null );

		$expected = array (
			'id' => self::$polylang->model->get_language( 'fr' )->term_id,
			'active' => 1,
			'native_name' => 'Français',
			'missing' => 0,
			'translated_name' => '',
			'language_code' => 'fr',
			'country_flag_url' => plugins_url( '/flags/fr.png', POLYLANG_FILE ),
			'url' => home_url( "?page_id={$fr}&lang=fr" ),
		);

		$this->assertCount( 2, $languages );
		$this->assertEqualSets( $expected, $languages['fr'] );
		$this->assertEquals( 1, $languages['en']['missing'] );

		$languages = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 1 ) );

		$this->assertCount( 1, $languages );
		$this->assertNotEmpty( $languages['fr'] );
	}

	function test_wpml_current_language() {
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );
		$this->assertEquals( 'en', apply_filters( 'wpml_current_language', null ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		$this->assertEquals( 'fr', apply_filters( 'wpml_current_language', null ) );
	}

	function test_wpml_default_language() {
		self::$polylang->options['default_lang'] = 'fr';
		$this->assertEquals( 'fr', apply_filters( 'wpml_default_language', null ) );
		$this->assertEquals( 'fr', icl_get_default_language() ); // Legacy
		$this->assertEquals( 'fr', wpml_get_default_language() ); // Legacy
	}

	function test_wpml_add_language_form_field() {
		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		ob_start();
		do_action( 'wpml_add_language_form_field' );

		$this->assertEquals( '<input type="hidden" name="lang" value="fr" />', ob_get_clean() );
	}

	function test_wpml_post_language_details() {
		$id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $id, 'en' );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		$lang = apply_filters( 'wpml_post_language_details', null, $id );

		$this->assertEquals( 'en', $lang['language_code'] );
		$this->assertEquals( 'en_US', $lang['locale'] );
		$this->assertFalse( $lang['text_direction'] );
		$this->assertEquals( 'English', $lang['native_name'] );
		$this->assertTrue( $lang['different_language'] );

		$GLOBALS['post'] = get_post( $id );
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );
		$lang = apply_filters( 'wpml_post_language_details', null );

		$this->assertEquals( 'en', $lang['language_code'] );
		$this->assertFalse( $lang['different_language'] );
	}

	function test_wpml_language_code() {
		$id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $id, 'en' );

		$this->assertEquals( 'en', apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $id, 'element_type' => 'page' ) ) );

		$id = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $id, 'en' );

		$this->assertEquals( 'en', apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $id, 'element_type' => 'category' ) ) );
	}

	function test_wpml_home_url() {
		self::$polylang->links = new PLL_Frontend_Links( self::$polylang );
		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );

		$this->assertEquals( 'http://example.org/?lang=fr', apply_filters( 'wpml_home_url', null ) );
		$this->assertEquals( 'http://example.org/?lang=fr', icl_get_home_url() ); // Legacy
	}

	function test_wpml_element_link() {
		global $wp_rewrite;

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );
		create_initial_taxonomies(); // Needed for catery links

		self::$polylang->options['force_lang'] = 1;
		self::$polylang->links_model = self::$polylang->model->get_links_model();
		self::$polylang->links_model->init();

		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		self::$polylang->init();

		// de-activate cache for links
		self::$polylang->links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		self::$polylang->links->cache->method( 'get' )->willReturn( false );

		self::$polylang->filters_links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		self::$polylang->filters_links->cache->method( 'get' )->willReturn( false );

		$en = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'test' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$tag = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $tag, 'en' );

		ob_start();
		$link = apply_filters( 'wpml_element_link', $en );
		$this->assertEquals( '<a href="http://example.org/en/test/">test</a>', ob_get_clean() ); // default echo true
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
		$this->assertEquals( '', ob_get_clean() ); // echo false

		ob_start();
		apply_filters( 'wpml_element_link', $tag, 'tag' );
		$this->assertEquals( '<a href="http://example.org/en/tag/test/">test</a>', ob_get_clean() );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );

		ob_start();
		$link = apply_filters( 'wpml_element_link', $en );
		$this->assertEquals( '<a href="http://example.org/en/test/">test</a>', ob_get_clean() ); // return_original_if_missing true

		$link = apply_filters( 'wpml_element_link', $en, 'page', '', '', '', false, false );
		$this->assertEquals( '', $link ); // return_original_if_missing false

		$fr = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'test fr' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );
		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		ob_start();
		apply_filters( 'wpml_element_link', $en, 'page', '', '', '' ); // Translation
		$this->assertEquals( '<a href="http://example.org/fr/test-fr/">test fr</a>', ob_get_clean() );
	}

	function test_wpml_object_id() {
		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );

		$en = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'fr' ) );

		$this->assertEquals( $fr, apply_filters( 'wpml_object_id', $fr, 'page' ) );
		$this->assertEquals( $fr, apply_filters( 'wpml_object_id', $en, 'page' ) );
		$this->assertEquals( $en, apply_filters( 'wpml_object_id', $fr, 'page', false, 'en' ) );

		$cat = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $cat, 'en' );

		$this->assertNull( apply_filters( 'wpml_object_id', $cat, 'category' ) );
		$this->assertEquals( $cat, apply_filters( 'wpml_object_id', $cat, 'category', true ) );
	}

	function test_wpml_element_has_translations() {
		$en = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'fr' ) );

		$id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $id, 'en' );

		$this->assertTrue( apply_filters( 'wpml_element_has_translations', null, $en, 'page' ) );
		$this->assertTrue( apply_filters( 'wpml_element_has_translations', null, $fr, 'page' ) );
		$this->assertFalse( apply_filters( 'wpml_element_has_translations', null, $id, 'page' ) );

		$en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'fr' ) );

		$id = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $id, 'en' );

		$this->assertTrue( apply_filters( 'wpml_element_has_translations', null, $en, 'category' ) );
		$this->assertTrue( apply_filters( 'wpml_element_has_translations', null, $fr, 'category' ) );
		$this->assertFalse( apply_filters( 'wpml_element_has_translations', null, $id, 'category' ) );
	}

	function test_strings_translations() {
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
			$language = self::$polylang->model->get_language( $lang );
			$mo = new PLL_MO();
			$mo->import_from_db( $language );
			$mo->add_entry( $mo->make_entry( 'wpml_string_test', "wpml_string_test_$lang" ) );
			$mo->export_to_db( $language );
		}

		$GLOBALS['polylang'] = self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		do_action( 'pll_language_defined' );

		$this->assertEquals( 'wpml_string_test_en', apply_filters( 'wpml_translate_single_string', 'wpml_string_test', 'wpml_string_context', 'wpml_string_name', 'en' ) );
		$this->assertEquals( 'wpml_string_test_fr', apply_filters( 'wpml_translate_single_string', 'wpml_string_test', 'wpml_string_context', 'wpml_string_name' ) );

		// Legacy
		$this->assertEquals( 'wpml_string_test_fr', icl_t( 'wpml_string_context', 'wpml_string_name' ) );
	}
}
