<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Option\Primitive;

use WP_Syntex\Polylang\Options\Option\Abstract_Option;
use WP_Syntex\Polylang\Options\Option\Primitive\List_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining single associative array option.
 *
 * @since 3.7
 *
 * @phpstan-import-type Schema from Abstract_Option
 */
class Map extends List_Type {
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
