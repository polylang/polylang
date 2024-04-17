<?php
/**
 * @package Polylang
 */

/**
 * Class defining language slug string option.
 *
 * @since 3.7
 *
 * @phpstan-import-type Schema from PLL_Abstract_Option
 */
class PLL_Language_Slug_String_Option extends PLL_String_Option {
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
		$string_schema            = parent::create_schema();
		$string_schema['pattern'] = '^[a-z_-]+$';

		return $string_schema;
	}
}
