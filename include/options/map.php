<?php
/**
 * @package Polylang
 */

/**
 * Class to manage single associative array option, default value type to mixed.
 * For convenience, no empty or falsy values are allowed.
 *
 * @since 3.7
 */
class PLL_Map_Option extends PLL_Abstract_Option {
	/**
	 * Value type.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Sets the value type, pass a type returned by `gettype()`, @see {https://www.php.net/manual/fr/function.gettype.php}.
	 *
	 * @since 3.7
	 *
	 * @param string $type Value type.
	 * @return void
	 */
	public function set_type( string $type ) {
		$this->type = $type;
	}

	/**
	 * Validates option's value,
	 *
	 * @since 3.7
	 *
	 * @param mixed $value Value to validate.
	 * @return bool True if the value is valid, false otherwise.
	 */
	protected function validate( $value ): bool {
		return is_array( $value );
	}

	/**
	 * Sanitizes the given value into a map of specific type.
	 *
	 * @since 3.7
	 *
	 * @param array $value Value to sanitize, expected to be validated before.
	 * @return array Sanitized value.
	 */
	protected function sanitize( $value ) {
		return array_filter(
			$value,
			function ( $v, $k ) {
				if ( ! empty( $this->type ) && ( gettype( $v ) !== $this->type || empty( $v ) ) ) {
					return false;
				}

				return is_string( $k ) && ! empty( $k );
			},
			ARRAY_FILTER_USE_BOTH
		);
	}
}
