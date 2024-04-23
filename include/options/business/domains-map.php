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
	 * Sanitizes option's value.
	 * Can return a `WP_Error` object in case of blocking sanitization error: the value must be rejected then.
	 * Can populate the `$errors` property with non-blocking sanitization errors: the value is sanitized and can be stored.
	 *
	 * @since 3.7
	 *
	 * @param array $value Value to filter.
	 * @return array|WP_Error
	 */
	protected function sanitize( $value ) {
		// Sanitize new URLs.
		$value = parent::sanitize( $value );

		if ( is_wp_error( $value ) ) {
			// Blocking error.
			return $value;
		}

		/** @var array $value */
		$all_values     = array(); // Previous and new values.
		$missing_langs  = array(); // Lang names corresponding to the empty values.
		$languages_list = PLL()->model->get_languages_list(); // FIX: PLL().

		// Detect empty values, fill missing keys with previous values.
		foreach ( $languages_list as $lang ) {
			if ( array_key_exists( $lang->slug, $value ) ) {
				// Use the new value.
				$all_values[ $lang->slug ] = $value[ $lang->slug ];
			} else {
				// Use previous value.
				$all_values[ $lang->slug ] = $this->value[ $lang->slug ] ?? '';
			}

			if ( empty( $all_values[ $lang->slug ] ) ) {
				// The value is empty.
				$missing_langs[] = $lang->name;
			}
		}

		if ( ! empty( $missing_langs ) ) { // TODO: && force_lang === 3.
			// Non-blocking error.
			$this->errors->add(
				'pll_invalid_domains',
				sprintf(
					/* translators: %s is a native language name. */
					_n( 'Please enter a valid URL for %s.', 'Please enter valid URLs for %s.', count( $missing_langs ), 'polylang' ),
					wp_sprintf_l( '%l', $missing_langs )
				)
			);
		}

		// Ping all URLs to make sure they are valid.
		// TODO: if force_lang > 1.
		$options     = array( $this->key() => $all_values ); // FIX: all options.
		$links_model = ( new PLL_Model( $options ) )->get_links_model();

		foreach ( $languages_list as $lang ) {
			$url = add_query_arg( 'deactivate-polylang', 1, $links_model->home_url( $lang ) );
			// Don't redefine vip_safe_wp_remote_get() as it has not the same signature as wp_remote_get()
			$response      = function_exists( 'vip_safe_wp_remote_get' ) ? vip_safe_wp_remote_get( esc_url_raw( $url ) ) : wp_remote_get( esc_url_raw( $url ) );
			$response_code = wp_remote_retrieve_response_code( $response );

			if ( 200 === $response_code ) {
				continue;
			}

			// Non-blocking error.
			$this->errors->add(
				sprintf( "pll_invalid_domain_{$lang->slug}" ),
				sprintf(
					/* translators: %s is an url */
					__( 'Polylang was unable to access the %s URL. Please check that the URL is valid.', 'polylang' ),
					$url
				)
			);
		}

		return $all_values;
	}
}
