<?php

namespace WP_Syntex\Polylang\Tests\Integration\Options\Abstract_Option;

use WP_Error;
use PHPUnit_Adapter_TestCase;
use WP_Syntex\Polylang\Options\Business;

/**
 * Test the schema of all classes extending {@see WP_Syntex\Polylang\Options\Abstract_Option}.
 */
class GetSchema_Test extends PHPUnit_Adapter_TestCase {
	/**
	 * @dataProvider boolean_provider
	 *
	 * @param mixed       $value           The value to test.
	 * @param bool        $sanitized_value Sanitized value.
	 * @param true|string $expected_valid  Validation result.
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
	 * @param mixed       $value           The value to test.
	 * @param array       $sanitized_value Sanitized value.
	 * @param true|string $expected_valid  Validation result.
	 */
	public function test_domains( $value, array $sanitized_value, $expected_valid ) {
		$this->test_option( Business\Domains::class, $value, $sanitized_value, $expected_valid );
	}

	/**
	 * @dataProvider first_activation_provider
	 *
	 * @param mixed       $value           The value to test.
	 * @param int         $sanitized_value Sanitized value.
	 * @param true|string $expected_valid  Validation result.
	 */
	public function test_first_activation( $value, int $sanitized_value, $expected_valid ) {
		$this->test_option( Business\First_Activation::class, $value, $sanitized_value, $expected_valid );
	}

	/**
	 * @dataProvider force_lang_provider
	 *
	 * @param mixed       $value           The value to test.
	 * @param int         $sanitized_value Sanitized value.
	 * @param true|string $expected_valid  Validation result.
	 * @param string      $show_0          Tells if the choice `0` (i.e. "Language set from content") is available, accepts `'yes'` or `'no'`.
	 */
	public function test_force_lang( $value, int $sanitized_value, $expected_valid, $show_0 = 'no' ) {
		update_option( 'pll_language_from_content_available', $show_0 );
		$this->test_option( Business\Force_Lang::class, $value, $sanitized_value, $expected_valid );
	}

	/**
	 * @dataProvider default_lang_provider
	 *
	 * @param mixed       $value           The value to test.
	 * @param string      $sanitized_value Sanitized value.
	 * @param true|string $expected_valid  Validation result.
	 */
	public function test_default_lang( $value, string $sanitized_value, $expected_valid ) {
		$this->test_option( Business\Default_Lang::class, $value, $sanitized_value, $expected_valid );
	}

