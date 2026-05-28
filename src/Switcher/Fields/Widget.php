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
					'horizontal' => __( 'Horizontal', 'polylang' ),
					'vertical'   => __( 'Vertical', 'polylang' ),
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
				'label'   => __( 'Flags aspect ratio:', 'polylang' ),
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
				'label' => __( 'Force link to front page', 'polylang' ),
			),
			'hide_current'           => array(
				'label'   => __( 'Hide the current language', 'polylang' ),
				'hide_if' => array(
					'layout' => 'select',
				),
			),
			'hide_if_no_translation' => array(
				'label' => __( 'Hide languages with no translation', 'polylang' ),
			),
		);
	}
}
