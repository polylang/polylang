<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Syntex\Polylang\Options\Primitive\Boolean;
use WP_Syntex\Polylang\Options\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining the "Detect browser language" boolean option.
 * /!\ Sanitization depends on `force_lang`: this option must be set AFTER `force_lang`.
 *
 * @since 3.7
 */
class Browser extends Boolean {
	/**
	 * Sanitizes option's value.
	 * Can populate the `$errors` property with blocking and non-blocking errors: in case of non-blocking errors,
	 * the value is sanitized and can be stored.
	 *
	 * @since 3.7
	 *
	 * @param bool    $value   Value to sanitize.
	 * @param Options $options All options.
	 * @return bool The sanitized value. The previous value in case of blocking error.
	 */
	protected function sanitize( $value, Options $options ) {
		if ( 3 === $options->get( 'force_lang' ) && ! class_exists( 'PLL_Xdata_Domain', true ) ) {
			// Cannot share cookies between domains without Polylang Pro.
			return false;
		}

		$value = parent::sanitize( $value, $options );

		/** @var bool $value */
		return $value;
	}
}
