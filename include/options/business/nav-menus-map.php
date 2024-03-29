<?php
/**
 * @package Polylang
 */

/**
 * Class to manage navigation menus array option.
 *
 * @since 3.7
 */
class PLL_Nav_Menu_Map_Option extends PLL_Map_Option {
	/**
	 * Creates JSON schema of the option.
	 *
	 * @since 3.7
	 *
	 * @return array The schema.
	 */
	public function create_schema(): array {
		$map_schema                      = parent::create_schema();
		$map_schema['patternProperties'] = array(
			'^\\w+$' => array( // Any word characters as key, correspond to a theme slug.
				'type' => 'object',
				'patternProperties'    => array(
					'^[a-z_-]+$' => array( // Language slug as key.
						'type' => 'integer',
						'minimum' => 0, // A post ID.
					),
				),
				'additionalProperties' => false,
			),
		);

		return $map_schema;
	}
}
