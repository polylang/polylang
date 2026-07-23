<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Class that holds the setting field data when in a "widget".
 *
 * @since 3.9
 *
 * @phpstan-import-type FieldsData from Abstract_Fields
 */
class Widget extends Abstract_Fields {
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
		return array(
			'layout'                 => array(
				'label'   => __( 'Layout:', 'polylang' ),
				'choices' => array(
					'horizontal' => _x( 'Horizontal', 'layout', 'polylang' ),
					'vertical'   => _x( 'Vertical', 'layout', 'polylang' ),
					'dropdown'   => __( 'Dropdown', 'polylang' ),
					'select'     => __( 'Selector', 'polylang' ),
				),
			),
			'alignment'              => array(
				'label'   => __( 'Alignment:', 'polylang' ),
				'choices' => array(
					'left'      => _x( 'Left', 'alignment', 'polylang' ),
					'center'    => _x( 'Center', 'alignment', 'polylang' ),
					'right'     => _x( 'Right', 'alignment', 'polylang' ),
					'stretched' => _x( 'Stretched', 'alignment', 'polylang' ),
				),
			),
			'show_flags'             => array(
				'label'   => __( 'Display flags', 'polylang' ),
				'hide_if' => array(
					'layout' => 'select',
				),
			),
			'flag_aspect_ratio'      => array(
				'label'   => __( 'Flags aspect:', 'polylang' ),
				'choices' => array(
					'3:2' => __( 'Landscape', 'polylang' ),
					'1:1' => __( 'Square', 'polylang' ),
				),
				'hide_if' => array(
					'layout'     => 'select',
					'show_flags' => false,
				),
			),
			'show_labels'            => array(
				'label'   => __( 'Display labels:', 'polylang' ),
				'choices' => array(
					''      => __( 'None', 'polylang' ),
					'names' => __( 'Language names', 'polylang' ),
					'codes' => __( 'Language codes', 'polylang' ),
				),
				'hide_if' => array(
					'layout' => 'select',
				),
			),
			'force_home'             => array(
				'label' => __( 'Force link to front page', 'polylang' ),
			),
			'hide_current'           => array(
				'label' => __( 'Hide the current language', 'polylang' ),
			),
			'hide_if_no_translation' => array(
				'label' => __( 'Hide languages with no translation', 'polylang' ),
			),
		);
	}

	/**
	 * Adds some legacy keys that we want to keep in the database alongside the new ones in case of plugin rollback.
	 * Must not be called before `Abstract_Fields::filter()`.
	 *
	 * This would be useful in case a user rollbacks to a version < 3.9.
	 * Backward compatibility with Polylang < 3.9.
	 *
	 * @since 3.9
	 *
	 * @param array $settings Switcher settings.
	 * @return array
	 */
	public static function add_legacy_settings( array $settings ): array {
		$settings['dropdown']   = isset( $settings['layout'] ) && 'select' === $settings['layout'] ? 1 : 0;
		$settings['show_names'] = ! empty( $settings['show_labels'] ) ? 1 : 0;
		return $settings;
	}
}
