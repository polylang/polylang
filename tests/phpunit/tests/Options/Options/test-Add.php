<?php

namespace WP_Syntex\Polylang\Tests\Integration\Options\Options;

use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
use WP_Syntex\Polylang\Options\Options;

class Add_Test extends PLL_UnitTestCase {

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
	}

	public function set_up() {
		parent::set_up();

		register_post_type( 'sauron' );
		register_post_type( 'saruman' );
		register_post_type( 'gandalf' );
	}

	public function tear_down() {
		unregister_post_type( 'sauron' );
		unregister_post_type( 'saruman' );
		unregister_post_type( 'gandalf' );

		parent::tear_down();
	}

	public function test_add_list() {
		$options = self::create_options(
			array(
				'post_types' => array( 'gandalf', 'saruman' ),
			)
		);

		$result = $options->add( 'post_types', 'sauron' );

		$this->assertWPError( $result );
		$this->assertFalse( $result->has_errors() );
		$this->assertSame( array( 'gandalf', 'saruman', 'sauron' ), $options->get( 'post_types' ), 'Sauron should have been added.' );
	}

	public function test_add_map() {
		$options = self::create_options(
			array(
				'domains' => array(
					'en' => 'https://example.com',
				),
			)
		);

		$result = $options->add(
			'domains',
			array(
				'fr' => 'https://example.fr',
			)
		);

		$this->assertWPError( $result );
		$this->assertFalse( $result->has_errors() );
		$this->assertSame( array( 'en' => 'https://example.com', 'fr' => 'https://example.fr' ), $options->get( 'domains' ), 'French domain should have been added.' );
	}

	public function test_add_unknown_option() {
		$options = self::create_options();

		$result = $options->add( 'unknown_option', 'value' );

		$this->assertWPError( $result );
		$this->assertTrue( $result->has_errors() );
	}

	public function test_add_invalid_option_type() {
		$options = self::create_options(
			array(
				'media_support' => true,
			)
		);

		$result = $options->add( 'media_support', 'duh' );

		$this->assertWPError( $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertSame( 'pll_invalid_option_type', $result->get_error_code() );
	}

	public function test_add_invalid_value_type() {
		$options = self::create_options(
			array(
				'post_types' => array( 'gandalf', 'saruman' ),
			)
		);

		$result = $options->add( 'post_types', 'gollum' );

		$this->assertWPError( $result );
		$this->assertSame( array( 'gandalf', 'saruman' ), $options->get( 'post_types' ), 'Gollum should not have been added.' );
	}

	public function test_add_invalid_value_type_map() {
		$options = self::create_options(
			array(
				'domains' => array( 'en' => 'https://example.com' ),
			)
		);

		$result = $options->add( 'domains', 'smeagol' );

		$this->assertWPError( $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertSame( 'pll_invalid_option_type', $result->get_error_code() );
	}

	public function test_add_invalid_subvalue_type_map() {
		$options = self::create_options(
			array(
				'domains' => array( 'en' => 'https://example.com' ),
			)
		);

		$result = $options->add( 'domains', 'en', 7 );

		$this->assertWPError( $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertSame( 'pll_invalid_option_type', $result->get_error_code() );
	}

	public function test_save_after_add_to_list() {
		$options = self::create_options();

		$options->add( 'post_types', 'sauron' );

		$this->assertTrue( $options->save(), 'The options should be saved.' );

		$options = new Options(); // Reload the options to check if the value is persisted.

		$this->assertSame( array( 'sauron' ), $options->get( 'post_types' ), 'The value should be persisted.' );
	}

	public function test_save_after_add_to_map() {
		$options = self::create_options();

		$options->add(
			'domains',
			array(
				'en' => 'https://example.com',
			)
		);

		$this->assertTrue( $options->save(), 'The options should be saved.' );

		$options = new Options(); // Reload the options to check if the value is persisted.

		$this->assertSame( array( 'en' => 'https://example.com', 'fr' => '' ), $options->get( 'domains' ), 'English domain should be persisted, French domain should be set to default value.' );
	}

	public function test_add_should_override_existing_value_in_map() {
		$options = self::create_options(
			array(
				'domains' => array( 'en' => 'https://example.com', 'fr' => 'https://example.fr' ),
			)
		);

		$result = $options->add(
			'domains',
			array(
				'en' => 'https://do-no-click-me.com',
			)
		);

		$this->assertWPError( $result );
		$this->assertFalse( $result->has_errors() );
		$this->assertSame( array( 'en' => 'https://do-no-click-me.com', 'fr' => 'https://example.fr' ), $options->get( 'domains' ), 'English domain should have been overridden.' );

		$this->assertTrue( $options->save(), 'The options should be saved.' );

		$options = new Options(); // Reload the options to check if the value is persisted.

		$this->assertSame( array( 'en' => 'https://do-no-click-me.com', 'fr' => 'https://example.fr' ), $options->get( 'domains' ), 'English domain should have been overridden.' );
	}
}