	/**
	 * @dataProvider object_types_provider
	 *
	 * @param mixed       $value           The value to test.
	 * @param array       $sanitized_value Sanitized value.
	 * @param true|string $expected_valid  Validation result.
	 */
	public function test_object_types( $value, array $sanitized_value, $expected_valid ) {
		$classes = array(
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
	 * @param mixed       $value           The value to test.
	 * @param array       $sanitized_value Sanitized value.
	 * @param true|string $expected_valid  Validation result.
	 */
	public function test_nav_menus( $value, array $sanitized_value, $expected_valid ) {
		$this->test_option( Business\Nav_Menus::class, $value, $sanitized_value, $expected_valid );
	}

	/**
	 * @dataProvider sync_provider
	 *
	 * @param mixed       $value           The value to test.
	 * @param array       $sanitized_value Sanitized value.
	 * @param true|string $expected_valid  Validation result.
	 */
	public function test_sync( $value, array $sanitized_value, $expected_valid ) {
		$this->test_option( Business\Sync::class, $value, $sanitized_value, $expected_valid );
	}

	/**
	 * @dataProvider version_provider
	 *
	 * @param mixed       $value           The value to test.
	 * @param mixed       $sanitized_value Sanitized value.
	 * @param true|string $expected_valid  Validation result.
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
				'expected_valid'  => 'rest_invalid_type',
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
				'expected_valid'  => 'rest_invalid_type',
			),
			'key wrong type'   => array(
				'value'           => array( 'en' => 'https://example.com', 7 => 'https://example.net', 'de' => 'https://example.org' ),
				'sanitized_value' => array( 'en' => 'https://example.com', 'de' => 'https://example.org' ),
				'expected_valid'  => 'rest_additional_properties_forbidden',
			),
			'value wrong type' => array(
				'value'           => array( 'en' => 'https://example.com', 'fr' => 7, 'de' => 'https://example.org' ),
				'sanitized_value' => array( 'en' => 'https://example.com', 'fr' => 'http://7', 'de' => 'https://example.org' ),
				'expected_valid'  => 'rest_invalid_type',
			),
			'invalid key'      => array(
				'value'           => array( 'en' => 'https://example.com', 'fr_FR' => 'https://example.net', 'de' => 'https://example.org' ),
				'sanitized_value' => array( 'en' => 'https://example.com', 'de' => 'https://example.org' ),
				'expected_valid'  => 'rest_additional_properties_forbidden',
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
				'expected_valid'  => 'rest_invalid_type',
			),
			'too small'  => array(
				'value'           => -2,
				'sanitized_value' => -2,
				'expected_valid'  => 'rest_out_of_bounds',
			),
		);
	}

	public function force_lang_provider() {
		return array(
			'0 displayed'    => array(
				'value'           => 0,
				'sanitized_value' => 0,
				'expected_valid'  => true,
				'show_0'          => 'yes',
			),
			'0 hidden'     => array(
				'value'           => 0,
				'sanitized_value' => 0,
				'expected_valid'  => 'rest_not_in_enum',
				'show_0'          => 'no',
			),
			'in list'     => array(
				'value'           => 2,
				'sanitized_value' => 2,
				'expected_valid'  => true,
			),
			'not in list' => array(
				'value'           => 8,
				'sanitized_value' => 8,
				'expected_valid'  => 'rest_not_in_enum',
			),
			'wrong type'  => array(
				'value'           => '3',
				'sanitized_value' => 3,
				'expected_valid'  => true,
			),
		);
	}

	public function default_lang_provider() {
		return array(
			'valid'      => array(
				'value'           => 'fr',
				'sanitized_value' => 'fr',
				'expected_valid'  => true,
			),
			'wrong type' => array(
				'value'           => 8,
				'sanitized_value' => '8',
				'expected_valid'  => 'rest_invalid_type',
			),
			'invalid'    => array(
				'value'           => 'fr_FR',
				'sanitized_value' => 'fr_FR',
				'expected_valid'  => 'rest_invalid_pattern',
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
				'expected_valid'  => 'rest_invalid_type',
			),
			'wrong type'  => array(
				'value'           => array( 'foobar_language', 8 ),
				'sanitized_value' => array( 'foobar_language', '8' ),
				'expected_valid'  => 'rest_invalid_type',
			),
		);
	}

	public function nav_menus_provider() {
		return array(
			'valid'                       => array(
				'value'           => array(
					'twentyfoobar' => array(
						'primary' => array(
							'en' => 7,
							'fr' => 4,
						),
					),
					'twentybarbaz' => array(
						'primary' => array(
							'fr' => 12,
							'de' => 27,
						),
					),
				),
				'sanitized_value' => array(
					'twentyfoobar' => array(
						'primary' => array(
							'en' => 7,
							'fr' => 4,
						),
					),
					'twentybarbaz' => array(
						'primary' => array(
							'fr' => 12,
							'de' => 27,
						),
					),
				),
				'expected_valid'  => true,
			),
			'wrong type'                  => array(
				'value'           => 8,
				'sanitized_value' => array(),
				'expected_valid'  => 'rest_invalid_type',
			),
			'invalid theme'               => array(
				'value'           => array(
					'' => array(
						'primary' => array(
							'en' => 7,
							'fr' => 4,
						),
					),
				),
				'sanitized_value' => array(),
				'expected_valid'  => 'rest_additional_properties_forbidden',
			),
			'theme wrong type'            => array(
				'value'           => array(
					8 => array(
						'primary' => array(
							'en' => 7,
							'fr' => 4,
						),
					),
				),
				'sanitized_value' => array(
					8 => array(
						'primary' => array(
							'en' => 7,
							'fr' => 4,
						),
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
				'expected_valid'  => 'rest_invalid_type',
			),
			'invalid locale'              => array(
				'value'           => array(
					'twentyfoobar' => array(
						'primary' => array(
							'en' => 7,
							''   => 4,
						),
					),
				),
				'sanitized_value' => array(
					'twentyfoobar' => array(
						'primary' => array(
							'en' => 7,
						),
					),
				),
				'expected_valid'  => 'rest_additional_properties_forbidden',
			),
			'invalid post ID'             => array(
				'value'           => array(
					'twentyfoobar' => array(
						'primary' => array(
							'en' => 0,
							'fr' => -4,
						),
					),
				),
				'sanitized_value' => array(
					'twentyfoobar' => array(
						'primary' => array(
							'en' => 0,
							'fr' => -4,
						),
					),
				),
				'expected_valid'  => 'rest_out_of_bounds',
			),
			'post ID string'              => array(
				'value'           => array(
					'twentyfoobar' => array(
						'primary' => array(
							'en' => 7,
							'fr' => '4',
						),
					),
				),
				'sanitized_value' => array(
					'twentyfoobar' => array(
						'primary' => array(
							'en' => 7,
							'fr' => 4,
						),
					),
				),
				'expected_valid'  => true,
			),
			'post ID wrong type'          => array(
				'value'           => array(
					'twentyfoobar' => array(
						'primary' => array(
							'en' => 7,
							'fr' => array(),
						),
					),
				),
				'sanitized_value' => array(
					'twentyfoobar' => array(
						'primary' => array(
							'en' => 7,
							'fr' => 0,
						),
					),
				),
				'expected_valid'  => 'rest_invalid_type',
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
				'expected_valid'  => 'rest_not_in_enum',
			),
			'invalid'      => array(
				'value'           => array( 'foo' ),
				'sanitized_value' => array( 'foo' ),
				'expected_valid'  => 'rest_not_in_enum',
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
				'expected_valid'  => 'rest_invalid_type',
			),
		);
	}

	/**
	 * @param string      $class           Option class.
	 * @param mixed       $value           The value to test.
	 * @param mixed       $sanitized_value Sanitized value.
	 * @param true|string $expected_valid  Validation result.
	 */
	private function test_option( string $class, $value, $sanitized_value, $expected_valid ) {
		$option = new $class( $value );

		$this->assertSame( $sanitized_value, $option->get() );

		$valid = rest_validate_value_from_schema( $value, $option->get_schema(), $option::key() );

		if ( is_string( $expected_valid ) ) {
			$this->assertInstanceOf( WP_Error::class, $valid );
			$this->assertSame( $expected_valid, $valid->get_error_code() );
		} else {
			$this->assertTrue( $valid );
		}
	}
}
