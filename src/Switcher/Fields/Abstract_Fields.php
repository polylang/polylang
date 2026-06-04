<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher\Fields;

use WP_Syntex\Polylang\Switcher\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class to use to manage setting field data.
 *
 * @since 3.9
 *
 * @phpstan-type FieldsData array<
 *     non-falsy-string,
 *     array{
 *         label: string,
 *         choices?: array<string>,
 *         hide_if?: array<string, string|bool>
 *     }
 * >
 */
abstract class Abstract_Fields {
	/**
	 * Returns setting field data available for the language switcher.
	 *
	 * @since 3.9
	 *
	 * @return array[]
	 *
	 * @phpstan-return FieldsData
	 */
	abstract public static function get(): array;

	/**
	 * Removes the legacy keys that were stored in the database for backward compatibility.
	 *
	 * @since 3.9
	 *
	 * @param array $raw_settings Raw settings.
	 * @return array
	 */
	public static function from_db( array $raw_settings ): array {
		if ( isset( $raw_settings['layout'] ) ) {
			// For backward compatibility, some legacy options are saved along the new ones.
			unset( $raw_settings['dropdown'], $raw_settings['show_names'] );
		}
		return $raw_settings;
	}

	/**
	 * Returns an array containing only the values corresponding to the setting fields, plus some legacy keys for
	 * backward compatibility.
	 *
	 * @since 3.9
	 *
	 * @param Settings $settings Switcher settings.
	 * @return array
	 */
	abstract public static function to_db( Settings $settings ): array;

	/**
	 * Returns an array containing only the values corresponding to the setting fields.
	 *
	 * @since 3.9
	 *
	 * @param Settings $settings Switcher settings.
	 * @return array
	 */
	public static function filter( Settings $settings ): array {
		$validated = array();

		foreach ( static::get() as $name => $data ) {
			$validated[ $name ] = $settings->$name;
		}

		return $validated;
	}
}
