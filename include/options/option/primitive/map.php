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
 * @phpstan-import-type SchemaType from Abstract_Option
 */
class Map extends Abstract_Option {
	/**
	 * Value type.
	 *
	 * @var SchemaType
	 */
	protected $type;

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param string $key         Option key.
	 * @param mixed  $value       Option value.
	 * @param mixed  $default     Option default value.
	 * @param string $description Option description, used in JSON schema.
	 * @param string $type        JSON schema value type for the list items, @see {https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types}.
	 *                            Possible values are `'string'`, `'null'`, `'number'` (float), `'integer'`, `'boolean'`,
	 *                            `'array'` (array with integer keys), and `'object'` (array with string keys).
	 *
	 * @phpstan-param non-falsy-string $key
	 * @phpstan-param SchemaType $type
	 */
	public function __construct( string $key, $value, $default, string $description, string $type ) {
		$this->type = $type;
		parent::__construct( $key, $value, $default, $description );
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{
	 *     type: SchemaType,
	 *     patternProperties: non-empty-array<non-empty-string, array{type: SchemaType}>,
	 *     additionalProperties: bool
	 * }
	 */
	protected function create_schema(): array {
		return array(
			'type'                 => 'object', // Correspond to associative array in PHP, @see{https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types}.
			'patternProperties'    => array(
				'^\\w+$' => array( // Any word characters as key.
					'type' => $this->type,
				),
			),
			'additionalProperties' => false,
		);
	}
}
