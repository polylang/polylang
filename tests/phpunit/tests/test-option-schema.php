<?php

use WP_Syntex\Polylang\Options\Business;

/**
 * Test the schema of all classes extending {@see WP_Syntex\Polylang\Options\Abstract_Option}.
 */
class Option_Schema_Test extends WP_UnitTestCase {
	/**
	 * @dataProvider boolean_provider
	 *
	 * @param mixed         $value           The value to test.
	 * @param bool          $sanitized_value Sanitized value.
	 * @param true|WP_Error $expected_valid  Validation result.
	 */
	public function test_boolean( $value, bool $sanitized_value, $expected_valid ) {
		$classes = array(
			Business\Browser::class,
			Business\Hide_Default::class,
			Business\Media_Support::class,
			Business\Redirect_Lang::class,
			Business\Rewrite::class,
		);
		foreach ( $classes as $class ) {
			$this->test_option( $class, $value, $sanitized_value, $expected_valid );
		}
	}

	/**
	 * @dataProvider domains_provider
	 *
	 * @param mixed         $value           The value to test.
	 * @param array         $sanitized_value Sanitized value.
	 * @param true|WP_Error $expected_valid  Validation result.
	 */
	public function test_domains( $value, array $sanitized_value, $expected_valid ) {
		$this->test_option( Business\Domains::class, $value, $sanitized_value, $expected_valid );
	}

	/**
	 * @dataProvider first_activation_provider
	 *
	 * @param mixed         $value           The value to test.
	 * @param int           $sanitized_value Sanitized value.
	 * @param true|WP_Error $expected_valid  Validation result.
	 */
	public function test_first_activation( $value, int $sanitized_value, $expected_valid ) {
		$this->test_option( Business\First_Activation::class, $value, $sanitized_value, $expected_valid );
	}

	/**
	 * @dataProvider force_lang_provider
	 *
	 * @param mixed         $value           The value to test.
	 * @param int           $sanitized_value Sanitized value.
	 * @param true|WP_Error $expected_valid  Validation result.
	 */
	public function test_force_lang( $value, int $sanitized_value, $expected_valid ) {
		$this->test_option( Business\Force_Lang::class, $value, $sanitized_value, $expected_valid );
	}

	/**
	 * @dataProvider language_slug_provider
	 *
	 * @param mixed         $value           The value to test.
	 * @param string        $sanitized_value Sanitized value.
	 * @param true|WP_Error $expected_valid  Validation result.
	 */
	public function test_language_slug( $value, string $sanitized_value, $expected_valid ) {
		$this->test_option( Business\Language_Slug::class, $value, $sanitized_value, $expected_valid );
	}

	/**
	 * @dataProvider object_types_provider
	 *
	 * @param mixed         $value           The value to test.
	 * @param array         $sanitized_value Sanitized value.
	 * @param true|WP_Error $expected_valid  Validation result.
	 */
	public function test_object_types( $value, array $sanitized_value, $expected_valid ) {
		$classes = array(
			Business\Language_Taxonomies::class,
			Business\Post_Types::class,
			Business\Taxonomies::class,
		);
		foreach ( $classes as $class ) {
			$this->test_option( $class, $value, $sanitized_value, $expected_valid );
		}
	}

	/**
	 * @dataProvider nav_menus_provider
	 *
	 * @param mixed         $value           The value to test.
	 * @param array         $sanitized_value Sanitized value.
	 * @param true|WP_Error $expected_valid  Validation result.
	 */
	public function test_nav_menus( $value, array $sanitized_value, $expected_valid ) {
		$this->test_option( Business\Nav_Menus::class, $value, $sanitized_value, $expected_valid );
	}

	/**
	 * @dataProvider sync_provider
	 *
	 * @param mixed         $value           The value to test.
	 * @param array         $sanitized_value Sanitized value.
	 * @param true|WP_Error $expected_valid  Validation result.
	 */
	public function test_sync( $value, array $sanitized_value, $expected_valid ) {
		$this->test_option( Business\Sync::class, $value, $sanitized_value, $expected_valid );
	}

