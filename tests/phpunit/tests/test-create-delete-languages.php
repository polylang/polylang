<?php

class Create_Delete_Languages_Test extends PLL_UnitTestCase {

	function test_add_and_delete_language() {
		// first language
		$args = array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 2,
		);

		$this->assertTrue( self::$polylang->model->add_language( $args ) );

		$lang = self::$polylang->model->get_language( 'en' );

		$this->assertEquals( 'English', $lang->name );
		$this->assertEquals( 'en', $lang->slug );
		$this->assertEquals( 'en_US', $lang->locale );
		$this->assertEquals( 0, $lang->is_rtl );
		$this->assertEquals( 2, $lang->term_group );

		// second language (rtl)
		$args = array(
			'name'       => 'العربية',
			'slug'       => 'ar',
			'locale'     => 'ar',
			'rtl'        => 1,
			'flag'       => 'arab',
			'term_group' => 1,
		);

		$this->assertTrue( self::$polylang->model->add_language( $args ) );

		$lang = self::$polylang->model->get_language( 'ar' );

		$this->assertEquals( 'العربية', $lang->name );
		$this->assertEquals( 'ar', $lang->slug );
		$this->assertEquals( 'ar', $lang->locale );
		$this->assertEquals( 1, $lang->is_rtl );
		$this->assertEquals( 1, $lang->term_group );

		// check default language
		$this->assertEquals( 'en', self::$polylang->options['default_lang'] );

		// check default category
		$default_cat_lang = self::$polylang->model->term->get_language( get_option( 'default_category' ) );
		$this->assertEquals( 'en', $default_cat_lang->slug );

		// check language order
		$this->assertEqualSetsWithIndex( array( 'ar', 'en' ), self::$polylang->model->get_languages_list( array( 'fields' => 'slug' ) ) );

		// attempt to create a language with the same slug as an existing one
		self::create_language( 'en_GB' );
		$lang = self::$polylang->model->get_language( 'en' );
		$this->assertEquals( 'en_US', $lang->locale );
		$this->assertFalse( self::$polylang->model->get_language( 'en_GB' ) );
		$this->assertEquals( 2, count( self::$polylang->model->get_languages_list() ) );

		// delete 1 language
		$lang = self::$polylang->model->get_language( 'en_US' );
		self::$polylang->model->delete_language( $lang->term_id );
		$this->assertEquals( 'ar', self::$polylang->options['default_lang'] );

		// delete the last language
		$lang = self::$polylang->model->get_language( 'ar' );
		self::$polylang->model->delete_language( $lang->term_id );
		$this->assertEquals( array(), self::$polylang->model->get_languages_list() );
	}

	// Bug fixed in 2.3
	function test_unique_language_code_if_same_as_locale() {
		// First language
		$args = array(
			'name'       => 'العربية',
			'slug'       => 'a', // Intentional mistake
			'locale'     => 'ar',
			'rtl'        => 1,
			'flag'       => 'arab',
			'term_group' => 1,
		);

		$this->assertTrue( self::$polylang->model->add_language( $args ) );

		$lang = self::$polylang->model->get_language( 'ar' );
		$args['lang_id'] = $lang->term_id;
		$args['slug'] = 'ar';
		$this->assertTrue( self::$polylang->model->update_language( $args ) );

		self::$polylang->model->delete_language( $lang->term_id );
	}

	function test_invalid_languages() {
		global $wp_settings_errors;

		$args = array(
			'name'       => '',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 1,
		);

		$this->assertWPError( self::$polylang->model->add_language( $args ), 'The language must have a name' );

		$args['name'] = 'English';
		$args['locale'] = 'EN';

		$this->assertWPError( self::$polylang->model->add_language( $args ), 'Enter a valid WordPress locale' );

		$args['locale'] = 'en-US';

		$this->assertWPError( self::$polylang->model->add_language( $args ), 'Enter a valid WordPress locale' );

		$args['locale'] = 'en_US';
		$args['slug'] = 'EN';

		$this->assertWPError( self::$polylang->model->add_language( $args ), 'The language code contains invalid characters' );

		$args['slug'] = 'en';
		$args['flag'] = 'en';

		$this->assertWPError( self::$polylang->model->add_language( $args ), 'The flag does not exist' );
	}
}
