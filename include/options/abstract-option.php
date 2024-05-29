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
 *     type: SchemaType,
 *     context: array<non-falsy-string>
 * }
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
	 * @param string $key     Option key.
	 * @param mixed  $value   Option value.
	 * @param mixed  $default Option default value.
	 *
	 * @phpstan-param non-falsy-string $key
	 */
	public function __construct( string $key, $value, $default ) {
		$this->errors  = new WP_Error();
		$this->key     = $key;
		$this->default = $default;

		if ( ! isset( $value ) ) {
			$this->value = $default;
			return;
		}

		$value = rest_sanitize_value_from_schema( $this->prepare( $value ), $this->get_specific_schema(), $this->key() );

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
		$value        = $this->prepare( $value );
		$is_valid     = rest_validate_value_from_schema( $value, $this->get_specific_schema(), $this->key() );

		if ( is_wp_error( $is_valid ) ) {
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
	public function &get() {
		return $this->value;
	}

	/**
	 * Sets default option value.
	 *
	 * @since 3.7
	 *
	 * @return mixed The new value.
	 */
	public function reset() {
		$this->value = $this->default;
		return $this->value;
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
				'description' => $this->get_description(),
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
	 * Prepares a value before validation.
	 *
	 * @since 3.7
	 *
	 * @param mixed $value Value to format.
	 * @return mixed
	 */
	protected function prepare( $value ) {
		return $value;
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
		return rest_sanitize_value_from_schema( $value, $this->get_specific_schema(), $this->key() );
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: SchemaType}
	 */
	abstract protected function get_specific_schema(): array;

	/**
	 * Returns the description used in the JSON schema.
	 *
	 * @since 3.7
	 *
	 * @return string
	 */
	abstract protected function get_description(): string;
}