	/**
	 * @dataProvider version_provider
	 *
	 * @param mixed         $value           The value to test.
	 * @param mixed         $sanitized_value Sanitized value.
	 * @param true|WP_Error $expected_valid  Validation result.
	 */
	public function test_version( $value, $sanitized_value, $expected_valid ) {
		$classes = array(
			Business\Previous_Version::class,
			Business\Version::class,
		);
		foreach ( $classes as $class ) {
			$this->test_option( $class, $value, $sanitized_value, $expected_valid );
		}
	}

	public function boolean_provider() {
		return array(
			'falsy string'   => array(
				'value'           => '0',
				'sanitized_value' => false,
				'expected_valid'  => true,
			),
			'falsy integer'  => array(
				'value'           => 0,
				'sanitized_value' => false,
				'expected_valid'  => true,
			),
			'false'          => array(
				'value'           => false,
				'sanitized_value' => false,
				'expected_valid'  => true,
			),
			'truthy string'  => array(
				'value'           => '1',
				'sanitized_value' => true,
				'expected_valid'  => true,
			),
			'truthy integer' => array(
				'value'           => 1,
				'sanitized_value' => true,
				'expected_valid'  => true,
			),
			'true'           => array(
				'value'           => true,
				'sanitized_value' => true,
				'expected_valid'  => true,
			),
			'wrong type'     => array(
				'value'           => 'foobar',
				'sanitized_value' => true,
				'expected_valid'  => new WP_Error( 'rest_invalid_type', '%s is not of type boolean.' ),
			),
		);
	}

	public function domains_provider() {
		return array(
			'valid'            => array(
				'value'           => array( 'en' => 'https://example.com', 'fr' => 'https://example.net', 'de' => 'https://example.org' ),
				'sanitized_value' => array( 'en' => 'https://example.com', 'fr' => 'https://example.net', 'de' => 'https://example.org' ),
				'expected_valid'  => true,
			),
			'wrong type'       => array(
				'value'           => 'foobar',
				'sanitized_value' => array(),
				'expected_valid'  => new WP_Error( 'rest_invalid_type', '%s is not of type object.' ),
			),
			'key wrong type'   => array(
				'value'           => array( 'en' => 'https://example.com', 7 => 'https://example.net', 'de' => 'https://example.org' ),
				'sanitized_value' => array( 'en' => 'https://example.com', 'de' => 'https://example.org' ),
				'expected_valid'  => new WP_Error( 'rest_additional_properties_forbidden', '7 is not a valid property of Object.' ),
			),
			'value wrong type' => array(
				'value'           => array( 'en' => 'https://example.com', 'fr' => 7, 'de' => 'https://example.org' ),
				'sanitized_value' => array( 'en' => 'https://example.com', 'fr' => 'http://7', 'de' => 'https://example.org' ),
				'expected_valid'  => new WP_Error( 'rest_invalid_type', '%s[fr] is not of type string.' ),
			),
			'invalid key'      => array(
				'value'           => array( 'en' => 'https://example.com', 'fr41' => 'https://example.net', 'de' => 'https://example.org' ),
				'sanitized_value' => array( 'en' => 'https://example.com', 'de' => 'https://example.org' ),
				'expected_valid'  => new WP_Error( 'rest_additional_properties_forbidden', 'fr41 is not a valid property of Object.' ),
			),
			'invalid value'    => array(
				'value'           => array( 'en' => 'https://example.com', 'fr' => 'foobar', 'de' => 'https://example.org' ),
				'sanitized_value' => array( 'en' => 'https://example.com', 'fr' => 'http://foobar', 'de' => 'https://example.org' ),
				'expected_valid'  => true, // This passes because there is no specific validation for `uri`. See `rest_validate_value_from_schema()`.
			),
		);
	}

	public function first_activation_provider() {
		return array(
			'string'     => array(
				'value'           => '7',
				'sanitized_value' => 7,
				'expected_valid'  => true,
			),
			'in range'   => array(
				'value'           => 7,
				'sanitized_value' => 7,
				'expected_valid'  => true,
			),
			'wrong type' => array(
				'value'           => 'foobar',
				'sanitized_value' => 0,
				'expected_valid'  => new WP_Error( 'rest_invalid_type', '%s is not of type integer.' ),
			),
			'too small'  => array(
				'value'           => -2,
				'sanitized_value' => -2,
				'expected_valid'  => new WP_Error( 'rest_out_of_bounds', '%s must be between 0 (inclusive) and 9223372036854775807 (inclusive)' ),
			),
		);
	}

