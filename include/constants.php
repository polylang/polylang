<?php
/**
 * Functions to work with constants.
 * These functions allow mocking constants in tests.
 *
 * @package Polylang
 */

/**
 * Tells if a constant is defined.
 *
 * @since 3.5
 *
 * @param string $constant_name Name of the constant.
 * @return bool True if the constant is defined, false otherwise.
 *
 * @phpstan-param non-falsy-string $constant_name
 */
function pll_has_constant( string $constant_name ): bool {
	return defined( $constant_name ); // phpcs:ignore WordPressVIPMinimum.Constants.ConstantString.NotCheckingConstantName
}

/**
 * Returns the value of a constant if it is defined.
 *
 * @since 3.5
 *
 * @param string $constant_name Name of the constant.
 * @param mixed  $default       Optional. Value to return if the constant is not defined. Defaults to `null`.
 * @return mixed The value of the constant.
 *
 * @phpstan-param non-falsy-string $constant_name
 */
function pll_get_constant( string $constant_name, $default = null ) {
	if ( ! pll_has_constant( $constant_name ) ) {
		return $default;
	}

	return constant( $constant_name );
}
