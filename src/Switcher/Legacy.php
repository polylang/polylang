<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher;

use WP_Syntex\Polylang\Model\Languages;
use WP_Syntex\Polylang\Switcher\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility layer between new and legacy switchers.
 *
 * @since 3.9
 */
class Legacy {
	/**
	 * Legacy settings that don't exist anymore.
	 */
	private const ENTRIES = array(
		'dropdown'           => 1,
		'echo'               => 1,
		'show_names'         => 1,
		'display_names_as'   => 1,
		'raw'                => 1,
		'item_spacing'       => 1,
		'admin_render'       => 1,
		'admin_current_lang' => 1,
		'classes'            => 1,
	);

	/**
	 * Legacy default settings.
	 * Copied from `PLL_Switcher`.
	 */
	public const DEFAULTS = array(
		'dropdown'               => 0, // Display as list and not as dropdown.
		'echo'                   => 1, // Echoes the list.
		'hide_if_empty'          => 1, // Hides languages with no posts (or pages).
		'show_flags'             => 0, // Don't show flags.
		'show_names'             => 1, // Show language names.
		'display_names_as'       => 'name', // Display the language name.
		'force_home'             => 0, // Tries to find a translation.
		'hide_if_no_translation' => 0, // Don't hide the link if there is no translation.
		'hide_current'           => 0, // Don't hide the current language.
		'post_id'                => null, // Link to the translations of the current page.
		'raw'                    => 0, // Build the language switcher.
		'item_spacing'           => 'preserve', // Preserve whitespace between list items.
		'admin_render'           => 0, // Make the switcher in a frontend context.
		'admin_current_lang'     => null, // Use the global current language.
	);

	/**
	 * Converts new settings structure to the legacy one, then applies the deprecated filter `pll_the_languages_args`,
	 * then converts it back to new settings structure.
	 * This removes legacy settings.
	 *
	 * @since 3.9
	 *
	 * @param array $settings Settings in new structure.
	 * @return array
	 */
	public static function maybe_filter_legacy_settings( array $settings ): array {
		if ( ! has_filter( 'pll_the_languages_args' ) ) {
			if ( ! self::is_legacy_settings( $settings ) ) {
				return $settings;
			}

			return array_diff_key( self::convert_from_legacy_settings( $settings ), self::ENTRIES );
		}

		if ( ! self::is_legacy_settings( $settings ) ) {
			$settings = self::convert_to_legacy_settings( $settings );
		}

		/**
		 * Filter the arguments of the 'pll_the_languages' template tag.
		 *
		 * @since 1.5
		 * @since 3.9 Deprecated.
		 * @deprecated
		 *
		 * @param array $args
		 */
		$settings = apply_filters_deprecated(
			'pll_the_languages_args',
			array( $settings ),
			'3.9.0',
			'pll_language_switcher_settings'
		);

		return array_diff_key( self::convert_from_legacy_settings( $settings ), self::ENTRIES );
	}

	/**
	 * Returns the switcher's markup after applying the deprecated filter `pll_the_languages`.
	 * However, 100% backward compatibility is not ensured since the markup is different.
	 *
	 * @since 3.9
	 *
	 * @param string   $html     The switcher's markup.
	 * @param Settings $settings Instance of `Settings`.
	 * @return string
	 */
	public static function maybe_filter_legacy_markup( string $html, Settings $settings ): string {
		if ( ! has_filter( 'pll_the_languages' ) ) {
			return $html;
		}

		$legacy_settings = self::convert_to_legacy_settings( get_object_vars( $settings ) );

		/**
		 * Filter the whole HTML markup returned by the 'pll_the_languages' template tag.
		 *
		 * @since 0.8
		 * @since 3.9 Deprecated.
		 * @deprecated
		 *
		 * @param string $html HTML returned/outputted by the template tag.
		 * @param array  $args Arguments passed to the template tag.
		 */
		return (string) apply_filters_deprecated(
			'pll_the_languages',
			array( $html, $legacy_settings ),
			'3.9.0',
			'pll_language_switcher_output'
		);
	}

