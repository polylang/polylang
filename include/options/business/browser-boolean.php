<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class defining the "Detect browser language" boolean option.
 * /!\ Sanitization depends on `force_lang`: this option must be set AFTER `force_lang`.
 *
 * @since 3.7
 *
 * @phpstan-import-type Schema from PLL_Abstract_Option
 */
class PLL_Browser_Boolean_Option extends PLL_Boolean_Option {
	/**
	 * Sanitizes option's value.
	 * Can populate the `$errors` property with blocking and non-blocking errors: in case of non-blocking errors,
	 * the value is sanitized and can be stored.
	 *
	 * @since 3.7
	 *
	 * @param bool        $value   Value to sanitize.
	 * @param PLL_Options $options All options.
	 * @return bool The sanitized value. The previous value in case of blocking error.
	 */
	protected function sanitize( $value, PLL_Options $options ) {
		if ( 3 === $options->get( 'force_lang' ) && ! class_exists( 'PLL_Xdata_Domain', true ) ) {
			// Cannot share cookies between domains without Polylang Pro.
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
