<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Language_Switcher\Settings;

use PLL_Links;
use PLL_Switcher;
use WP_Syntex\Polylang\Language_Switcher\Switchers;

defined( 'ABSPATH' ) || exit;

/**
 * Class that holds all the language switcher's settings.
 *
 * @since 3.9
 */
class Settings {
	private const LEGACY_ENTRIES = array(
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
	 * @var string
	 *
	 * @phpstan-var 'horizontal'|'vertical'|'dropdown'|'select'
	 */
	public string $layout = 'vertical';

	/**
	 * No default value here because it depends on `is_rtl()`. see `self::get_defaults()`.
	 *
	 * @var string
	 *
	 * @phpstan-var 'left'|'center'|'right'|'stretched'
	 */
	public string $alignment;

	/**
	 * @var bool
	 */
	public bool $show_flags = false;

	/**
	 * @var string
	 *
	 * @phpstan-var '3:2'|'1:1'
	 */
	public string $flag_aspect_ratio = '3:2';

	/**
	 * @var string
	 *
	 * @phpstan-var ''|'names'|'codes'
	 */
	public string $show_labels = 'names';

	/**
	 * @var bool
	 */
	public bool $hide_current = false;

	/**
	 * @var bool
	 */
	public bool $hide_if_empty = true;

	/**
	 * @var bool
	 */
	public bool $hide_if_no_translation = false;

	/**
	 * @var bool
	 */
	public bool $force_home = false;

	/**
	 * @var int
	 */
	public int $post_id = 0;

	/**
	 * @var bool
	 */
	public bool $preserve_spacing = true;

	/**
	 * @var bool
	 */
	public bool $show_wrapper = true;

	/**
	 * @var array
	 *
	 * @phpstan-var non-empty-string[]
	 */
	public array $wrapper_classes = array();

	/**
	 * @var array
	 *
	 * @phpstan-var non-empty-string[]
	 */
	public array $item_classes = array();

	/**
	 * @var array
	 *
	 * @phpstan-var non-empty-string[]
	 */
	public array $link_classes = array();

	/**
	 * @var string
	 */
	public string $unique_id = '';

	/**
	 * @var int
	 */
	private static int $increment = 0;

	/**
	 * Constructor.
	 *
	 * @since 3.9
	 *
	 * @param array $settings {
	 *     Optional switcher settings.
	 *
	 *     @type string   $layout                 Layout of the switcher. Possible values are `horizontal`, `vertical`,
	 *                                            `dropdown`, and `select`. Default is `vertical`.
	 *     @type string   $alignment              Alignment of the items. Possible values are `left`, `center`, `right`,
	 *                                            `stretched`. Default is `left` or `right`, depending on `is_rtl()`.
	 *     @type bool     $show_wrapper           Display the wrapper or not. Default is `true`.
	 *     @type bool     $show_flags             Display the flags or not. Default is `false`.
	 *     @type string   $flag_aspect_ratio      Flags aspect ratio. Possible values are `3:2` and `1:1`. Default is `3:2`.
	 *     @type string   $show_labels            Display the labels. Possible values are an empty string (no labels),
	 *                                            `names` (language names), `codes` (languages codes). Default is `names`.
	 *     @type bool     $hide_if_empty          Hide languages that don't have any posts. Default is `true`.
	 *     @type bool     $hide_if_no_translation Hide languages that don't have a translation. Default is `false`.
	 *     @type bool     $hide_current           Hide the current language. Default is `false`.
	 *     @type bool     $force_home             Force elements to link to the home pages instead of the translations.
	 *                                            Default is `false`.
	 *     @type int      $post_id                Build the links according to the translations of the given post ID.
	 *                                            Default is `0`.
	 *     @type bool     $preserve_spacing       Preserve or discard white space characters between tags.
	 *                                            Default is `true` (preserve).
	 *     @type string[] $wrapper_classes        HTML classes to add to the wrapper. Default is an empty array.
	 *     @type string[] $item_classes           HTML classes to add to each item. Default is an empty array.
	 *     @type string[] $link_classes           HTML classes to add to each link. Default is an empty array.
	 *     @type string   $unique_id              A unique identifier. Default is an empty string.
	 * }
	 */
	public function __construct( array $settings ) {
		$settings = $this->maybe_convert_and_filter_legacy_settings( $settings );

		/**
		 * Filter the language switcher settings.
		 *
		 * @since 3.9
		 *
		 * @param array $settings Settings.
		 */
		$settings = apply_filters( 'pll_language_switcher_settings', $settings );

		foreach ( $this->validate( $settings ) as $name => $value ) {
			$this->$name = $value;
		}

		if ( '' === $this->unique_id ) {
			++self::$increment;
			$this->unique_id = 'pll-switcher-' . self::$increment;
		}
	}

	/**
	 * Returns the public default values.
	 *
	 * @since 3.9
	 *
	 * @return array
	 */
	public static function get_defaults(): array {
		$properties = array_diff_key( get_class_vars( self::class ), array( 'increment' => 0 ) );

		$properties['alignment'] = is_rtl() ? 'right' : 'left';

		return $properties;
	}

	/**
	 * Returns an instance of the switcher.
	 *
	 * @since 3.9
	 *
	 * @param PLL_Links $links Instance of `PLL_Links`.
	 * @return Switchers\Abstract_Switcher|null
	 */
	public function get_switcher( PLL_Links $links ): ?Switchers\Abstract_Switcher {
		switch ( $this->layout ) {
			case 'horizontal':
			case 'vertical':
				return new Switchers\Nav( $this, $links );

			case 'dropdown':
				return new Switchers\Dropdown( $this, $links );

			case 'select':
				return new Switchers\Select( $this, $links );

			default:
				return null;
		}
	}

	/**
	 * Returns the values as an array after converting them to the legacy format.
	 *
	 * @since 3.9
	 *
	 * @return array
	 */
	public function get_legacy(): array {
		return $this->convert_to_legacy( get_object_vars( $this ) );
	}

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
	private function maybe_convert_and_filter_legacy_settings( array $settings ): array {
		if ( ! has_filter( 'pll_the_languages_args' ) ) {
			if ( ! $this->is_legacy( $settings ) ) {
				return $settings;
			}

			return array_diff_key( $this->convert_from_legacy( $settings ), self::LEGACY_ENTRIES );
		}

		if ( ! $this->is_legacy( $settings ) ) {
			$settings = $this->convert_to_legacy( $settings );
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
		$settings = apply_filters_deprecated( 'pll_the_languages_args', array( $settings ), '3.9.0', 'pll_language_switcher_settings' );

		return array_diff_key( $this->convert_from_legacy( $settings ), self::LEGACY_ENTRIES );
	}

	/**
	 * Validates the settings (value and type).
	 * This removes additional keys.
	 *
	 * @since 3.9
	 *
	 * @param array $settings Switcher settings.
	 * @return array
	 */
	protected function validate( array $settings ): array {
		$validated = self::get_defaults();
		$choices   = array(
			'layout'                 => array( 'horizontal', 'vertical', 'dropdown', 'select' ),
			'alignment'              => array( 'left', 'center', 'right', 'stretched' ),
			'flag_aspect_ratio'      => array( '3:2', '1:1' ),
			'show_labels'            => array( '', 'names', 'codes' ),
		);

		foreach ( $choices as $key => $setting_choices ) {
			if ( isset( $settings[ $key ] ) && in_array( $settings[ $key ], $setting_choices, true ) ) {
				$validated[ $key ] = $settings[ $key ];
			}
		}

		foreach ( array( 'show_flags', 'hide_current', 'hide_if_empty', 'hide_if_no_translation', 'force_home', 'preserve_spacing', 'show_wrapper' ) as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$validated[ $key ] = ! empty( $settings[ $key ] );
			}
		}

		if ( ! empty( $settings['post_id'] ) && is_numeric( $settings['post_id'] ) ) {
			$validated['post_id'] = absint( $settings['post_id'] );
		}

		foreach ( array( 'wrapper_classes', 'item_classes', 'link_classes' ) as $key ) {
			if ( isset( $settings[ $key ] ) && is_array( $settings[ $key ] ) ) {
				$validated[ $key ] = array_filter(
					$settings[ $key ],
					static function ( $class ) {
						return is_string( $class ) && ! empty( trim( $class ) );
					}
				);
			}
		}

		if ( ! empty( $settings['unique_id'] ) && is_string( $settings['unique_id'] ) ) {
			$validated['unique_id'] = sanitize_key( $settings['unique_id'] );
		}

		// Mandatory settings for the `select` layout.
		if ( 'select' === $validated['layout'] ) {
			$validated['show_flags']   = false;
			$validated['show_labels']  = 'names';
			$validated['hide_current'] = false;
		}

		// Make sure something is displayed.
		if ( ! $validated['show_flags'] && empty( $validated['show_labels'] ) ) {
			$validated['show_labels'] = 'names';
		}

		return $validated;
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
	protected function convert_from_legacy( array $settings ): array {
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
	protected function convert_to_legacy( array $settings ): array {
		$args = PLL_Switcher::DEFAULTS;

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

	/**
	 * Tells if the given settings list contain legacy settings.
	 *
	 * @since 3.9
	 *
	 * @param array $settings Settings.
	 * @return bool
	 */
	protected function is_legacy( array $settings ): bool {
		return ! empty( array_intersect_key( $settings, self::LEGACY_ENTRIES ) );
	}
}
