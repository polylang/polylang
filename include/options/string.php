<?php
/**
 * @package Polylang
 */

/**
 * Class to manage single string option.
 *
 * @since 3.7
 */
class PLL_String_Option extends PLL_Abstract_Option {
	/**
	 * Validates option's value,
	 * only string accepted.
	 *
	 * @since 3.7
	 *
	 * @param mixed $value Value to validate.
	 * @return bool True if the value is valid, false otherwise.
	 */
	protected function validate( $value ): bool {
		return is_string( $value );
	}

	/**
	 * Sanitizes the given value into string.
	 *
	 * @since 3.7
	 *
	 * @param string $value Does nothing, `self::valifate()` ensure `$value` is a string.
	 * @return string Sanitized value.
	 */
	protected function sanitize( $value ) {
		return $value;
	}
}
