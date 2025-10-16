<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Primitive;

use WP_Syntex\Polylang\Options\Abstract_Option;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining single list option, default value type to mixed.
 *
 * @since 3.7
 *
 * @phpstan-import-type SchemaType from Abstract_Option
 */
abstract class Abstract_List extends Abstract_Option {
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
			return array_values( array_unique( $value ) );
		}
		return $value;
	}

	/**
	 * Returns the JSON schema value type for the list items.
	 * Possible values are `'string'`, `'null'`, `'number'` (float), `'integer'`, `'boolean'`,
	 * `'array'` (array with integer keys), and `'object'` (array with string keys).
	 *
	 * @since 3.7
	 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types
	 *
	 * @return string
	 *
	 * @phpstan-return SchemaType
	 */
	protected function get_type(): string {
		return 'string';
	}

	/**
	 * Returns the default value.
	 *
	 * @since 3.7
	 *
	 * @return array
	 */
	protected function get_default() {
		return array();
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
	protected function get_data_structure(): array {
		return array(
			'type'  => 'array',
			'items' => array(
				'type' => $this->get_type(),
			),
		);
	}
}
