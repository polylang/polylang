<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Syntex\Polylang\Options\Abstract_Option;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining navigation menus array option.
 *
 * @since 3.7
 *
 * @phpstan-import-type SchemaType from \WP_Syntex\Polylang\Options\Abstract_Option
 */
class Nav_Menu extends Abstract_Option {
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
	 *     additionalProperties: false
	 * }
	 */
	protected function get_specific_schema(): array {
		return array(
			'type'                 => 'object', // Correspond to associative array in PHP, @see{https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types}.
			'patternProperties'    => array(
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
			),
			'additionalProperties' => false,
		);
	}
}
