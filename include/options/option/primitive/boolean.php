<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Option\Primitive;

use WP_Syntex\Polylang\Options\Option\Abstract_Option;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining single boolean option.
 * Note that for historic reason, boolean are stored as 0 or 1.
 *
 * @since 3.7
 *
 * @phpstan-import-type SchemaType from Abstract_Option
 */
class Boolean extends Abstract_Option {
	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: SchemaType}
	 */
	protected function get_specific_schema(): array {
		return array(
			'type' => 'boolean',
		);
	}
}
