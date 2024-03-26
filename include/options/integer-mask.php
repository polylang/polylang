<?php
/**
 * @package Polylang
 */

/**
 * Class to manage single integer mask option, default range from 0 to `PHP_INT_MAX`.
 *
 * @since 3.7
 */
class PLL_Integer_Mask_Option extends PLL_Abstract_Option {
	/**
	 * Minimal value of the integer mask, default to 0.
	 *
	 * @var int
	 */
	private $min = 0;

	/**
	 * Maximal value of the integer mask, default to `PHP_INT_MAX`.
	 *
	 * @var int
	 */
	private $max = PHP_INT_MAX;

	/**
	 * Sets minimal value.
	 *
	 * @since 3.7
	 *
	 * @param int $min Minimal value for integer mask.
	 * @return void
	 */
	public function set_min( int $min ) {
		$this->min = $min;
	}

	/**
	 * Sets maximal value.
	 *
	 * @since 3.7
	 *
	 * @param integer $max Maximal value for integer mask.
	 * @return void
	 */
	public function set_max( int $max ) {
		$this->max = $max;
	}

	/**
	 * Validates option's value according to `self::$min` and `self::$max`.
	 *
	 * @since 3.7
	 *
	 * @param mixed $value Value to validate.
	 * @return bool True if the value is valid, false otherwise.
	 */
	protected function validate( $value ): bool {
		return is_numeric( $value ) && $value > $this->min && $value < $this->max;
	}

	/**
	 * Sanitizes the given value into integer.
	 *
	 * @since 3.7
	 *
	 * @param bool|int|string $value Value to sanitize, expected to be validated before.
	 * @return int Sanitized value, 0 or 1.
	 */
	protected function sanitize( $value ) {
		return (int) $value;
	}
}
