<?php
/**
 * @package Polylang
 */

/**
 * Class to manage single associative array option.
 *
 * @since 3.7
 */
class PLL_Map_Option extends PLL_List_Option {
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
			'type'        => 'object', // Correspond to associative array in PHP, @see{https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types}.
			'patternProperties'    => array(
				'^\\w+$' => array( // Any word characters as key.
					'type' => $this->type,
				),
			),
			'additionalProperties' => false,
		);
	}
}
