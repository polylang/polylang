<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Primitive;

use WP_Syntex\Polylang\Options\Abstract_Option;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining single list option, default value type to mixed.
 * For convenience, no empty or falsy values are allowed.
 *
 * @since 3.7
 *
 * @phpstan-import-type SchemaType from Abstract_Option
 */
abstract class Abstract_List extends Abstract_Option {
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
	 * @param string $key     Option key.
	 * @param mixed  $value   Option value.
	 * @param mixed  $default Option default value.
	 * @param string $type    JSON schema value type for the list items, @see {https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types}.
	 *                        Possible values are `'string'`, `'null'`, `'number'` (float), `'integer'`, `'boolean'`,
	 *                        `'array'` (array with integer keys), and `'object'` (array with string keys).
	 *
	 * @phpstan-param non-falsy-string $key
	 * @phpstan-param SchemaType $type
	 */
	public function __construct( string $key, $value, $default, string $type ) {
		$this->type = $type;
		parent::__construct( $key, $value, $default );
	}

	/**
	 * Prepares a value before validation.
	 * Allows to receive a string-keyed array but returns an integer-keyed array.
	 *
	 * @since 3.7
	 *
	 * @param mixed $value Value to format.
	 * @return mixed
	 */
	protected function prepare( $value ) {
		if ( is_array( $value ) ) {
			return array_values( $value );
		}
		return $value;
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: 'array', items: array{type: SchemaType}}
	 */
	protected function get_specific_schema(): array {
		return array(
			'type'  => 'array',
			'items' => array(
				'type' => $this->type,
			),
		);
	}
}
