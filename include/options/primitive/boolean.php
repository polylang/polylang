<?php
/**
 * @package Polylang
 */

/**
 * Class defining single boolean option.
 * Note that for historic reason, boolean are stored as 0 or 1.
 *
 * @since 3.7
 *
 * @phpstan-import-type Schema from PLL_Abstract_Option
 */
class PLL_Boolean_Option extends PLL_Abstract_Option {
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
				'type' => 'boolean',
			)
		);
	}
}
