<?php
/**
 * Tests for PLL_Language and PLL_Language_Deprecated.
 */
class Language_Test extends PLL_UnitTestCase {

	/**
	 * @var PLL_Language
	 */
	private $language;

	/**
	 * @var array
	 */
	private $data;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );
		self::create_language( 'en_US' );
	}

	public function set_up() {
		parent::set_up();

		$this->language = self::$model->get_default_language();
		$this->assertInstanceOf( PLL_Language::class, $this->language );
		$this->data = $this->language->to_array();

		add_action( 'deprecated_property_trigger_error', '__return_false' );
	}

	/**
	 * PLL_Language::get_prop()
	 */
	public function test_get_prop() {
		// Test deprecated properties.
		foreach ( PLL_Language::DEPRECATED_TERM_PROPERTIES as $prop => $args ) {
			$this->assertArrayHasKey( $args[0], $this->data['term_props'] );
			$this->assertArrayHasKey( $args[1], $this->data['term_props'][ $args[0] ] );
			$this->assertSame( $this->data['term_props'][ $args[0] ][ $args[1] ], $this->language->get_prop( $prop ) );
		}
		foreach ( PLL_Language::DEPRECATED_URL_PROPERTIES as $prop => $callback ) {
			$this->assertSame( $this->data[ $prop ], $this->language->get_prop( $prop ) );
		}

		// Test composite properties.
		foreach ( array( 'language', 'term_language' ) as $tax ) {
			foreach ( array( 'term_id', 'term_taxonomy_id', 'count' ) as $field ) {
				$this->assertArrayHasKey( $tax, $this->data['term_props'] );
				$this->assertArrayHasKey( $field, $this->data['term_props'][ $tax ] );
				$this->assertSame( $this->data['term_props'][ $tax ][ $field ], $this->language->get_prop( "$tax:$field" ) );
			}
		}

		// Test a public property.
		$this->assertSame( $this->data['flag_code'], $this->language->get_prop( 'flag_code' ) );

		// Test unknown property.
		$this->assertFalse( $this->language->get_prop( 'foobar' ) );
	}

	/**
	 * PLL_Language::__get()
	 */
	public function test___get() {
		// Test deprecated properties.
		$count = did_filter( 'deprecated_property_trigger_error' );

		foreach ( PLL_Language::DEPRECATED_TERM_PROPERTIES as $prop => $args ) {
			$this->assertArrayHasKey( $args[0], $this->data['term_props'] );
			$this->assertArrayHasKey( $args[1], $this->data['term_props'][ $args[0] ] );
			$this->assertSame( $this->data['term_props'][ $args[0] ][ $args[1] ], $this->language->$prop );
		}
		foreach ( PLL_Language::DEPRECATED_URL_PROPERTIES as $prop => $callback ) {
			$this->assertSame( $this->data[ $prop ], $this->language->$prop );
		}

		$expected_count = $count + count( PLL_Language::DEPRECATED_TERM_PROPERTIES ) + count( PLL_Language::DEPRECATED_URL_PROPERTIES );
		$this->assertSame( $expected_count, did_filter( 'deprecated_property_trigger_error' ) );

		// Test unknown property.
		$this->assertNull( $this->language->foobar );

		// Test a public property.
		$this->assertSame( $this->data['flag_code'], $this->language->flag_code );
	}

	/**
	 * PLL_Language::__isset()
	 */
	public function test___isset() {
		// Test deprecated properties.
		foreach ( PLL_Language::DEPRECATED_TERM_PROPERTIES as $prop => $args ) {
			$this->assertTrue( isset( $this->language->$prop ) );
		}
		foreach ( PLL_Language::DEPRECATED_URL_PROPERTIES as $prop => $callback ) {
			$this->assertTrue( isset( $this->language->$prop ) );
		}

		// Test unknown property.
		$this->assertFalse( isset( $this->language->foobar ) );

		// Test a public property.
		$this->assertTrue( isset( $this->language->flag_code ) );
	}

	/**
	 * PLL_Language::get_tax_prop()
	 */
	public function test_get_tax_prop() {
		foreach ( $this->data['term_props'] as $tax => $fields ) {
			foreach ( $fields as $field => $value ) {
				$this->assertSame( $value, $this->language->get_tax_prop( $tax, $field ) );
			}
		}

		$this->assertSame( 0, $this->language->get_tax_prop( 'foobar', 'term_taxonomy_id' ) );
		$this->assertSame( 0, $this->language->get_tax_prop( 'language', 'foobar' ) );
	}

	/**
	 * PLL_Language::get_tax_props()
	 */
	public function test_get_tax_props() {
		$expected = array();

		foreach ( $this->data['term_props'] as $tax => $fields ) {
			$expected[ $tax ] = $fields['term_taxonomy_id'];
		}

		$this->assertSame( $expected, $this->language->get_tax_props( 'term_taxonomy_id' ) );
		$this->assertSame( $this->data['term_props'], $this->language->get_tax_props() );
	}
}
