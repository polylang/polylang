<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Error;
use WP_Syntex\Polylang\Options\Primitive\Abstract_Boolean;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining the "Translate media" boolean option.
 *
 * @since 3.7
 */
class Media_Support extends Abstract_Boolean {
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'media_support'
	 */
	public static function key(): string {
		return 'media_support';
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
			/* translators: %1$s and %2$s are "true/false" values. */
			__( 'Translate media: %1$s to translate, %2$s otherwise.', 'polylang' ),
			'`true`',
			'`false`'
		);
	}
}
