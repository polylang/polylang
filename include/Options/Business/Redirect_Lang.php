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
	 * Adds information to the site health info array.
	 *
	 * @since 3.8
	 *
	 * @param Options $options An instance of the Options class providing additional configuration.
	 *
	 * @return array The updated site health information.
	 */
	public function get_site_health_info( Options $options ): array { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( $this->get() ) {
			$value = '1: ' . __( 'The front page URL contains the language code instead of the page name or page id', 'polylang' );
		} else {
			$value = '0: ' . __( 'The front page URL contains the page name or page id instead of the language code', 'polylang' );
		}

		return $this->format_single_value_for_site_health_info( $value );
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
			__( 'Remove the page name or page ID from the URL of the front page: %1$s to remove, %2$s to keep.', 'polylang' ),
			'`true`',
			'`false`'
		);
	}
}
