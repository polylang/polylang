<?php

namespace WP_Syntex\Polylang\Tests\Model\Languages;

use PLL_UnitTestCase;
use WP_Syntex\Polylang\Model\Languages;
use PLL_Translatable_Objects;
use PLL_Cache;
use PLL_Language;
use WP_Error;

class Test_CRUD extends PLL_UnitTestCase {

	private $languages;

	public function set_up() {
		parent::set_up();

		$options         = self::create_options();
		$this->languages = new Languages(
			$options,
			new PLL_Translatable_Objects(),
			new PLL_Cache()
		);
	}

	public function test_create_language() {
		$args = array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => false,
			'flag'       => 'us',
			'term_group' => 1,
		);

		$result = $this->languages->add( $args );

		$this->assertTrue( $result );

		$language = $this->languages->get( 'en' );
		$this->assertInstanceOf( PLL_Language::class, $language );
		$this->assertSame( 'English', $language->name );
		$this->assertSame( 'en', $language->slug );
		$this->assertSame( 'en_US', $language->locale );
		$this->assertSame( 0, $language->is_rtl );
		$this->assertSame( 1, $language->term_group );
		$this->assertSame( 'us', $language->flag_code );

		$default = $this->languages->get_default();
		$this->assertInstanceOf( PLL_Language::class, $default );
		$this->assertSame( 'en', $default->slug );
	}

	public function test_create_language_with_minimal_args() {
		$args = array(
			'locale' => 'fr_FR',
		);

		$result = $this->languages->add( $args );

		$this->assertTrue( $result );

		$language = $this->languages->get( 'fr' );
		$this->assertInstanceOf( PLL_Language::class, $language );
		$this->assertSame( 'Français', $language->name );
		$this->assertSame( 'fr', $language->slug );
		$this->assertSame( 'fr_FR', $language->locale );
		$this->assertSame( 0, $language->is_rtl );
		$this->assertSame( 0, $language->term_group );
	}

	public function test_create_rtl_language() {
		$args = array(
			'name'       => 'العربية',
			'slug'       => 'ar',
			'locale'     => 'ar',
			'rtl'        => true,
			'flag'       => 'arab',
			'term_group' => 2,
		);

		$result = $this->languages->add( $args );

		$this->assertTrue( $result );

		$language = $this->languages->get( 'ar' );
		$this->assertInstanceOf( PLL_Language::class, $language );
		$this->assertSame( 1, $language->is_rtl );
	}

	public function test_create_language_with_invalid_locale() {
		$args = array(
			'name'   => 'Invalid',
			'slug'   => 'inv',
			'locale' => 'invalid_locale',
		);

		$result = $this->languages->add( $args );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'pll_invalid_locale', $result->get_error_code() );
	}

	public function test_create_language_with_invalid_slug() {
		$args = array(
			'name'   => 'Invalid',
			'slug'   => '123invalid',
			'locale' => 'en_US',
		);

		$result = $this->languages->add( $args );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'pll_invalid_slug', $result->get_error_code() );
	}

	public function test_create_language_with_duplicate_slug() {
		$args1 = array(
			'name'   => 'English',
			'slug'   => 'en',
			'locale' => 'en_US',
		);
		$this->languages->add( $args1 );

		$args2 = array(
			'name'   => 'French',
			'slug'   => 'en',
			'locale' => 'fr_FR',
		);

		$result = $this->languages->add( $args2 );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'pll_non_unique_slug', $result->get_error_code() );
	}

	public function test_create_language_with_invalid_data() {
		$args = array(
			'name'       => '',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 1,
		);

		$this->assertWPError( $this->languages->add( $args ), 'The language must have a name' );

		$args['name']   = 'English';
		$args['locale'] = 'EN';

		$this->assertWPError( $this->languages->add( $args ), 'Enter a valid WordPress locale' );

		$args['locale'] = 'en-US';

		$this->assertWPError( $this->languages->add( $args ), 'Enter a valid WordPress locale' );

		$args['locale'] = 'en_US';
		$args['slug']   = 'EN';

		$this->assertWPError( $this->languages->add( $args ), 'The language code contains invalid characters' );

		$args['slug'] = 'en';
		$args['flag'] = 'en';

		$this->assertWPError( $this->languages->add( $args ), 'The flag does not exist' );
	}

	public function test_read_language_by_slug() {
		$args = array(
			'name'   => 'English',
			'slug'   => 'en',
			'locale' => 'en_US',
		);
		$this->languages->add( $args );

		$language = $this->languages->get( 'en' );

		$this->assertInstanceOf( PLL_Language::class, $language );
		$this->assertSame( 'en', $language->slug );
	}

	public function test_read_language_by_locale() {
		$args = array(
			'name'   => 'English',
			'slug'   => 'en',
			'locale' => 'en_US',
		);
		$this->languages->add( $args );

		$language = $this->languages->get( 'en_US' );

		$this->assertInstanceOf( PLL_Language::class, $language );
		$this->assertSame( 'en_US', $language->locale );
	}

	public function test_read_nonexistent_language() {
		$language = $this->languages->get( 'nonexistent' );

		$this->assertFalse( $language );
	}

	public function test_get_languages_list() {
		$args1 = array(
			'name'   => 'English',
			'slug'   => 'en',
			'locale' => 'en_US',
		);
		$args2 = array(
			'name'   => 'French',
			'slug'   => 'fr',
			'locale' => 'fr_FR',
		);

		$this->languages->add( $args1 );
		$this->languages->add( $args2 );

		$languages = $this->languages->get_list();

		$this->assertIsArray( $languages );
		$this->assertCount( 2, $languages );

		$slugs = $this->languages->get_list( array( 'fields' => 'slug' ) );
		$this->assertContains( 'en', $slugs );
		$this->assertContains( 'fr', $slugs );
	}

	public function test_update_language() {
		$args = array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => false,
			'term_group' => 1,
		);
		$this->languages->add( $args );
		$language = $this->languages->get( 'en' );

		$update_args = array(
			'lang_id'    => $language->term_id,
			'name'       => 'English Updated',
			'slug'       => 'en_updated',
			'locale'     => 'en_GB',
			'rtl'        => false,
			'term_group' => 2,
		);

		$result = $this->languages->update( $update_args );

		$this->assertTrue( $result );

		$updated_language = $this->languages->get( 'en_updated' );
		$this->assertInstanceOf( PLL_Language::class, $updated_language );
		$this->assertSame( 'English Updated', $updated_language->name );
		$this->assertSame( 'en_updated', $updated_language->slug );
		$this->assertSame( 'en_GB', $updated_language->locale );
		$this->assertSame( 2, $updated_language->term_group );
	}

	public function test_update_language_with_same_slug() {
		$args = array(
			'name'   => 'English',
			'slug'   => 'en',
			'locale' => 'en_US',
		);
		$this->languages->add( $args );
		$language = $this->languages->get( 'en' );

		$update_args = array(
			'lang_id'    => $language->term_id,
			'name'       => 'English Updated',
			'slug'   => 'en',
			'locale' => 'en_GB',
		);

		$result = $this->languages->update( $update_args );
		$this->assertTrue( $result );
	}

	public function test_update_nonexistent_language() {
		$update_args = array(
			'lang_id' => 99999,
			'name'    => 'Updated',
		);

		$result = $this->languages->update( $update_args );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'pll_invalid_language_id', $result->get_error_code() );
	}

	public function test_update_language_with_invalid_data() {
		$args = array(
			'name'   => 'English',
			'slug'   => 'en',
			'locale' => 'en_US',
		);
		$this->languages->add( $args );
		$language = $this->languages->get( 'en' );

		$update_args = array(
			'lang_id' => $language->term_id,
			'locale'  => 'invalid_locale',
		);

		$result = $this->languages->update( $update_args );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'pll_invalid_locale', $result->get_error_code() );
	}

	/**
	 * Bug fixed in 2.3.
	 */
	public function test_update_language_with_code_same_as_locale() {
		// First language.
		$args = array(
			'name'       => 'العربية',
			'slug'       => 'a', // Intentional mistake.
			'locale'     => 'ar',
			'rtl'        => 1,
			'flag'       => 'arab',
			'term_group' => 1,
		);

		$this->assertTrue( $this->languages->add( $args ) );

		$lang            = $this->languages->get( 'ar' );
		$args['lang_id'] = $lang->term_id;
		$args['slug']    = 'ar';

		$this->assertTrue( $this->languages->update( $args ) );
	}

	public function test_delete_language() {
		$args1 = array(
			'name'   => 'English',
			'slug'   => 'en',
			'locale' => 'en_US',
		);
		$args2 = array(
			'name'   => 'French',
			'slug'   => 'fr',
			'locale' => 'fr_FR',
		);

		$this->languages->add( $args1 );
		$this->languages->add( $args2 );

		$languages = $this->languages->get_list();
		$this->assertCount( 2, $languages );

		$language = $this->languages->get( 'fr' );
		$result = $this->languages->delete( $language->term_id );

		$this->assertTrue( $result );

		$deleted_language = $this->languages->get( 'fr' );
		$this->assertFalse( $deleted_language );

		$remaining_language = $this->languages->get( 'en' );
		$this->assertInstanceOf( PLL_Language::class, $remaining_language );

		$default = $this->languages->get_default();
		$this->assertInstanceOf( PLL_Language::class, $default );
		$this->assertSame( 'en', $default->slug );
	}

	public function test_delete_default_language() {
		$args1 = array(
			'name'   => 'English',
			'slug'   => 'en',
			'locale' => 'en_US',
		);
		$args2 = array(
			'name'   => 'French',
			'slug'   => 'fr',
			'locale' => 'fr_FR',
		);

		$this->languages->add( $args1 );
		$this->languages->add( $args2 );

		$default = $this->languages->get_default();
		$this->assertSame( 'en', $default->slug );

		$result = $this->languages->delete( $default->term_id );

		$this->assertTrue( $result );

		$new_default = $this->languages->get_default();
		$this->assertInstanceOf( PLL_Language::class, $new_default );
		$this->assertSame( 'fr', $new_default->slug );
	}

	public function test_delete_nonexistent_language() {
		$result = $this->languages->delete( 666 );

		$this->assertFalse( $result );
	}

	public function test_get_default_language() {
		$default = $this->languages->get_default();
		$this->assertFalse( $default );

		$args = array(
			'name'   => 'English',
			'slug'   => 'en',
			'locale' => 'en_US',
		);
		$this->languages->add( $args );

		$default = $this->languages->get_default();
		$this->assertInstanceOf( PLL_Language::class, $default );
		$this->assertSame( 'en', $default->slug );
	}

	public function test_update_default_language() {
		$args1 = array(
			'name'   => 'English',
			'slug'   => 'en',
			'locale' => 'en_US',
		);
		$args2 = array(
			'name'   => 'French',
			'slug'   => 'fr',
			'locale' => 'fr_FR',
		);

		$this->languages->add( $args1 );
		$this->languages->add( $args2 );

		$default = $this->languages->get_default();
		$this->assertSame( 'en', $default->slug );

		$result = $this->languages->update_default( 'fr' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertFalse( $result->has_errors() );

		$new_default = $this->languages->get_default();
		$this->assertSame( 'fr', $new_default->slug );
	}
}
