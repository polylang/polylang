<?php
/**
 * @package Polylang
 */

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
abstract class PLL_Abstract_Option {
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
	 * Non-blocking sanitization errors.
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
	 * @param mixed $value Value to set.
	 * @return WP_Error
	 */
	public function set( $value ): WP_Error {
		$this->errors = new WP_Error(); // Reset non-blocking sanitization errors.

		$is_valid = $this->validate( $value );
		if ( $is_valid->has_errors() ) {
			// Blocking validation error.
			return $is_valid;
		}

		$value = $this->sanitize( $value );
		if ( is_wp_error( $value ) ) {
			// Blocking sanitization error. `$this->errors` may still contain non-blocking sanitization errors.
			return $value;
		}

		// Sanitized value. `$this->errors` may still contain non-blocking sanitization errors.
		$this->value = $value;
		return new WP_Error();
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
	 * Validates option's value, can be overridden for specific cases not handled by `rest_validate_value_from_schema`.
	 * If the validation fails, the value must be rejected.
	 *
	 * @since 3.7
	 *
	 * @param mixed $value Value to validate.
	 * @return WP_Error
	 */
	protected function validate( $value ): WP_Error {
		$is_valid = rest_validate_value_from_schema( $value, $this->get_schema(), $this->key() );

		if ( is_wp_error( $is_valid ) ) {
			// Invalid.
			return $is_valid;
		}

		return new WP_Error();
	}

	/**
	 * Sanitizes option's value, can be overridden for specific cases not handled by `rest_sanitize_value_from_schema()`.
	 * Can return a `WP_Error` object in case of blocking sanitization error: the value must be rejected then.
	 * Can populate the `$errors` property with non-blocking sanitization errors: the value is sanitized and can be stored.
	 *
	 * @since 3.7
	 *
	 * @param mixed $value Value to filter.
	 * @return mixed
	 */
	protected function sanitize( $value ) {
		return rest_sanitize_value_from_schema( $value, $this->get_schema(), $this->key() );
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
}
