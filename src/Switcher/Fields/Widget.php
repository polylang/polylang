<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher\Fields;

use WP_Syntex\Polylang\Switcher\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class that holds the setting field data when in a "widget".
 *
 * @since 3.9
 *
 * @phpstan-import-type FieldsData from Fields_Interface
 */
class Widget implements Fields_Interface {
	/**
	 * Returns setting field data available for the language switcher.
	 *
	 * @since 3.9
	 *
	 * @return array[]
	 *
	 * @phpstan-return FieldsData
	 */
	public static function get(): array {
		$defaults = Settings::get_defaults();
		return array(
			'layout'                 => array(
				'label'   => __( 'Layout:', 'polylang' ),
				'default' => $defaults['layout'],
				'choices' => array(
					'horizontal' => __( 'Horizontal', 'polylang' ),
					'vertical'   => __( 'Vertical', 'polylang' ),
					'dropdown'   => __( 'Dropdown', 'polylang' ),
					'select'     => __( 'Selector', 'polylang' ),
				),
			),
			'alignment'              => array(
				'label'   => __( 'Alignment:', 'polylang' ),
				'default' => $defaults['alignment'],
				'choices' => array(
					'left'      => _x( 'Left', 'alignment', 'polylang' ),
					'center'    => _x( 'Center', 'alignment', 'polylang' ),
					'right'     => _x( 'Right', 'alignment', 'polylang' ),
					'stretched' => _x( 'Stretched', 'alignment', 'polylang' ),
				),
			),
			'show_flags'             => array(
				'label'   => __( 'Display flags', 'polylang' ),
				'default' => $defaults['show_flags'],
				'hide_if' => array(
					'layout' => 'select',
				),
			),
			'flag_aspect_ratio'      => array(
				'label'   => __( 'Flags aspect ratio:', 'polylang' ),
				'default' => $defaults['flag_aspect_ratio'],
				'choices' => array(
					'3:2' => '3:2',
					'1:1' => '1:1',
				),
				'hide_if' => array(
					'layout'     => 'select',
					'show_flags' => false,
				),
			),
			'show_labels'            => array(
				'label'   => __( 'Display labels:', 'polylang' ),
				'default' => $defaults['show_labels'],
				'choices' => array(
					''      => __( 'No', 'polylang' ),
					'names' => __( 'Language names', 'polylang' ),
					'codes' => __( 'Language codes', 'polylang' ),
				),
				'hide_if' => array(
					'layout' => 'select',
				),
			),
			'force_home'             => array(
				'label'   => __( 'Force link to front page', 'polylang' ),
				'default' => $defaults['force_home'],
			),
			'hide_current'           => array(
				'label'   => __( 'Hide the current language', 'polylang' ),
				'default' => $defaults['hide_current'],
				'hide_if' => array(
					'layout' => 'select',
				),
			),
			'hide_if_no_translation' => array(
				'label'   => __( 'Hide languages with no translation', 'polylang' ),
				'default' => $defaults['hide_if_no_translation'],
			),
		);
	}

	/**
	 * Validates the given settings.
	 *
	 * @since 3.9
	 *
	 * @param array $settings Switcher settings.
	 * @return array
	 */
	public static function validate( array $settings ): array {
		$validated = array();

		foreach ( self::get() as $name => $data ) {
			if ( ! isset( $settings[ $name ] ) ) {
				$validated[ $name ] = $data['default'];
				continue;
			}
			$value = $settings[ $name ];

			if ( ! empty( $data['choices'] ) ) {
				$validated[ $name ] = isset( $data['choices'][ $value ] ) ? $value : $data['default'];
				continue;
			}
			$validated[ $name ] = ! empty( $value );
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
