<?php
/**
 * @package Polylang
 */

/**
 * Class to manage language slug string option.
 *
 * @since 3.7
 */
class PLL_Language_Slug_String_Option extends PLL_String_Option {
	/**
	 * Creates JSON schema of the option.
	 *
	 * @since 3.7
	 *
	 * @return array The schema.
	 */
	public function create_schema(): array {
		$string_schema            = parent::create_schema();
		$string_schema['pattern'] = '^[a-z_-]+$';

		return $string_schema;
	}
}
