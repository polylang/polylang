<?php

/**
 * Test options.
 */
class Options_Test extends PLL_UnitTestCase {

	public function test_save_all() {
		$options = self::create_options(
			array(
				'default_lang' => 'en',
				'force_lang'   => 1,
			)
		);

		// Test the value in DB.
		$this->assert_option_in_db( 'force_lang', 1 );

		// Test the value in object.
		$this->assertSame( 1, $options['force_lang'] );

		// Change the value.
		$options['force_lang'] = 2;

		// Test that the value in DB hasn't changed.
		$this->assert_option_in_db( 'force_lang', 1 );

		// Test that the value in object has changed.
		$this->assertSame( 2, $options['force_lang'] );

		// Save in DB.
		$options->save_all();

		// Test that the value in DB has changed.
		$this->assert_option_in_db( 'force_lang', 2 );
	}

	public function test_iterator() {
		$options = self::create_options();

		$this->assertIsIterable( $options );
		$this->assertSameSetsWithIndex( $options->get_all(), iterator_to_array( $options->getIterator() ) );
	}

	private function assert_option_in_db( string $key, $value ): void {
		$raw_options = get_option( 'polylang' );
		$this->assertIsArray( $raw_options );
		$this->assertArrayHasKey( $key, $raw_options );
		$this->assertSame( $value, $raw_options[ $key ] );
	}
}
