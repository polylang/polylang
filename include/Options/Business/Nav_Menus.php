<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Error;
use WP_Syntex\Polylang\Options\Abstract_Option;
use WP_Syntex\Polylang\Options\Options;
use WP_Syntex\Polylang\Model\Languages;


defined( 'ABSPATH' ) || exit;

/**
 * Class defining navigation menus array option.
 *
 * @since 3.7
 *
 * @phpstan-type NavMenusValue array<
 *     non-falsy-string,
 *     array<
 *         non-falsy-string,
 *         array<non-falsy-string, int<0, max>>
 *     >
 * >
 */
class Nav_Menus extends Abstract_Option {
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'nav_menus'
	 */
	public static function key(): string {
		return 'nav_menus';
	}

	/**
	 * Returns the default value.
	 *
	 * @since 3.7
	 *
	 * @return array
	 */
	protected function get_default() {
		return array();
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 */
	protected function get_data_structure(): array {
		return array(
			'type'                 => 'object', // Correspond to associative array in PHP, @see{https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types}.
			'patternProperties'    => array(
				'[^\/:<>\*\?"\|]+' => array( // Excludes invalid directory name characters @see https://developer.wordpress.org/reference/classes/wp_rest_themes_controller/register_routes/
					'type'                 => 'object',
					'patternProperties'    => array(
						'[\w-]+' => array( // Accepted characters for menu locations @see https://developer.wordpress.org/reference/classes/wp_rest_menu_locations_controller/register_routes/
							'type'              => 'object',
							'patternProperties' => array(
								Languages::SLUG_PATTERN => array( // Language slug as key.
									'type'    => 'integer',
									'minimum' => 0, // A post ID.
								),
							),
							'additionalProperties' => false,
						),
					),
					'additionalProperties' => false,
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Prepares a value before validation.
	 *
	 * @since 3.7.2
	 *
	 * @param mixed $value Value to format.
	 * @return mixed
	 */
	protected function prepare( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		$cur_theme = get_option( 'stylesheet' );

		foreach ( $value as $theme => &$menus_by_loc ) {
			if ( ! is_array( $menus_by_loc ) ) {
				if ( $theme !== $cur_theme ) {
					// Not the current theme: prevent a validation error.
					unset( $value[ $theme ] );
				}
				// Current theme: let the validation process trigger an error.
				continue;
			}
			foreach ( $menus_by_loc as $location => &$menus_by_lang ) {
				if ( ! is_array( $menus_by_lang ) ) {
					if ( $theme !== $cur_theme ) {
						// Not the current theme: prevent a validation error.
						unset( $menus_by_loc[ $location ] );
					}
					// Current theme: let the validation process trigger an error.
					continue;
				}
				foreach ( $menus_by_lang as &$menu_id ) {
					if ( empty( $menu_id ) ) {
						// Prevent a useless validation error.
						$menu_id = 0;
					} elseif ( ! is_numeric( $menu_id ) || $menu_id < 0 ) {
						if ( $theme !== $cur_theme ) {
							// Not the current theme: prevent a validation error.
							$menu_id = 0;
						}
						// Current theme: let the validation process trigger an error.
					}
				}
			}
		}

		return $value;
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
	 * @phpstan-return NavMenusValue|WP_Error
	 */
	protected function sanitize( $value, Options $options ) {
		// Sanitize new value.
		$value = parent::sanitize( $value, $options );

		if ( is_wp_error( $value ) ) {
			// Blocking error.
			return $value;
		}

		/** @phpstan-var NavMenusValue $value */
		if ( empty( $value ) ) {
			// Nothing to validate.
			return $value;
		}

		$all_langs      = array();
		$language_terms = wp_list_pluck( $this->get_language_terms(), 'slug' );

		foreach ( $value as $theme_slug => $menu_ids_by_location ) {
			foreach ( $menu_ids_by_location as $location => $menu_ids ) {
				// Make sure the language slugs correspond to an existing language.
				$value[ $theme_slug ][ $location ] = array();

				foreach ( $language_terms as $lang_slug ) {
					if ( ! empty( $menu_ids[ $lang_slug ] ) ) {
						$value[ $theme_slug ][ $location ][ $lang_slug ] = $menu_ids[ $lang_slug ];
					}
				}

				// Detect unknown languages.
				$all_langs = array_merge( $all_langs, $menu_ids );
			}
		}

		/** @phpstan-var NavMenusValue $value */
		$unknown_langs = array_diff_key( $all_langs, array_flip( $language_terms ) );

		// Detect invalid language slugs.
		if ( ! empty( $unknown_langs ) ) {
			// Non-blocking error.
			$this->add_unknown_languages_warning( array_keys( $unknown_langs ) );
		}

		return $value;
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 * @since 3.7
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( 'Translated navigation menus for each theme.', 'polylang' );
	}
}
