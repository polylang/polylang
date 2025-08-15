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
 * Class defining the "Detect browser language" boolean option.
 * /!\ Sanitization depends on `force_lang`: this option must be set AFTER `force_lang`.
 *
 * @since 3.7
 */
class Browser extends Abstract_Boolean {
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'browser'
	 */
	public static function key(): string {
		return 'browser';
	}

	/**
	 * Adds information to the site health info array.
	 *
	 * @since 3.8
	 *
	 * @param array   $info    The current site health information.
	 * @param Options $options An instance of the Options class providing additional configuration.
	 *
	 * @return array The updated site health information.
	 */
	public function add_to_site_health_info( array $info, Options $options ): array {
		if ( ! $options->get( self::key() ) ) {
			$value = '0: ' . __( 'Detect browser language deactivated', 'polylang' );
		} else {
			$value = '1: ' . __( 'Detect browser language activated', 'polylang' );
		}

		return $this->get_site_health_info( $info, $value, self::key() );
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
		if ( 3 === $options->get( 'force_lang' ) && ! class_exists( 'PLL_Xdata_Domain', true ) ) {
			// Cannot share cookies between domains without Polylang Pro.
			return false;
		}

		/** @var bool|WP_Error */
		$value = parent::sanitize( $value, $options );
		return $value;
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
			__( 'Detect preferred browser language on front page: %1$s to detect, %2$s to not detect.', 'polylang' ),
			'`true`',
			'`false`'
		);
	}
}
