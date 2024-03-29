<?php
/**
 * @package Polylang
 */

/**
 * Class to manage object types list option.
 *
 * @since 3.7
 */
abstract class PLL_Abstract_Object_Types_List_Option extends PLL_List_Option {
	/**
	 * List of non-core, public post types.
	 *
	 * @var string[]|null
	 */
	private $object_types;

	/**
	 * Creates JSON schema of the option.
	 *
	 * @since 3.7
	 *
	 * @return array The schema.
	 */
	public function create_schema(): array {
		return array(
			'$schema'     => 'http://json-schema.org/draft-04/schema#',
			'title'       => $this->key(),
			'description' => $this->description,
			'type'        => 'array',
			'items' => array(
				'type'   => $this->type,
			),
		);
	}

	/**
	 * Validates option's value.
	 *
	 * @since 3.7
	 *
	 * @param mixed $value Value to validate.
	 * @return bool True if the value is valid, false otherwise.
	 */
	protected function validate( $value ): bool {
		if ( ! parent::validate( $value ) ) {
			return false;
		}

		if ( null === $this->object_types ) {
			$this->object_types = $this->get_object_types();
		}

		return in_array( $value, $this->object_types, true );
	}

	/**
	 * Returns non-core, public object types.
	 *
	 * @since 3.7
	 *
	 * @return string[] Object type names list.
	 */
	abstract protected function get_object_types(): array;
}
