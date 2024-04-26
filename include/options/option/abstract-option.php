<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Option;

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

		if ( ! $this->validate( $value, $options ) ) {
			// Blocking validation error.
			return false;
		}

		$value = $this->sanitize( $value, $options );

		if ( $this->has_blocking_errors() ) {
			// Blocking sanitization error.
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
	 * @return void
	 */
	public function reset(): void {
		$this->value = $this->default;
	}

	/**
	 * Returns JSON schema of the option.
	 *
	 * @since 3.7
	 *
	 * @return array The schema.
	 */
	public function get_schema(): array {
		if ( is_array( $this->schema ) ) {
			return $this->schema;
		}

		$this->schema = $this->create_schema();

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
	 * Tells if blocking errors have been raised during validation and sanitization.
	 *
	 * @since 3.7
	 *
	 * @return bool
	 */
	public function has_blocking_errors(): bool {
		foreach ( $this->errors->get_error_codes() as $code ) {
			$data = $this->errors->get_error_data( $code );

			if ( empty( $data ) || 'error' === $data ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Validates option's value, can be overridden for specific cases not handled by `rest_validate_value_from_schema`.
	 * If the validation fails, the value must be rejected.
	 *
	 * @since 3.7
	 *
	 * @param mixed   $value   Value to validate.
	 * @param Options $options All options.
	 * @return bool True on success, false otherwise.
	 */
	protected function validate( $value, Options $options ): bool { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$is_valid = rest_validate_value_from_schema( $value, $this->get_schema(), $this->key() );

		if ( is_wp_error( $is_valid ) ) {
			// Invalid: blocking error.
			$this->errors->merge_from( $this->make_error_unique( $is_valid ) );
			return false;
		}

		return true;
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
	 * @return mixed The sanitized value. The previous value in case of blocking error.
	 */
	protected function sanitize( $value, Options $options ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$value = rest_sanitize_value_from_schema( $value, $this->get_schema(), $this->key() );

		if ( is_wp_error( $value ) ) {
			// Blocking error.
			$this->errors->merge_from( $this->make_error_unique( $value ) );
			return $this->value;
		}

		return $value;
	}

	/**
	 * Creates JSON schema of the option.
	 *
	 * @since 3.7
	 *
	 * @return array The schema.
	 */
	abstract protected function create_schema(): array;

	/**
	 * Returns a base for a JSON schema of the option.
	 *
	 * @since 3.7
	 *
	 * @param array $schema A list of data to add to the schema. At least the key `type` must be added.
	 * @return array The schema.
	 *
	 * @phpstan-param array{type: SchemaType}&array<non-falsy-string, mixed> $schema
	 * @phpstan-return Schema
	 */
	protected function build_schema( array $schema ): array {
		return array_merge(
			array(
				'$schema'     => 'http://json-schema.org/draft-04/schema#',
				'title'       => $this->key(),
				'description' => $this->description,
				'context'     => array( 'edit' ),
			),
			$schema
		);
	}

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
