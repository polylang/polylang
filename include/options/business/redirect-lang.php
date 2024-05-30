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
 * Class defining the "Remove the page name or page id from the URL of the front page" boolean option.
 *
 * @since 3.7
 */
class Redirect_Lang extends Abstract_Boolean {
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'redirect_lang'
	 */
	public static function key(): string {
		return 'redirect_lang';
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 * @since 3.7
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( 'Remove the page name or page id from the URL of the front page: true to remove, false to keep.', 'polylang' );
	}
}
