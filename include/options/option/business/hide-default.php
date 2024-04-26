<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Option\Business;

use WP_Syntex\Polylang\Options\Option\Primitive\Boolean;
use WP_Syntex\Polylang\Options\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining the "Display/Hide URL language information for default language" boolean option.
 * /!\ Sanitization depends on `force_lang`: this option must be set AFTER `force_lang`.
 *
 * @since 3.7
 *
 * @phpstan-import-type Schema from \WP_Syntex\Polylang\Options\Option\Abstract_Option
 */
class Hide_Default extends Boolean {

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
		if ( 3 === $options->get( 'force_lang' ) ) {
			return false;
		}

		$value = parent::sanitize( $value, $options );

		/** @var bool $value */
		if ( $this->has_blocking_errors() ) {
			// Blocking error.
			return $value;
		}

		return $value;
	}
	/**
	 * Creates JSON schema of the option.
	 *
	 * @since 3.7
	 *
	 * @return array The schema.
	 *
	 * @phpstan-return Schema
	 */
	protected function create_schema(): array {
		return $this->build_schema(
			array(
				'type' => 'boolean',
			)
		);
	}
}
