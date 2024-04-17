<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class defining single associative array of domain as value and language slug as key option.
 *
 * @since 3.7
 *
 * @phpstan-import-type Schema from PLL_Abstract_Option
 */
class PLL_Domains_Map_Option extends PLL_Map_Option {
	/**
	 * Creates JSON schema of the option.
	 *
	 * @since 3.7
	 *
	 * @return array The schema.
	 *
	 * @phpstan-return Schema
	 */
	protected function create_schema(): array {
		$map_schema                      = parent::create_schema();
		$map_schema['patternProperties'] = array(
			'^[a-z_-]+$' => array( // Language slug as key.
				'type'   => $this->type,
				'format' => 'uri',
			),
		);
		return $map_schema;
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

		/** @var array $value */
		foreach ( $value as $url ) {
			// Don't redefine vip_safe_wp_remote_get() as it has not the same signature as wp_remote_get().
			$response = function_exists( 'vip_safe_wp_remote_get' ) ? vip_safe_wp_remote_get( esc_url_raw( $url ) ) : wp_remote_get( esc_url_raw( $url ) );

			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return false;
			}
		}

		return true;
	}
}
