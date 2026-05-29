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
