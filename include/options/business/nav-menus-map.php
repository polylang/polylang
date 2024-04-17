<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class defining navigation menus array option.
 *
 * @since 3.7
 *
 * @phpstan-import-type Schema from PLL_Abstract_Option
 */
class PLL_Nav_Menu_Map_Option extends PLL_Map_Option {
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
		$map_schema                      = parent::create_schema();
		$map_schema['patternProperties'] = array(
			'^\\w+$' => array( // Any word characters as key, correspond to a theme slug.
				'type'              => 'object',
				'context'           => array( 'edit' ),
				'patternProperties' => array(
					'^[a-z_-]+$' => array( // Language slug as key.
						'type'    => 'integer',
						'minimum' => 0, // A post ID.
					),
				),
				'additionalProperties' => false,
			),
		);

		return $map_schema;
	}
}