	public function force_lang_provider() {
		return array(
			'in list'     => array(
				'value'           => 2,
				'sanitized_value' => 2,
				'expected_valid'  => true,
			),
			'not in list' => array(
				'value'           => 8,
				'sanitized_value' => 8,
				'expected_valid'  => new WP_Error( 'rest_not_in_enum', '%s is not one of 0, 1, 2, and 3.' ),
			),
			'wrong type'  => array(
				'value'           => '3',
				'sanitized_value' => 3,
				'expected_valid'  => true,
			),
		);
	}

	public function language_slug_provider() {
		return array(
			'valid'      => array(
				'value'           => 'fr',
				'sanitized_value' => 'fr',
				'expected_valid'  => true,
			),
			'wrong type' => array(
				'value'           => 8,
				'sanitized_value' => '8',
				'expected_valid'  => new WP_Error( 'rest_invalid_type', '%s is not of type string.' ),
			),
			'invalid'    => array(
				'value'           => 'fr41',
				'sanitized_value' => 'fr41',
				'expected_valid'  => new WP_Error( 'rest_invalid_pattern', '%s does not match pattern ^[a-z_-]+$.' ),
			),
		);
	}

	public function object_types_provider() {
		return array(
			'array'       => array(
				'value'           => array( 'foobar_language' ),
				'sanitized_value' => array( 'foobar_language' ),
				'expected_valid'  => true,
			),
			'object'      => array(
				'value'           => array( 'foobar_language' => 'foobar_language' ),
				'sanitized_value' => array( 'foobar_language' ),
				'expected_valid'  => new WP_Error( 'rest_invalid_type', '%s is not of type array.' ),
			),
			'wrong type'  => array(
				'value'           => array( 'foobar_language', 8 ),
				'sanitized_value' => array( 'foobar_language', '8' ),
				'expected_valid'  => new WP_Error( 'rest_invalid_type', '%s[1] is not of type string.' ),
			),
		);
	}

	public function nav_menus_provider() {
		return array(
			'valid'                       => array(
				'value'           => array(
					'twentyfoobar' => array(
						'en' => 7,
						'fr' => 4,
					),
					'twentybarbaz' => array(
						'fr' => 12,
						'de' => 27,
					),
				),
				'sanitized_value' => array(
					'twentyfoobar' => array(
						'en' => 7,
						'fr' => 4,
					),
					'twentybarbaz' => array(
						'fr' => 12,
						'de' => 27,
					),
				),
				'expected_valid'  => true,
			),
			'wrong type'                  => array(
				'value'           => 8,
				'sanitized_value' => array(),
				'expected_valid'  => new WP_Error( 'rest_invalid_type', '%s is not of type object.' ),
			),
			'invalid theme'               => array(
				'value'           => array(
					'' => array(
						'en' => 7,
						'fr' => 4,
					),
				),
				'sanitized_value' => array(),
				'expected_valid'  => new WP_Error( 'rest_additional_properties_forbidden', ' is not a valid property of Object.' ),
			),
			'theme wrong type'            => array(
				'value'           => array(
					8 => array(
						'en' => 7,
						'fr' => 4,
					),
				),
				'sanitized_value' => array(
					8 => array(
						'en' => 7,
						'fr' => 4,
					),
				),
				'expected_valid'  => true, // Passes because php casts `8` as a string automatically, thanks to type Juggling.
			),
			'list of post IDs wrong type' => array(
				'value'           => array(
					'twentyfoobar' => 'test',
				),
				'sanitized_value' => array(
					'twentyfoobar' => array(),
				),
				'expected_valid'  => new WP_Error( 'rest_invalid_type', '%s[twentyfoobar] is not of type object.' ),
			),
			'invalid locale'              => array(
				'value'           => array(
					'twentyfoobar' => array(
						'en' => 7,
						''   => 4,
					),
				),
				'sanitized_value' => array(
					'twentyfoobar' => array(
						'en' => 7,
					),
				),
				'expected_valid'  => new WP_Error( 'rest_additional_properties_forbidden', ' is not a valid property of Object.' ),
			),
			'invalid post ID'             => array(
				'value'           => array(
					'twentyfoobar' => array(
						'en' => 0,
						'fr' => -4,
					),
				),
				'sanitized_value' => array(
					'twentyfoobar' => array(
						'en' => 0,
						'fr' => -4,
					),
				),
				'expected_valid'  => new WP_Error( 'rest_out_of_bounds', '%s[twentyfoobar][fr] must be greater than or equal to 0' ),
			),
			'post ID string'              => array(
				'value'           => array(
					'twentyfoobar' => array(
						'en' => 7,
						'fr' => '4',
					),
				),
				'sanitized_value' => array(
					'twentyfoobar' => array(
						'en' => 7,
						'fr' => 4,
					),
				),
				'expected_valid'  => true,
			),
			'post ID wrong type'          => array(
				'value'           => array(
					'twentyfoobar' => array(
						'en' => 7,
						'fr' => array(),
					),
				),
				'sanitized_value' => array(
					'twentyfoobar' => array(
						'en' => 7,
						'fr' => 0,
					),
				),
				'expected_valid'  => new WP_Error( 'rest_invalid_type', '%s[twentyfoobar][fr] is not of type integer.' ),
			),
		);
	}

