<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Primitive;

use WP_Syntex\Polylang\Options\Abstract_Option;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining single boolean option.
 * Note that for historic reason, boolean are stored as 0 or 1.
 *
 * @since 3.7
 */
abstract class Abstract_Boolean extends Abstract_Option {
	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: 'boolean'}
	 */
	protected function get_specific_schema(): array {
		return array(
			'type' => 'boolean',
		);
	}
}