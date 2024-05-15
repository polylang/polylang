<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Primitive;

use WP_Syntex\Polylang\Options\Abstract_Option;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining single integer option.
 *
 * @since 3.7
 *
 * @phpstan-import-type SchemaType from Abstract_Option
 */
class Integer extends Abstract_Option {
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
	 * @param int    $value       Option value.
	 * @param int    $default     Option default value.
	 * @param string $description Option description, used in JSON schema.
	 * @param int    $min         Optional. Minimal value. Default is `0`.
	 * @param int    $max         Optional. Maximal value. Default is `PHP_INT_MAX`.
	 *
	 * @phpstan-param non-falsy-string $key
	 */
	public function __construct( string $key, $value, $default, string $description, int $min = 0, int $max = PHP_INT_MAX ) {
		parent::__construct( $key, $value, $default, $description );
		$this->min = $min;
		$this->max = $max;
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: SchemaType, minimum: int, maximum: int}
	 */
	protected function get_specific_schema(): array {
		return array(
			'type'    => 'integer',
			'minimum' => $this->min,
			'maximum' => $this->max,
		);
	}
}
