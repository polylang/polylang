<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class defining single associative array of domain as value and language slug as key option.
 * /!\ Sanitization depends on `force_lang`: this option must be set AFTER `force_lang`.
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
	 * Can populate the `$errors` property with blocking and non-blocking errors: in case of non-blocking errors,
	 * the value is sanitized and can be stored.
	 *
	 * @since 3.7
	 *
	 * @param mixed       $value   Value to sanitize.
	 * @param PLL_Options $options All options.
	 * @return mixed The sanitized value. The previous value in case of blocking error.
	 */
	protected function sanitize( $value, PLL_Options $options ) {
		// Sanitize new URLs.
		$value = parent::sanitize( $value, $options );

		/** @var array $value */
		if ( $this->has_blocking_errors() ) {
			// Blocking error.
			return $value;
		}

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

		if ( 3 === $options->get( 'force_lang' ) && ! empty( $missing_langs ) ) {
			// Non-blocking error.
			$this->errors->add(
				'pll_invalid_domains',
				sprintf(
					/* translators: %s is a list of native language names. */
					_n( 'Please enter a valid URL for %s.', 'Please enter valid URLs for %s.', count( $missing_langs ), 'polylang' ),
					wp_sprintf_l( '%l', $missing_langs )
				),
				'warning'
			);
		}

		// Ping all URLs to make sure they are valid.
		if ( $options->get( 'force_lang' ) > 1 ) {
			$failed_urls = array();

			foreach ( array_filter( $all_values ) as $url ) {
				$url = add_query_arg( 'deactivate-polylang', 1, $url );
				// Don't redefine vip_safe_wp_remote_get() as it has not the same signature as wp_remote_get().
				$response = function_exists( 'vip_safe_wp_remote_get' ) ? vip_safe_wp_remote_get( $url ) : wp_remote_get( $url );

				if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
					$failed_urls[] = $url;
				}
			}

			if ( ! empty( $failed_urls ) ) {
				// Non-blocking error.
				$this->errors->add(
					sprintf( 'pll_invalid_domains' ),
					sprintf(
						/* translators: %s is a list of URLs. */
						_n( 'Polylang was unable to access the %s URL. Please check that the URL is valid.', 'Polylang was unable to access the %s URLs. Please check that the URLs are valid.', count( $failed_urls ), 'polylang' ),
						wp_sprintf_l( '%l', $failed_urls )
					),
					'warning'
				);
			}
		}

		return $all_values;
	}
}
