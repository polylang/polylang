<?php
/**
 * @package Polylang
 */

/**
 * Class to manage single boolean option.
 * Note that for historic reason, boolean are stored as 0 or 1.
 *
 * @since 3.7
 */
class PLL_Boolean_Option extends PLL_Abstract_Option {
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
			'type'        => 'boolean',
			'context'     => array( 'edit' ),
		);
	}
}
