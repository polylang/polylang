<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Option\Business;

use WP_Syntex\Polylang\Options\Option\Primitive\String_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining language slug string option.
 *
 * @since 3.7
 *
 * @phpstan-import-type Schema from \WP_Syntex\Polylang\Options\Option\Abstract_Option
 */
class Language_Slug extends String_Type {
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
		$string_schema            = parent::create_schema();
		$string_schema['pattern'] = '^[a-z_-]+$';

		return $string_schema;
	}
}
