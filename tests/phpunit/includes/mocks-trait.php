<?php

use Brain\Monkey\Functions;

/**
 * Trait containing commonly used mocks.
 */
trait PLL_Mocks_Trait {

	/**
	 * Mocks constants (`pll_get_constant()` and `pll_has_constant()`).
	 * Note: `pll_set_constant()` is not mocked.
	 *
	 * @param array $constants Array keys are constant names, array values are constant values (use `null` to tell
	 *                         a constant is not defined).
	 * @return void
	 */
	private function mock_constants( array $constants ) {
		Functions\when( 'pll_get_constant' )->alias(
			function ( $constant_name, $default = null ) use ( $constants ) {
				if ( array_key_exists( $constant_name, $constants ) ) {
					return null !== $constants[ $constant_name ] ? $constants[ $constant_name ] : $default;
				}

				return defined( $constant_name ) ? constant( $constant_name ) : $default;
			}
		);

		Functions\when( 'pll_has_constant' )->alias(
			function ( $constant_name ) use ( $constants ) {
				if ( array_key_exists( $constant_name, $constants ) ) {
					return null !== $constants[ $constant_name ];
				}

				return defined( $constant_name );
			}
		);
	}
}
