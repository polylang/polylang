<?php
/**
 * @package Polylang
 */

/**
 * Class defining single list option, default value type to mixed.
 * For convenience, no empty or falsy values are allowed.
 *
 * @since 3.7
 */
class PLL_List_Option extends PLL_Abstract_Option {
	/**
	 * Value type.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param string $key         Option key.
	 * @param mixed  $value       Option value.
	 * @param mixed  $default     Option default value.
	 * @param string $description Option description, used in JSON schema.
	 * @param string $type        JSON schema value type for the list items, @see {https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types}.
	 *                            Possible values are `'string'`, `'null'`, `'number'` (float), `'integer'`, `'boolean'`, and `'array'`.
	 *
	 * @phpstan-param non-falsy-string $key
	 * @phpstan-param 'string'|'null'|'number'|'integer'|'boolean'|'array' $type
	 */
	public function __construct( string $key, $value, $default, string $description, string $type ) {
		parent::__construct( $key, $value, $default, $description );
		$this->type = $type;
	}

	/**
	 * Creates JSON schema of the option.
	 *
	 * @since 3.7
	 *
	 * @return array The schema.
	 */
	protected function create_schema(): array {
		return array(
			'$schema'     => 'http://json-schema.org/draft-04/schema#',
			'title'       => $this->key(),
			'description' => $this->description,
			'type'        => 'array',
			'context'     => array( 'edit' ),
			'items' => array(
				'type'   => $this->type,
			),
		);
	}
}
