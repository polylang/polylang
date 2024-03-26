<?php
/**
 * @package Polylang
 */

/**
 * Class to manage single boolean option.
 * Note that for historic reason, boolean are stored as 0 or 1.
 *
 * @since 3.7
 */
class PLL_Boolean_Option extends PLL_Abstract_Option {
	/**
	 * Validates option's value,
	 * only boolean or 0 and 1 accepted (int or string).
	 *
	 * @since 3.7
	 *
	 * @param mixed $value Value to validate.
	 * @return bool True if the value is valid, false otherwise.
	 */
	protected function validate( $value ): bool {
		return is_bool( $value ) || ( is_numeric( $value ) && in_array( $value, array( 0, 1 ) ) );
	}

	/**
	 * Sanitizes the given value into boolean.
	 *
	 * @since 3.7
	 *
	 * @param bool|int|string $value Value to sanitize, expected to be validated before.
	 * @return int Sanitized value, 0 or 1.
	 */
	protected function sanitize( $value ) {
		return intval( $value );
	}
}
