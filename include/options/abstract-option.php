<?php
/**
 * @package Polylang
 */

/**
 * Class to manage a single option.
 *
 * @since 3.7
 */
abstract class PLL_Abstract_Option {
	/**
	 * Option key.
	 *
	 * @var string
	 * @phpstan-var non-falsy-string
	 */
	protected $key;

	/**
	 * Option value.
	 *
	 * @var mixed
	 */
	protected $value;

	/**
	 * Option default value.
	 *
	 * @var mixed
	 */
	protected $default;

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param string $key     Option key.
	 * @param mixed  $value   Option value.
	 * @param mixed  $default Option default value.
	 *
	 * @phpstan-param non-falsy-string $key
	 */
	public function __construct( string $key, $value, $default ) {
		$this->key     = $key;
		$this->value   = $value;
		$this->default = $default;
	}

	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return non-falsy-string
	 */
	public function key(): string {
		return $this->key;
	}

	/**
	 * Sets option's value if valid, does nothing otherwise.
	 *
	 * @since 3.7
	 *
	 * @param mixed $value Value to set.
	 * @return bool True if new value has been set, false otherwise.
	 */
	public function set( $value ): bool {
		if ( $this->validate( $value ) ) {
			$this->value = $this->sanitize( $value );

			return true;
		}

		return false;
	}

	/**
	 * Returns option's value.
	 *
	 * @since 3.7
	 *
	 * @return mixed
	 */
	public function get() {
		return $this->value;
	}

	/**
	 * Sets default option value.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	public function reset() {
		$this->value = $this->default;
	}

	/**
	 * Validates option's value.
	 *
	 * @since 3.7
	 *
	 * @param mixed $value Value to validate.
	 * @return bool True if the value is valid, false otherwise.
	 */
	abstract protected function validate( $value ): bool;

	/**
	 * Sanitizes the given value.
	 *
	 * @since 3.7
	 *
	 * @param mixed $value Value to sanitize, expected to be validated before.
	 * @return mixed Sanitized value.
	 */
	abstract protected function sanitize( $value );
}
