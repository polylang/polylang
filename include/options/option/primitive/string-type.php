<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Option\Primitive;

use WP_Syntex\Polylang\Options\Option\Abstract_Option;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining single string option.
 *
 * @since 3.7
 *
 * @phpstan-import-type Schema from Abstract_Option
 */
class String_Type extends Abstract_Option {
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
				'type' => 'string',
			)
		);
	}
}
