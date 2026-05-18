<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher\Settings;

use PLL_Links;
use PLL_Switcher;
use WP_Syntex\Polylang\Switcher\Layout;
use WP_Syntex\Polylang\Switcher\Legacy;

defined( 'ABSPATH' ) || exit;

/**
 * Class that holds all the language switcher's settings.
 *
 * @since 3.9
 */
class Settings {
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
		$settings = Legacy::maybe_filter_legacy_settings( $settings );

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
	 * @return Layout\Abstract_Layout|null
	 */
	public function get_switcher( PLL_Links $links ): ?Layout\Abstract_Layout {
		switch ( $this->layout ) {
			case 'horizontal':
			case 'vertical':
				return new Layout\Nav( $this, $links );

			case 'dropdown':
				return new Layout\Dropdown( $this, $links );

			case 'select':
				return new Layout\Select( $this, $links );

			default:
				return null;
		}
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
}
