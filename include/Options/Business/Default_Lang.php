<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Error;
use WP_Syntex\Polylang\Model\Languages;
use WP_Syntex\Polylang\Options\Options;
use WP_Syntex\Polylang\Options\Primitive\Abstract_String;
use WP_Syntex\Polylang\Options\Primitive\Site_Health_String_Trait;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining language slug string option.
 *
 * @since 3.7
 */
class Default_Lang extends Abstract_String {
	use site_health_string_trait;

	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'default_lang'
	 */
	public static function key(): string {
		return 'default_lang';
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: 'string', pattern: Languages::SLUG_PATTERN}
	 */
	protected function get_data_structure(): array {
		$string_schema            = parent::get_data_structure();
		$string_schema['pattern'] = Languages::SLUG_PATTERN;

		return $string_schema;
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 * @since 3.7
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( 'Slug of the default language.', 'polylang' );
	}

	/**
	 * Sanitizes option's value.
	 * Can populate the `$errors` property with blocking and non-blocking errors: in case of non-blocking errors,
	 * the value is sanitized and can be stored.
	 *
	 * @since 3.7
	 *
	 * @param string  $value   Value to sanitize.
	 * @param Options $options All options.
	 * @return string|WP_Error The sanitized value. An instance of `WP_Error` in case of error.
	 */
	protected function sanitize( $value, Options $options ) {
		$value = parent::sanitize( $value, $options );

		if ( is_wp_error( $value ) ) {
			return $value;
		}

		/** @var string $value */
		if ( ! get_term_by( 'slug', $value, 'language' ) ) {
			return new WP_Error( 'pll_invalid_language', sprintf( 'The language slug \'%s\' is not a valid language.', $value ) );
		}

		return $value;
	}
}
