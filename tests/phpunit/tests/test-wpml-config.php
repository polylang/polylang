<?php

class WPML_Config_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		@mkdir( WP_CONTENT_DIR . '/polylang' );
		copy( dirname( __FILE__ ) . '/../data/wpml-config.xml', WP_CONTENT_DIR . '/polylang/wpml-config.xml' );

		require_once PLL_INC . '/api.php';
		$GLOBALS['polylang'] = &self::$polylang;
	}

	function setUp() {
		parent::setUp();

		$this->prepare_options(); // before reading the wpml-config.xml file
		$this->translate_options( 'fr' );
	}

	static function wpTearDownAfterClass() {
		parent::wpTearDownAfterClass();

		unlink( WP_CONTENT_DIR . '/polylang/wpml-config.xml' );
		rmdir( WP_CONTENT_DIR . '/polylang' );
	}

	function prepare_options() {
		// mirror options defined in the sample wpml-config.xml
		$my_plugins_options = array(
			'option_name_1'   => 'val1',
			'option_name_2'   => 'val2',
			'options_group_1' => array(
				'sub_option_name_11' => 'val11',
				'sub_option_name_12' => 'val12',
			),
			'options_group_2' => array(
				'sub_key_21' => 'val21',
				'sub_key_22' => array(
					'sub_sub_221' => 'val221',
					'sub_sub_222' => array(
						'sub_sub_sub_2221' => 'val2221',
					),
				),
			),
		);
		update_option( 'my_plugins_options', $my_plugins_options );
		update_option( 'simple_string_option', 'val' );
	}

	function translate_options( $slug ) {
		$language = self::$polylang->model->get_language( $slug );
		$mo = new PLL_MO();
		$mo->import_from_db( $language );
		$mo->add_entry( $mo->make_entry( 'val', "val_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'val1', "val1_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'val2', "val2_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'val11', "val11_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'val12', "val12_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'val21', "val21_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'val221', "val221_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'val2221', "val2221_$slug" ) );
		$mo->export_to_db( $language );
	}

	function test_cf() {
		self::$polylang = new PLL_Admin( self::$polylang->links_model );
		PLL_WPML_Config::instance()->init();

		$en = $from = $this->factory->post->create();
		self::$polylang->model->post->set_language( $from, 'en' );
		add_post_meta( $from, 'quantity', 1 ); // copy
		add_post_meta( $from, 'custom-title', 'title' ); // translate
		add_post_meta( $from, 'bg-color', '#23282d' ); // copy-once
		add_post_meta( $from, 'date-added', 2007 ); // ignore

		$fr = $to = $this->factory->post->create();
		self::$polylang->model->post->set_language( $to, 'fr' );
		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		// copy
		$sync = new PLL_Admin_Sync( self::$polylang );
		$sync->post_metas->copy( $from, $to, 'fr' ); // copy

		$this->assertEquals( 1, get_post_meta( $to, 'quantity', true ) );
		$this->assertEquals( 'title', get_post_meta( $to, 'custom-title', true ) );
		$this->assertEquals( '#23282d', get_post_meta( $to, 'bg-color', true ) );
		$this->assertEmpty( get_post_meta( $to, 'date-added', true ) );

		// sync
		update_post_meta( $to, 'quantity', 2 );
		update_post_meta( $to, 'custom-title', 'titre' );
		update_post_meta( $to, 'bg-color', '#ffeedd' );
		update_post_meta( $to, 'date-added', 2008 );

		$this->assertEquals( 2, get_post_meta( $from, 'quantity', true ) );
		$this->assertEquals( 'title', get_post_meta( $from, 'custom-title', true ) );
		$this->assertEquals( '#23282d', get_post_meta( $from, 'bg-color', true ) );
		$this->assertEquals( 2007, get_post_meta( $from, 'date-added', true ) );

		// remove custom field and sync
		delete_post_meta( $to, 'quantity' );
		delete_post_meta( $to, 'custom-title' );
		delete_post_meta( $to, 'bg-color' );
		delete_post_meta( $to, 'date-added' );

		$this->assertEmpty( get_post_meta( $from, 'quantity', true ) );
		$this->assertEquals( 'title', get_post_meta( $from, 'custom-title', true ) );
		$this->assertEquals( '#23282d', get_post_meta( $from, 'bg-color', true ) );
		$this->assertEquals( 2007, get_post_meta( $from, 'date-added', true ) );
	}

	function test_cpt() {
		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		PLL_WPML_Config::instance()->init();

		register_post_type( 'book' ); // translated
		register_post_type( 'DVD' ); // untranslated
		self::$polylang->model->cache->clean( 'post_types' );

		$this->assertTrue( self::$polylang->model->is_translated_post_type( 'book' ) );
		$this->assertFalse( self::$polylang->model->is_translated_post_type( 'DVD' ) );

		// settings
		$post_types = get_post_types( array( 'public' => true, '_builtin' => false ) );
		$post_types = array_diff( $post_types, get_post_types( array( '_pll' => true ) ) );
		$post_types = array_unique( apply_filters( 'pll_get_post_types', $post_types, true ) );
		$this->assertNotContains( 'book', $post_types );
		$this->assertNotContains( 'DVD', $post_types );

		_unregister_post_type( 'book' );
		_unregister_post_type( 'DVD' );
	}

	function test_tax() {
		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		PLL_WPML_Config::instance()->init();

		register_post_type( 'book' ); // translated
		register_taxonomy( 'genre', 'book' ); // translated
		register_taxonomy( 'publisher', 'book' ); // untranslated
		self::$polylang->model->cache->clean( 'taxonomies' );

		$this->assertTrue( self::$polylang->model->is_translated_taxonomy( 'genre' ) );
		$this->assertFalse( self::$polylang->model->is_translated_taxonomy( 'publisher' ) );

		// settings
		$taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ) );
		$taxonomies = array_diff( $taxonomies, get_taxonomies( array( '_pll' => true ) ) );
		$taxonomies = array_unique( apply_filters( 'pll_get_taxonomies', $taxonomies, true ) );
		$this->assertNotContains( 'genre', $taxonomies );
		$this->assertNotContains( 'publisher', $taxonomies );

		_unregister_post_type( 'book' );
		_unregister_taxonomy( 'genre' );
		_unregister_taxonomy( 'publisher' );
	}

	function test_translate_strings() {
		$GLOBALS['polylang'] = self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		PLL_WPML_Config::instance()->init();

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		do_action( 'pll_language_defined' );

		$options = get_option( 'my_plugins_options' );
		$this->assertEquals( 'val2_fr', $options['option_name_2'] );
		$this->assertEquals( 'val12_fr', $options['options_group_1']['sub_option_name_12'] );
		$this->assertEquals( 'val21_fr', $options['options_group_2']['sub_key_21'] );
		$this->assertEquals( 'val221_fr', $options['options_group_2']['sub_key_22']['sub_sub_221'] );
		$this->assertEquals( 'val2221_fr', $options['options_group_2']['sub_key_22']['sub_sub_222']['sub_sub_sub_2221'] );
		$this->assertEquals( 'val_fr', get_option( 'simple_string_option' ) );
	}

	function test_register_string() {
		$GLOBALS['polylang'] = self::$polylang = new PLL_Admin( self::$polylang->links_model );
		PLL_WPML_Config::instance()->init();

		$strings = wp_list_pluck( PLL_Admin_Strings::get_strings(), 'string' );
		$this->assertContains( 'val2', $strings );
		$this->assertContains( 'val12', $strings );
		$this->assertContains( 'val21', $strings );
		$this->assertContains( 'val221', $strings );
		$this->assertContains( 'val2221', $strings );
		$this->assertContains( 'val', $strings );
	}
}
