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

		$this->assert_option_is_persisted( 'force_lang', 1, 'The value in DB should be right from the start.' );

		$this->assertSame( 1, $options['force_lang'], 'The value in object should be right from the start.' );

		// Change the value.
		$options['force_lang'] = 2;

		$this->assert_option_is_persisted( 'force_lang', 1, 'The value in DB should not have changed.' );

		$this->assertSame( 2, $options['force_lang'], 'The value inobject should have changed.' );

		// Save in DB.
		$options->save_all();

		$this->assert_option_is_persisted( 'force_lang', 2, 'The value in DB should have changed.' );
	}

	public function test_iterator() {
		$options = self::create_options();

		$this->assertIsIterable( $options );
		$this->assertSameSetsWithIndex( $options->get_all(), iterator_to_array( $options->getIterator() ) );
	}

	private function assert_option_is_persisted( string $key, $value, string $message ): void {
		$raw_options = get_option( 'polylang' );
		$this->assertIsArray( $raw_options );
		$this->assertArrayHasKey( $key, $raw_options );
		$this->assertSame( $value, $raw_options[ $key ], $message );
	}
}
