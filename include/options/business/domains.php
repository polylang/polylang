<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Error;
use WP_Syntex\Polylang\Options\Abstract_Option;
use WP_Syntex\Polylang\Options\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining single associative array of domain as value and language slug as key option.
 * /!\ Sanitization depends on `force_lang`: this option must be set AFTER `force_lang`.
 *
 * @since 3.7
 *
 * @phpstan-import-type SchemaType from \WP_Syntex\Polylang\Options\Abstract_Option
 */
class Domains extends Abstract_Option {
	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{
	 *     type: SchemaType,
	 *     patternProperties: non-empty-array<non-empty-string, array{type: SchemaType, format: 'uri'}>,
	 *     additionalProperties: false
	 * }
	 */
	protected function get_specific_schema(): array {
		return array(
			'type'                 => 'object', // Correspond to associative array in PHP, @see{https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types}.
			'patternProperties'    => array(
				'^[a-z_-]+$' => array( // Language slug as key.
					'type'   => 'string',
					'format' => 'uri',
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Sanitizes option's value.
	 * Can populate the `$errors` property with blocking and non-blocking errors: in case of non-blocking errors,
	 * the value is sanitized and can be stored.
	 *
	 * @since 3.7
	 *
	 * @param array   $value   Value to sanitize.
	 * @param Options $options All options.
	 * @return array|WP_Error The sanitized value. An instance of `WP_Error` in case of blocking error.
	 *
	 * @phpstan-return array<non-falsy-string, string>|WP_Error
	 */
	protected function sanitize( $value, Options $options ) {
		global $polylang;

		if ( empty( $polylang ) || ! $polylang->model->are_languages_ready() ) {
			// Access to global `$polylang` is required.
			_doing_it_wrong(
				__METHOD__,
				esc_html( sprintf( 'The option \'%s\' cannot be set before the hook \'pll_init\'.', $this->key() ) ),
				'3.7'
			);
			/** @phpstan-var array<non-falsy-string, string> */
			return $this->get();
		}

		// Sanitize new URLs.
		$value = parent::sanitize( $value, $options );

		if ( is_wp_error( $value ) ) {
			// Blocking error.
			return $value;
		}

		/** @phpstan-var array<non-falsy-string, string> $value */
		$all_values    = array(); // Previous and new values.
		$missing_langs = array(); // Lang names corresponding to the empty values.

		// Detect empty values, fill missing keys with previous values.
		foreach ( $polylang->model->get_languages_list() as $lang ) {
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
				'pll_empty_domains',
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
					'pll_invalid_domains',
					sprintf(
						/* translators: %s is a list of URLs. */
						_n( 'Polylang was unable to access the %s URL. Please check that the URL is valid.', 'Polylang was unable to access the %s URLs. Please check that the URLs are valid.', count( $failed_urls ), 'polylang' ),
						wp_sprintf_l( '%l', $failed_urls )
					),
					'warning'
				);
			}
		}

		/** @phpstan-var array<non-falsy-string, string> */
		return $all_values;
	}
}
