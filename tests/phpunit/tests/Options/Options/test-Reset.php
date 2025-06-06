<?php

namespace WP_Syntex\Polylang\Tests\Integration\Options\Options;

use PLL_UnitTestCase;

/**
 * Test the reset method of {@see WP_Syntex\Polylang\Options\Options}.
 */
class Reset_Test extends PLL_UnitTestCase {
	/**
	 * Makes sure that `reset()` does its job and saves it.
	 *
	 * @return void
	 */
	public function test_save_after_reset() {
		$options = self::create_options();

		$this->assertSame( array(), $options->get( 'sync' ), 'The initial value should be an empty array.' );

		$options->set( 'sync', array( 'taxonomies' ) );

		$this->assertSame( array( 'taxonomies' ), $options->get( 'sync' ), 'The value should be set.' );
		$this->assertSame( true, $options->save(), 'The value should be saved.' );

		$options->reset( 'sync' );

		$this->assertSame( true, $options->save(), 'The value should be saved after reset.' );
	}
}
