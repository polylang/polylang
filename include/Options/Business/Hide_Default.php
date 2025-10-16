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
 * Class defining the "Display/Hide URL language information for default language" boolean option.
 * /!\ Sanitization depends on `force_lang`: this option must be set AFTER `force_lang`.
 *
 * @since 3.7
 */
class Hide_Default extends Abstract_Boolean {
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'hide_default'
	 */
	public static function key(): string {
		return 'hide_default';
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
	 * Sanitizes option's value.
	 * Can populate the `$errors` property with blocking and non-blocking errors: in case of non-blocking errors,
	 * the value is sanitized and can be stored.
	 *
	 * @since 3.7
	 *
	 * @param bool    $value   Value to sanitize.
	 * @param Options $options All options.
	 * @return bool|WP_Error The sanitized value. An instance of `WP_Error` in case of blocking error.
	 */
	protected function sanitize( $value, Options $options ) {
		if ( 3 === $options->get( 'force_lang' ) ) {
			return false;
		}

		/** @var bool|WP_Error */
		return parent::sanitize( $value, $options );
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
			__( 'Remove the language code in URL for the default language: %1$s to hide, %2$s to display.', 'polylang' ),
			'`true`',
			'`false`'
		);
	}
}
