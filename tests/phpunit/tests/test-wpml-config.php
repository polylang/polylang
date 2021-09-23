<?php

class WPML_Config_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		@mkdir( WP_CONTENT_DIR . '/polylang' );
		copy( dirname( __FILE__ ) . '/../data/wpml-config.xml', WP_CONTENT_DIR . '/polylang/wpml-config.xml' );

		require_once POLYLANG_DIR . '/include/api.php';
	}

	static function wpTearDownAfterClass() {
		parent::wpTearDownAfterClass();

		unlink( WP_CONTENT_DIR . '/polylang/wpml-config.xml' );
		rmdir( WP_CONTENT_DIR . '/polylang' );
	}

	function set_up() {
		parent::set_up();

		$this->links_model = self::$model->get_links_model();
	}

	function prepare_options( $method = 'ARRAY' ) {
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
			'options_group_3' => array(
				'sub_key_31' => array(
					'sub_sub_option_name_3x1' => 'val311',
					'sub_sub_option_name_3x2' => 'val312',
					'sub_sub_option_name_3x3' => 'val313',
				),
				'sub_key_32' => array(
					'sub_sub_option_name_3x1' => 'val321',
					'sub_sub_option_name_3x2' => 'val322',
					'sub_sub_option_name_3x3' => 'val323',
				),
			),
			'options_group_4' => array(
				'sub_option_name_41' => 'val41',
				'sub_option_name_42' => 'val42',
				'sub_option_diff_43' => 'val43',
			),
		);

		if ( 'OBJECT' === $method ) {
			$my_plugins_options = json_decode( json_encode( $my_plugins_options ) ); // Recursively converts the arrays to objects.
		}

		update_option( 'my_plugins_options', $my_plugins_options );
		update_option( 'simple_string_option', 'val' );
		update_option( 'generic_option_1', 'generic_val_1' );
		update_option( 'generic_option_2', 'generic_val_2' );
	}

	function translate_options( $slug ) {
		$language = self::$model->get_language( $slug );
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
		$mo->add_entry( $mo->make_entry( 'val311', "val311_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'val312', "val312_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'val313', "val313_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'val321', "val321_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'val322', "val322_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'val323', "val323_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'val41', "val41_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'val42', "val42_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'val43', "val43_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'generic_val_1', "generic_val_1_$slug" ) );
		$mo->add_entry( $mo->make_entry( 'generic_val_2', "generic_val_2_$slug" ) );
		$mo->export_to_db( $language );
	}

	function test_cf() {
		wp_set_current_user( 1 ); // To pass current_user_can_synchronize() test

		$pll_admin = new PLL_Admin( $this->links_model );
		PLL_WPML_Config::instance()->init();

		$en = $from = $this->factory->post->create();
		self::$model->post->set_language( $from, 'en' );
		add_post_meta( $from, 'quantity', 1 ); // copy
		add_post_meta( $from, 'custom-title', 'title' ); // translate
		add_post_meta( $from, 'bg-color', '#23282d' ); // copy-once
		add_post_meta( $from, 'date-added', 2007 ); // ignore

		$fr = $to = $this->factory->post->create();
		self::$model->post->set_language( $to, 'fr' );
		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		// copy
		$sync = new PLL_Admin_Sync( $pll_admin );
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

	function test_custom_term_field() {
		$pll_admin = new PLL_Admin( $this->links_model );
		PLL_WPML_Config::instance()->init();

		$en = $from = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $from, 'en' );
		add_term_meta( $from, 'term_meta_A', 'A' ); // copy
		add_term_meta( $from, 'term_meta_B', 'B' ); // translate
		add_term_meta( $from, 'term_meta_C', 'C' ); // ignore
		add_term_meta( $from, 'term_meta_D', 'D' ); // copy-once

		$fr = $to = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $to, 'fr' );
		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		// Copy
		$sync = new PLL_Admin_Sync( $pll_admin );
		$sync->term_metas->copy( $from, $to, 'fr' ); // copy

		$this->assertEquals( 'A', get_term_meta( $to, 'term_meta_A', true ) );
		$this->assertEquals( 'B', get_term_meta( $to, 'term_meta_B', true ) );
		$this->assertEmpty( get_term_meta( $to, 'term_meta_C', true ) );
		$this->assertEquals( 'D', get_term_meta( $to, 'term_meta_D', true ) );

		// Sync
		update_term_meta( $to, 'term_meta_A', 'A2' );
		update_term_meta( $to, 'term_meta_B', 'B2' );
		update_term_meta( $to, 'term_meta_C', 'C2' );
		update_term_meta( $to, 'term_meta_D', 'D2' );

		$this->assertEquals( 'A2', get_term_meta( $from, 'term_meta_A', true ) );
		$this->assertEquals( 'B', get_term_meta( $from, 'term_meta_B', true ) );
		$this->assertEquals( 'C', get_term_meta( $from, 'term_meta_C', true ) );
		$this->assertEquals( 'D', get_term_meta( $from, 'term_meta_D', true ) );

		// Remove custom field and sync
		delete_term_meta( $to, 'term_meta_A' );
		delete_term_meta( $to, 'term_meta_B' );
		delete_term_meta( $to, 'term_meta_C' );
		delete_term_meta( $to, 'term_meta_D' );

		$this->assertEmpty( get_term_meta( $from, 'term_meta_A', true ) );
		$this->assertEquals( 'B', get_term_meta( $from, 'term_meta_B', true ) );
		$this->assertEquals( 'C', get_term_meta( $from, 'term_meta_C', true ) );
		$this->assertEquals( 'D', get_term_meta( $from, 'term_meta_D', true ) );
	}

	function test_cpt() {
		$frontend = new PLL_Frontend( $this->links_model );
		PLL_WPML_Config::instance()->init();

		register_post_type( 'book' ); // translated
		register_post_type( 'dvd' ); // untranslated
		self::$model->cache->clean( 'post_types' );

		$this->assertTrue( self::$model->is_translated_post_type( 'book' ) );
		$this->assertFalse( self::$model->is_translated_post_type( 'dvd' ) );

		// settings
		$post_types = get_post_types( array( 'public' => true, '_builtin' => false ) );
		$post_types = array_diff( $post_types, get_post_types( array( '_pll' => true ) ) );
		$post_types = array_unique( apply_filters( 'pll_get_post_types', $post_types, true ) );
		$this->assertNotContains( 'book', $post_types );
		$this->assertNotContains( 'dvd', $post_types );

		_unregister_post_type( 'book' );
		_unregister_post_type( 'dvd' );
	}

	function test_tax() {
		$frontend = new PLL_Frontend( $this->links_model );
		PLL_WPML_Config::instance()->init();

		register_post_type( 'book' ); // translated
		register_taxonomy( 'genre', 'book' ); // translated
		register_taxonomy( 'publisher', 'book' ); // untranslated
		self::$model->cache->clean( 'taxonomies' );

		$this->assertTrue( self::$model->is_translated_taxonomy( 'genre' ) );
		$this->assertFalse( self::$model->is_translated_taxonomy( 'publisher' ) );

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
		$this->prepare_options( 'ARRAY' ); // Before reading the wpml-config.xml file.
		$this->translate_options( 'fr' );

		$GLOBALS['polylang'] = $frontend = new PLL_Frontend( $this->links_model );
		PLL_WPML_Config::instance()->init();

		$frontend->curlang = self::$model->get_language( 'fr' );
		do_action( 'pll_language_defined' );

		$options = get_option( 'my_plugins_options' );
		$this->assertEquals( 'val2_fr', $options['option_name_2'] );
		$this->assertEquals( 'val12_fr', $options['options_group_1']['sub_option_name_12'] );
		$this->assertEquals( 'val21_fr', $options['options_group_2']['sub_key_21'] );
		$this->assertEquals( 'val221_fr', $options['options_group_2']['sub_key_22']['sub_sub_221'] );
		$this->assertEquals( 'val2221_fr', $options['options_group_2']['sub_key_22']['sub_sub_222']['sub_sub_sub_2221'] );
		$this->assertEquals( 'val311_fr', $options['options_group_3']['sub_key_31']['sub_sub_option_name_3x1'] );
		$this->assertEquals( 'val312_fr', $options['options_group_3']['sub_key_31']['sub_sub_option_name_3x2'] );
		$this->assertEquals( 'val313', $options['options_group_3']['sub_key_31']['sub_sub_option_name_3x3'] ); // This one must not be translated.
		$this->assertEquals( 'val321_fr', $options['options_group_3']['sub_key_32']['sub_sub_option_name_3x1'] );
		$this->assertEquals( 'val322_fr', $options['options_group_3']['sub_key_32']['sub_sub_option_name_3x2'] );
		$this->assertEquals( 'val323', $options['options_group_3']['sub_key_32']['sub_sub_option_name_3x3'] ); // This one must not be translated.
		$this->assertEquals( 'val41_fr', $options['options_group_4']['sub_option_name_41'] );
		$this->assertEquals( 'val42_fr', $options['options_group_4']['sub_option_name_42'] );
		$this->assertEquals( 'val43', $options['options_group_4']['sub_option_diff_43'] ); // This one must not be translated.
		$this->assertEquals( 'val_fr', get_option( 'simple_string_option' ) );
		$this->assertEquals( 'generic_val_1_fr', get_option( 'generic_option_1' ) );
		$this->assertEquals( 'generic_val_2_fr', get_option( 'generic_option_2' ) );
	}

	function test_translate_strings_object() {
		$this->prepare_options( 'OBJECT' ); // Before reading the wpml-config.xml file.
		$this->translate_options( 'fr' );

		$GLOBALS['polylang'] = $frontend = new PLL_Frontend( $this->links_model );
		PLL_WPML_Config::instance()->init();

		$frontend->curlang = self::$model->get_language( 'fr' );
		do_action( 'pll_language_defined' );

		$options = get_option( 'my_plugins_options' );
		$this->assertEquals( 'val2_fr', $options->option_name_2 );
		$this->assertEquals( 'val12_fr', $options->options_group_1->sub_option_name_12 );
		$this->assertEquals( 'val21_fr', $options->options_group_2->sub_key_21 );
		$this->assertEquals( 'val221_fr', $options->options_group_2->sub_key_22->sub_sub_221 );
		$this->assertEquals( 'val2221_fr', $options->options_group_2->sub_key_22->sub_sub_222->sub_sub_sub_2221 );
		$this->assertEquals( 'val311_fr', $options->options_group_3->sub_key_31->sub_sub_option_name_3x1 );
		$this->assertEquals( 'val312_fr', $options->options_group_3->sub_key_31->sub_sub_option_name_3x2 );
		$this->assertEquals( 'val313', $options->options_group_3->sub_key_31->sub_sub_option_name_3x3 ); // This one must not be translated.
		$this->assertEquals( 'val321_fr', $options->options_group_3->sub_key_32->sub_sub_option_name_3x1 );
		$this->assertEquals( 'val322_fr', $options->options_group_3->sub_key_32->sub_sub_option_name_3x2 );
		$this->assertEquals( 'val323', $options->options_group_3->sub_key_32->sub_sub_option_name_3x3 ); // This one must not be translated.
		$this->assertEquals( 'val41_fr', $options->options_group_4->sub_option_name_41 );
		$this->assertEquals( 'val42_fr', $options->options_group_4->sub_option_name_42 );
		$this->assertEquals( 'val43', $options->options_group_4->sub_option_diff_43 ); // This one must not be translated.
	}


	function _test_register_string() {
		$GLOBALS['polylang'] = new PLL_Admin( $this->links_model );
		PLL_WPML_Config::instance()->init();

		$strings = wp_list_pluck( PLL_Admin_Strings::get_strings(), 'string' );
		$this->assertContains( 'val2', $strings );
		$this->assertContains( 'val12', $strings );
		$this->assertContains( 'val21', $strings );
		$this->assertContains( 'val221', $strings );
		$this->assertContains( 'val2221', $strings );
		$this->assertContains( 'val311', $strings );
		$this->assertContains( 'val312', $strings );
		$this->assertNotContains( 'val313', $strings ); // This one must not be registered.
		$this->assertContains( 'val321', $strings );
		$this->assertContains( 'val322', $strings );
		$this->assertNotContains( 'val323', $strings ); // This one must not be registered.
		$this->assertContains( 'val41', $strings );
		$this->assertContains( 'val42', $strings );
		$this->assertNotContains( 'val43', $strings ); // This one must not be registered.
		$this->assertContains( 'val', $strings );
		$this->assertContains( 'generic_val_1', $strings );
		$this->assertContains( 'generic_val_2', $strings );
	}

	function test_register_string() {
		$this->prepare_options( 'ARRAY' );
		$this->_test_register_string();
	}

	function test_register_string_object() {
		$this->prepare_options( 'OBJECT' );
		$this->_test_register_string();
	}
}
