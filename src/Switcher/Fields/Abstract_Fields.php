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
	 * Removes the legacy keys that were stored in the database alongside the new ones in case of plugin rollback.
	 *
	 * This method is part of the plan to support rollbacks to versions < 3.9.
	 * Backward compatibility with Polylang < 3.9.
	 *
	 * @since 3.9
	 *
	 * @param array $raw_settings Raw settings.
	 * @return array
	 */
	public static function remove_legacy_settings( array $raw_settings ): array {
		if ( isset( $raw_settings['layout'] ) ) {
			unset( $raw_settings['dropdown'], $raw_settings['show_names'] );
		}
		return $raw_settings;
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
	abstract public static function add_legacy_settings( array $settings ): array;

	/**
	 * Returns an array containing ONLY the values corresponding to the setting fields.
	 *
	 * @since 3.9
	 *
	 * @param Settings $settings Switcher settings.
	 * @return array
	 */
	public static function filter( Settings $settings ): array {
		return array_intersect_key( get_object_vars( $settings ), static::get() );
	}
}
