<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Primitive;

use WP_Syntex\Polylang\Options\Abstract_Option;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining single string option.
 *
 * @since 3.7
 */
abstract class Abstract_String extends Abstract_Option {
	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: 'string'}
	 */
	protected function get_specific_schema(): array {
		return array(
			'type' => 'string',
		);
	}
}