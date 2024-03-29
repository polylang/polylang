<?php
/**
 * @package Polylang
 */

/**
 * Class to manage single associative array of domain as value and language slug as key option.
 *
 * @since 3.7
 */
class PLL_Domains_Map_Option extends PLL_List_Option {
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
			'context'     => array( 'edit' ),
			'patternProperties'    => array(
				'^[a-z_-]+$' => array( // Language slug as key.
					'type'   => $this->type,
					'format' => 'uri',
				),
			),
			'additionalProperties' => false,
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

		// Don't redefine vip_safe_wp_remote_get() as it has not the same signature as wp_remote_get()
		$response = function_exists( 'vip_safe_wp_remote_get' ) ? vip_safe_wp_remote_get( esc_url_raw( $value ) ) : wp_remote_get( esc_url_raw( $value ) );
		$response_code = wp_remote_retrieve_response_code( $response );

		return 200 === $response_code;
	}
}
