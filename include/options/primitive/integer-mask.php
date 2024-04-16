<?php
/**
 * @package Polylang
 */

/**
 * Class defining single integer mask option.
 *
 * @since 3.7
 */
class PLL_Integer_Mask_Option extends PLL_Abstract_Option {
	/**
	 * Minimal value of the integer mask.
	 *
	 * @var int
	 */
	private $min;

	/**
	 * Maximal value of the integer mask.
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
	public function __construct( string $key, $value, $default, string $description, $min, $max ) {
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
	 */
	public function create_schema(): array {
		return array(
			'$schema'     => 'http://json-schema.org/draft-04/schema#',
			'title'       => $this->key(),
			'description' => $this->description,
			'type'        => 'integer',
			'context'     => array( 'edit' ),
			'minimum'     => $this->min,
			'maximum'     => $this->max,
		);
	}
}
