<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Error;
use WP_Syntex\Polylang\Options\Primitive\Abstract_Boolean;
use WP_Syntex\Polylang\Options\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining the "Remove /language/ in pretty permalinks" boolean option.
 *
 * @since 3.7
 */
class Rewrite extends Abstract_Boolean {
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'rewrite'
	 */
	public static function key(): string {
		return 'rewrite';
	}

	/**
	 * Returns the default value.
	 *
	 * @since 3.7
	 *
	 * @return bool
	 */
	protected function get_default() {
		return true;
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 * @since 3.7
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return sprintf(
			/* translators: %1$s is a URL slug: `/language/`. %2$s and %3$s are "true/false" values. */
			__( 'Remove %1$s in pretty permalinks: %2$s to remove, %3$s to keep.', 'polylang' ),
			'`/language/`',
			'`true`',
			'`false`'
		);
	}
}
