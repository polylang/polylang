<?php
/**
 * @package Polylang
 */

/**
 * Class defining single associative array option.
 *
 * @since 3.7
 *
 * @phpstan-import-type Schema from PLL_Abstract_Option
 */
class PLL_Map_Option extends PLL_List_Option {
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
				'type'                 => 'object', // Correspond to associative array in PHP, @see{https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types}.
				'patternProperties'    => array(
					'^\\w+$' => array( // Any word characters as key.
						'type' => $this->type,
					),
				),
				'additionalProperties' => false,
			)
		);
	}
}
