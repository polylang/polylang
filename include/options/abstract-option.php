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
	 * @return bool True if new value has been set, false otherwise.
	 */
	public function set( $value ): bool {
		if ( ! $this->validate( $value ) ) {
			return false;
		}

		$value = rest_sanitize_value_from_schema( $value, $this->get_schema(), $this->key() );
		if ( is_wp_error( $value ) ) {
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
	public function reset() {
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
	 * Validates option's value, can be overridden for specific cases not handled by `rest_validate_value_from_schema`.
	 *
	 * @since 3.7
	 *
	 * @param mixed $value Value to validate.
	 * @return bool True if the value is valid, false otherwise.
	 */
	protected function validate( $value ): bool {
		return ! is_wp_error( rest_validate_value_from_schema( $value, $this->get_schema(), $this->key() ) );
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
