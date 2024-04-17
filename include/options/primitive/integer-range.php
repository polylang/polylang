<?php
/**
 * @package Polylang
 */

/**
 * Class defining single integer range option.
 *
 * @since 3.7
 *
 * @phpstan-import-type Schema from PLL_Abstract_Option
 */
class PLL_Integer_Range_Option extends PLL_Abstract_Option {
	/**
	 * Minimal value of the integer range.
	 *
	 * @var int
	 */
	private $min;

	/**
	 * Maximal value of the integer range.
	 *
	 * @var int
	 */
	private $max;

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param string $key         Option key.
	 * @param mixed  $value       Option value.
	 * @param mixed  $default     Option default value.
	 * @param string $description Option description, used in JSON schema.
	 * @param int    $min         Minimal value.
	 * @param int    $max         Maximal value.
	 *
	 * @phpstan-param non-falsy-string $key
	 */
	public function __construct( string $key, $value, $default, string $description, int $min, int $max ) {
		parent::__construct( $key, $value, $default, $description );
		$this->min = $min;
		$this->max = $max;
	}

	/**
	 * Creates JSON schema of the option.
	 *
	 * @since 3.7
	 *
	 * @return array The schema.
	 *
	 * @phpstan-return Schema
	 */
	protected function create_schema(): array {
		return $this->build_schema(
			array(
				'type'    => 'integer',
				'minimum' => $this->min,
				'maximum' => $this->max,
			)
		);
	}
}
