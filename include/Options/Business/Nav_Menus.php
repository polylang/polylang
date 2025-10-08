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
	 * Adds information to the site health info array.
	 *
	 * @since 3.8
	 *
	 * @param Options $options An instance of the Options class providing additional configuration.
	 *
	 * @return array The updated site health information.
	 */
	public function get_site_health_info( Options $options ): array { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$current_theme = get_stylesheet();
		/** @phpstan-var NavMenusValue $nav_menus */
		$nav_menus     = $this->get();
		$fields        = array();
		if ( empty( $nav_menus[ $current_theme ] ) ) {
			return $fields;
		}
		foreach ( $nav_menus[ $current_theme ] as $location => $lang ) {
			if ( empty( $lang ) ) {
				/* translators: default value when a menu location is not used. */
				$lang = __( 'Not used', 'polylang' );
			}

			$fields[ $location ]['label'] = sprintf( 'menu: %s', $location );
			$fields[ $location ]['value'] = is_array( $lang ) ? $this->format_array_for_site_health_info( $lang ) : $lang;
		}
		return $fields;
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
