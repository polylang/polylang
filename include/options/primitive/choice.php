<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class defining single choice option.
 *
 * @since 3.7
 *
 * @phpstan-import-type Schema from PLL_Abstract_Option
 */
class PLL_Choice_Option extends PLL_Abstract_Option {
	/**
	 * List of possible choices.
	 *
	 * @var array
	 *
	 * @phpstan-var non-empty-list<int|string>
	 */
	private $choices;

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param string $key         Option key.
	 * @param mixed  $value       Option value.
	 * @param mixed  $default     Option default value.
	 * @param string $description Option description, used in JSON schema.
	 * @param array  $choices     List of possible choices. All choices must of the same type.
	 *
	 * @phpstan-param non-falsy-string $key
	 * @phpstan-param non-empty-array<int|string> $choices
	 */
	public function __construct( string $key, $value, $default, string $description, array $choices ) {
		parent::__construct( $key, $value, $default, $description );
		$this->choices = array_values( $choices );
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
				'type' => gettype( reset( $this->choices ) ),
				'enum' => $this->choices,
			)
		);
	}
}