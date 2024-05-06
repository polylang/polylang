<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options;

use WP_Error;
use WP_Syntex\Polylang\Options\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining a single option.
 *
 * @since 3.7
 *
 * @phpstan-type SchemaType 'string'|'null'|'number'|'integer'|'boolean'|'array'|'object'
 * @phpstan-type Schema array{
 *     '$schema': non-falsy-string,
 *     title: non-falsy-string,
 *     description: string,
 *     type: SchemaType,
 *     context: array<non-falsy-string>
 * }&array<non-falsy-string, mixed>
 */
abstract class Abstract_Option {
	/**
	 * Option key.
	 *
	 * @var string
	 * @phpstan-var non-falsy-string
	 */
	private $key;

	/**
	 * Option value.
	 *
	 * @var mixed
	 */
	private $value;

	/**
	 * Option default value.
	 *
	 * @var mixed
	 */
	private $default;

	/**
	 * Cached option JSON schema.
	 *
	 * @var array|null
	 *
	 * @phpstan-var Schema|null
	 */
	private $schema;

	/**
	 * Option description.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Validation and sanitization errors.
	 *
	 * @var WP_Error
	 */
	protected $errors;

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param string $key         Option key.
	 * @param mixed  $value       Option value.
	 * @param mixed  $default     Option default value.
	 * @param string $description Option description, used in JSON schema.
	 *
	 * @phpstan-param non-falsy-string $key
	 */
	public function __construct( string $key, $value, $default, string $description ) {
		$this->errors      = new WP_Error();
		$this->key         = $key;
		$this->default     = $default;
		$this->description = $description;

		$value = rest_sanitize_value_from_schema( $value, $this->get_schema(), $this->key() );

		if ( ! is_wp_error( $value ) ) {
			$this->value = $value;
		} else {
			$this->value = $default;
		}
	}

	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return non-falsy-string
	 */
	public function key(): string {
		return $this->key;
	}

	/**
	 * Sets option's value if valid, does nothing otherwise.
	 *
	 * @since 3.7
	 *
	 * @param mixed   $value   Value to set.
	 * @param Options $options All options.
	 * @return bool True if the value has been assigned. False in case of errors.
	 */
	public function set( $value, Options $options ): bool {
		$this->errors = new WP_Error(); // Reset errors.
		$is_valid     = $this->validate( $value, $options );

		if ( $is_valid->has_errors() ) {
			// Blocking validation error.
			$this->errors->merge_from( $is_valid );
			return false;
		}

		$value = $this->sanitize( $value, $options );

		if ( is_wp_error( $value ) ) {
			// Blocking sanitization error.
			$this->errors->merge_from( $value );
			return false;
		}

		$this->value = $value;
		return true;
	}

	/**
	 * Returns option's value.
	 *
	 * @since 3.7
	 *
	 * @return mixed
	 */
	public function get() {
		return $this->value;
	}

	/**
	 * Sets default option value.
	 *
	 * @since 3.7
	 *
	 * @return bool True if the value has been modified, false otherwise.
	 */
	public function reset(): bool {
		if ( $this->value === $this->default ) {
			return false;
		}

		$this->value = $this->default;
		return true;
	}

	/**
	 * Returns JSON schema of the option.
	 *
	 * @since 3.7
	 *
	 * @return array The schema.
	 *
	 * @phpstan-return Schema
	 */
	public function get_schema(): array {
		if ( is_array( $this->schema ) ) {
			return $this->schema;
		}

		$this->schema = array_merge(
			array(
				'$schema'     => 'http://json-schema.org/draft-04/schema#',
				'title'       => $this->key(),
				'description' => $this->description,
				'context'     => array( 'edit' ),
			),
			$this->get_specific_schema()
		);

		return $this->schema;
	}

	/**
	 * Returns non-blocking sanitization errors.
	 *
	 * @since 3.7
	 *
	 * @return WP_Error
	 */
	public function get_errors(): WP_Error {
		return $this->errors;
	}

	/**
	 * Validates option's value, can be overridden for specific cases not handled by `rest_validate_value_from_schema`.
	 * If the validation fails, the value must be rejected.
	 *
	 * @since 3.7
	 *
	 * @param mixed   $value   Value to validate.
	 * @param Options $options All options.
	 * @return WP_Error
	 */
	protected function validate( $value, Options $options ): WP_Error { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$is_valid = rest_validate_value_from_schema( $value, $this->get_schema(), $this->key() );

		if ( is_wp_error( $is_valid ) ) {
			// Invalid: blocking error.
			return $is_valid;
		}

		return new WP_Error();
	}

	/**
	 * Sanitizes option's value, can be overridden for specific cases not handled by `rest_sanitize_value_from_schema()`.
	 * Can populate the `$errors` property with blocking and non-blocking errors: in case of non-blocking errors,
	 * the value is sanitized and can be stored.
	 *
	 * @since 3.7
	 *
	 * @param mixed   $value   Value to sanitize.
	 * @param Options $options All options.
	 * @return mixed The sanitized value. An instance of `WP_Error` in case of blocking error.
	 */
	protected function sanitize( $value, Options $options ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return rest_sanitize_value_from_schema( $value, $this->get_schema(), $this->key() );
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: SchemaType}&array<non-falsy-string, mixed>
	 */
	abstract protected function get_specific_schema(): array;

	/**
	 * Changes error codes so they are unique to the option.
	 * Copied from `WP_Error::copy_errors()`.
	 *
	 * @since 3.7
	 *
	 * @param WP_Error $errors An error object.
	 * @return WP_Error
	 */
	protected function make_error_unique( WP_Error $errors ): WP_Error {
		$return = new WP_Error();

		foreach ( $errors->get_error_codes() as $code ) {
			$new_code = "pll_{$code}_{$this->key}";

			foreach ( $errors->get_error_messages( $code ) as $error_message ) {
				$return->add( $new_code, $error_message );
			}

			foreach ( $errors->get_all_error_data( $code ) as $data ) {
				$return->add_data( $data, $new_code );
			}
		}

		return $return;
	}
}