	public function sync_provider() {
		return array(
			'valid'        => array(
				'value'           => array( 'taxonomies', 'post_meta', 'comment_status' ),
				'sanitized_value' => array( 'taxonomies', 'post_meta', 'comment_status' ),
				'expected_valid'  => true,
			),
			'valid empty'  => array(
				'value'           => array(),
				'sanitized_value' => array(),
				'expected_valid'  => true,
			),
			'valid string' => array(
				'value'           => 'taxonomies',
				'sanitized_value' => array( 'taxonomies' ),
				'expected_valid'  => true,
			),
			'wrong type'   => array(
				'value'           => 8,
				'sanitized_value' => array( '8' ),
				'expected_valid'  => new WP_Error( 'rest_not_in_enum', '%s[0] is not one of taxonomies, post_meta, comment_status, ping_status, sticky_posts, post_date, post_format, post_parent, _wp_page_template, menu_order, and _thumbnail_id.' ),
			),
			'invalid'      => array(
				'value'           => array( 'foo' ),
				'sanitized_value' => array( 'foo' ),
				'expected_valid'  => new WP_Error( 'rest_not_in_enum', '%s[0] is not one of taxonomies, post_meta, comment_status, ping_status, sticky_posts, post_date, post_format, post_parent, _wp_page_template, menu_order, and _thumbnail_id.' ),
			),
		);
	}

	public function version_provider() {
		return array(
			'valid'      => array(
				'value'           => '1.2.3',
				'sanitized_value' => '1.2.3',
				'expected_valid'  => true,
			),
			'wrong type' => array(
				'value'           => 3,
				'sanitized_value' => '3',
				'expected_valid'  => new WP_Error( 'rest_invalid_type', '%s is not of type string.' ),
			),
		);
	}

	/**
	 * @param string        $class           Option class.
	 * @param mixed         $value           The value to test.
	 * @param mixed         $sanitized_value Sanitized value.
	 * @param true|WP_Error $expected_valid  Validation result.
	 */
	private function test_option( string $class, $value, $sanitized_value, $expected_valid ) {
		$option = new $class( $value );

		$this->assertSame( $sanitized_value, $option->get() );

		$valid = rest_validate_value_from_schema( $value, $option->get_schema(), $option->key() );

		if ( is_wp_error( $expected_valid ) ) {
			$this->assertInstanceOf( WP_Error::class, $valid );
			$this->assertSame( sprintf( $expected_valid->get_error_message(), $option::key() ), $valid->get_error_message() );
		} else {
			$this->assertSame( $expected_valid, $valid );
		}
	}
}
