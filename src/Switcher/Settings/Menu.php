<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class that holds the language switcher's settings for a menu.
 *
 * @since 3.9
 */
class Menu extends Settings {
	/**
	 * @var string
	 *
	 * @phpstan-var 'horizontal'|'dropdown'
	 */
	public string $layout = 'horizontal';

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
		if ( ! isset( $settings['layout'] ) || in_array( $settings['layout'], array( 'horizontal', 'dropdown' ), true ) ) {
			return parent::validate( $settings );
		}

		if ( 'select' === $settings['layout'] ) {
			$settings['layout'] = 'dropdown';
		} else {
			$settings['layout'] = 'horizontal';
		}

		return parent::validate( $settings );
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
		$settings = parent::convert_from_legacy( $settings );

		$settings['layout'] = ! empty( $settings['dropdown'] ) ? 'dropdown' : 'horizontal';

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
		$args = parent::convert_to_legacy( $settings );

		if ( isset( $settings['layout'] ) && 'dropdown' === $settings['layout'] ) {
			$args['dropdown'] = 1;
		} else {
			$args['dropdown'] = 0;
		}

		return $args;
	}
}
