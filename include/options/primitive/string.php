<?php
/**
 * @package Polylang
 */

/**
 * Class defining single string option.
 *
 * @since 3.7
 */
class PLL_String_Option extends PLL_Abstract_Option {
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
			'type'        => 'string',
			'context'     => array( 'edit' ),
		);
	}
}
