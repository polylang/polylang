<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Syntex\Polylang\Options\Primitive\Map;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining navigation menus array option.
 *
 * @since 3.7
 *
 * @phpstan-import-type SchemaType from \WP_Syntex\Polylang\Options\Abstract_Option
 */
class Nav_Menu extends Map {
	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{
	 *     type: SchemaType,
	 *     patternProperties: non-empty-array<
	 *         non-empty-string, array{
	 *             type: SchemaType,
	 *             context: array<non-falsy-string>,
	 *             patternProperties: non-empty-array<non-empty-string, array{type: SchemaType, minimum: int}>
	 *         }
	 *     >,
	 *     additionalProperties: bool
	 * }
	 */
	protected function get_specific_schema(): array {
		$map_schema                      = parent::get_specific_schema();
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
