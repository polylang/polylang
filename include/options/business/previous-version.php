<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Syntex\Polylang\Options\Primitive\Abstract_String;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining the "previous version" option.
 *
 * @since 3.7
 */
class Previous_Version extends Abstract_String {
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'previous_version'
	 */
	public static function key(): string {
		return 'previous_version';
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 * @since 3.7
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( "Polylang's previous version.", 'polylang' );
	}
}
