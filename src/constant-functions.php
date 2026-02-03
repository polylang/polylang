<?php
/**
 * @package Polylang
 *
 * The aim of these functions is to be stubed in unit tests.
 *
 * /!\ THE CODE IN THIS FILE MUST BE COMPATIBLE WITH PHP 5.6.
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
function pll_has_constant( $constant_name ) {
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
 * @phpstan-template D of int|float|string|bool|array|null
 * @phpstan-param non-falsy-string $constant_name
 * @phpstan-param D $default
 * @phpstan-return D
 */
function pll_get_constant( $constant_name, $default = null ) {
	if ( ! pll_has_constant( $constant_name ) ) {
		return $default;
	}

	/** @phpstan-var D $return */
	$return = constant( $constant_name );
	return $return;
}

/**
 * Defines a constant if it is not already defined.
 *
 * @since 3.5
 *
 * @param string $constant_name Name of the constant.
 * @param mixed  $value         Value to set.
 * @return bool True on success, false on failure or already defined.
 *
 * @phpstan-param non-falsy-string $constant_name
 * @phpstan-param int|float|string|bool|array|null $value
 */
function pll_set_constant( $constant_name, $value ) {
	if ( pll_has_constant( $constant_name ) ) {
		return false;
	}

	return define( $constant_name, $value ); // phpcs:ignore WordPressVIPMinimum.Constants.ConstantString.NotCheckingConstantName
}
