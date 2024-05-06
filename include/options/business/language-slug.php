<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Syntex\Polylang\Options\Primitive\String_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining language slug string option.
 *
 * @since 3.7
 *
 * @phpstan-import-type SchemaType from \WP_Syntex\Polylang\Options\Abstract_Option
 */
class Language_Slug extends String_Type {
	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: SchemaType, pattern: non-empty-string}
	 */
	protected function get_specific_schema(): array {
		$string_schema            = parent::create_schema();
		$string_schema['pattern'] = '^[a-z_-]+$';

		return $string_schema;
	}
}