	/**
	 * Converts the new elements structure to the legacy one.
	 *
	 * @since 3.9
	 *
	 * @param \WP_Syntex\Polylang\Switcher\Element\Abstract_Element[] $elements  List of instances of `Abstract_Element`.
	 * @param Settings                                                $settings  Instance of `Settings`.
	 * @param Languages                                               $languages Languages model.
	 * @return array[]
	 */
	public static function convert_to_legacy_elements( array $elements, Settings $settings, Languages $languages ): array {
		$languages       = $languages->get_list();
		$keyed_languages = array_combine( array_column( $languages, 'slug' ), $languages );
		$data            = array();

		foreach ( $elements as $slug => $element ) {
			if ( ! isset( $keyed_languages[ $element->slug ] ) ) {
				// Should not happen.
				continue;
			}

			$language      = $keyed_languages[ $element->slug ];
			$data[ $slug ] = array(
				'id'             => $element->id,
				'order'          => $element->order,
				'slug'           => $element->slug,
				'locale'         => $element->locale,
				'is_rtl'         => 'rtl' === $element->direction,
				'name'           => 'codes' === $settings->show_labels ? $element->slug : $language->name,
				'url'            => $element->get_url(),
				'flag'           => ! empty( $settings->show_flags ) ? $element->flag : $language->get_display_flag_url(),
				'current_lang'   => $element->is_current,
				'no_translation' => ! $element->has_translations,
				'classes'        => $element->item_classes,
				'link_classes'   => $element->link_classes,
			);
		}

		return $data;
	}

	/**
	 * Tells if the given settings list contain legacy settings.
	 *
	 * @since 3.9
	 *
	 * @param array $settings Settings.
	 * @return bool
	 */
	private static function is_legacy_settings( array $settings ): bool {
		return ! empty( array_intersect_key( $settings, self::ENTRIES ) );
	}

	/**
	 * Converts the legacy structure to the new one.
	 * This preserves the legacy structure's keys.
	 *
	 * @since 3.9
	 *
	 * @param array $settings The settings.
	 * @return array
	 */
	private static function convert_from_legacy_settings( array $settings ): array {
		_deprecated_argument(
			Settings::class . '::__construct()',
			'3.9',
			sprintf(
				/* translators: %s is a function name. */
				esc_html__( "See %s's documentation.", 'polylang' ),
				'pll_the_languages()'
			)
		);

		if ( ! empty( $settings['dropdown'] ) && ! isset( $settings['show_wrapper'] ) ) {
			// `PLL_Walker_Dropdown` displays the wrapper (`<select>`).
			$settings['show_wrapper'] = true;
		}

		if ( isset( $settings['layout'], $settings['dropdown'] ) ) {
			// Set a new value to `layout` only if the value of `layout` and `dropdown` don't match.
			if ( ! empty( $settings['dropdown'] ) && 'select' !== $settings['layout'] ) {
				$settings['layout'] = 'select';
			} elseif ( empty( $settings['dropdown'] ) && 'select' === $settings['layout'] ) {
				$settings['layout'] = 'vertical';
			}
		} elseif ( ! isset( $settings['layout'] ) ) {
			$settings['layout'] = ! empty( $settings['dropdown'] ) ? 'select' : 'vertical';
		}

		if ( isset( $settings['show_names'] ) && empty( $settings['show_names'] ) ) {
			$settings['show_labels'] = '';
		} elseif ( isset( $settings['display_names_as'] ) && 'slug' === $settings['display_names_as'] ) {
			$settings['show_labels'] = 'codes';
		}

		foreach ( array( 'hide_if_empty', 'show_flags', 'force_home', 'hide_if_no_translation', 'hide_current' ) as $name ) {
			if ( isset( $settings[ $name ] ) ) {
				$settings[ $name ] = ! empty( $settings[ $name ] );
			}
		}

		if ( isset( $settings['item_spacing'] ) && 'discard' === $settings['item_spacing'] ) {
			$settings['preserve_spacing'] = false;
		}

		if ( ! empty( $settings['classes'] ) && is_array( $settings['classes'] ) ) {
			$settings['item_classes'] = $settings['classes'];
		}

		return $settings;
	}

	/**
	 * Converts the new structure to the legacy one.
	 * This preserves the new structure's keys.
	 *
	 * @since 3.9
	 *
	 * @param array $settings Settings in new structure.
	 * @return array
	 */
	private static function convert_to_legacy_settings( array $settings ): array {
		$args = self::DEFAULTS;

		if ( isset( $settings['layout'] ) && 'select' === $settings['layout'] ) {
			$args['dropdown'] = 1;
		}

		if ( isset( $settings['show_labels'] ) ) {
			if ( empty( $settings['show_labels'] ) ) {
				$args['show_names'] = 0;
			} elseif ( 'codes' === $settings['show_labels'] ) {
				$args['display_names_as'] = 'slug';
			}
		}

		foreach ( array( 'hide_if_empty', 'show_flags', 'force_home', 'hide_if_no_translation', 'hide_current' ) as $name ) {
			if ( isset( $settings[ $name ] ) ) {
				$args[ $name ] = (int) ! empty( $settings[ $name ] );
			}
		}

		if ( isset( $settings['preserve_spacing'] ) && ! $settings['preserve_spacing'] ) {
			$args['item_spacing'] = 'discard';
		}

		if ( ! empty( $settings['item_classes'] ) && is_array( $settings['item_classes'] ) ) {
			$args['classes'] = array_filter( $settings['item_classes'] );
		}

		return array_merge( $settings, $args );
	}
}
