<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use PLL_Install;
use WP_Error;
use WP_Syntex\Polylang\Options\Primitive\Abstract_Boolean;
use WP_Syntex\Polylang\Options\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining the "Hide Language From Content Option" boolean option.
 *
 * @since 3.7
 */
class Hide_Language_From_Content_Option extends Abstract_Boolean {
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'hide_language_from_content_option'
	 */
	public static function key(): string {
		return 'hide_language_from_content_option';
	}

	/**
	 * Returns the default value.
	 *
	 * @since 3.7
	 *
	 * @return bool
	 */
	protected function get_default() {
		return PLL_Install::should_hide_language_from_content_option();
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 * @since 3.7
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( 'Tells if the choice allowing to define the current language from content must be hidden.', 'polylang' );
	}
}
