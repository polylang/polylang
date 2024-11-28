<?php

namespace WP_Syntex\Polylang\Tests\Integration\Options\Options;

use PLL_UnitTestCase;

/**
 * Tests for `Options\Options->getIterator()`.
 *
 * @group options
 */
class GetIterator_Test extends PLL_UnitTestCase {

	public function test_should_return_all_options() {
		$options = self::create_options();

		$this->assertIsIterable( $options );
		$this->assertSameSetsWithIndex( $options->get_all(), iterator_to_array( $options->getIterator() ) );
	}
}
