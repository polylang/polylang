<?php

namespace WP_Syntex\Polylang\Tests\Integration\Options\Options;

use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
use WP_Syntex\Polylang\Options\Options;

class Remove_Test extends PLL_UnitTestCase {

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
	}

	public function test_remove_list() {
		$options = self::create_options(
			array(
				'post_types' => array(
					'gandalf',
					'saruman',
				),
			)
		);

		$result = $options->remove( 'post_types', 'saruman' );

		$this->assertWPError( $result );
		$this->assertFalse( $result->has_errors() );
		$this->assertSame( array( 'gandalf' ), $options->get( 'post_types' ), 'Saruman should have been removed.' );
	}

	public function test_remove_map() {
		$options = self::create_options(
			array(
				'domains' => array(
					'en' => 'https://example.com',
					'fr' => 'https://example.fr',
				),
			)
		);

		$result = $options->remove( 'domains', 'fr' );

		$this->assertWPError( $result );
		$this->assertFalse( $result->has_errors() );
		$this->assertSame(
			array(
				'en' => 'https://example.com',
				'fr' => '',
			),
			$options->get( 'domains' ),
			'The french domain should have been removed.',
		);
	}

	public function test_remove_unknown_option() {
		$options = self::create_options();

		$result = $options->remove( 'unknown_option', 'value' );

		$this->assertWPError( $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertSame( 'pll_unknown_option_key', $result->get_error_code() );
	}

	public function test_remove_invalid_option_type() {
		$options = self::create_options(
			array(
				'media_support' => true,
			)
		);

		$result = $options->remove( 'media_support', 'duh' );

		$this->assertWPError( $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertSame( 'pll_invalid_option_type', $result->get_error_code() );
	}

	/**
	 * @testWith ["domains", "foo"]
	 *           ["post_types", "bar"]
	 *
	 * @param string $key   The key of the option to remove.
	 * @param string $value The value to remove.
	 * @return void
	 */
	public function test_remove_failed( string $key, string $value ) {
		$options = self::create_options(
			array(
				'domains' => array(
					'en' => 'https://example.com',
					'fr' => 'https://example.fr',
				),
				'post_types' => array(
					'gandalf',
					'saruman',
				),
			)
		);

		$result = $options->remove( $key, $value );

		$this->assertWPError( $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertSame( 'pll_remove_failed', $result->get_error_code() );
	}

	public function test_save_after_remove_from_list() {
		$options = self::create_options(
			array(
				'post_types' => array( 'gandalf', 'saruman' ),
			)
		);

		$options->remove( 'post_types', 'saruman' );

		$this->assertTrue( $options->save(), 'The options should be saved.' );

		$options = new Options(); // Reload the options to check if the value is persisted.

		$this->assertSame( array( 'gandalf' ), $options->get( 'post_types' ), 'The value should be persisted.' );
	}

	public function test_save_after_remove_from_map() {
		$options = self::create_options(
			array(
				'domains' => array( 'en' => 'https://example.com', 'fr' => 'https://example.fr' ),
			)
		);

		$options->remove( 'domains', 'fr' );

		$this->assertTrue( $options->save(), 'The options should be saved.' );

		$options = new Options(); // Reload the options to check if the value is persisted.

		$this->assertSame( array( 'en' => 'https://example.com', 'fr' => '' ), $options->get( 'domains' ), 'English domain should remain, French domain should be set to default value.' );
	}
}
