<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Interface to use to manage setting field data.
 *
 * @since 3.9
 *
 * @phpstan-type FieldsData array<
 *     non-falsy-string,
 *     array{
 *         label: string,
 *         default: string|bool,
 *         choices?: array<string>,
 *         hide_if?: array<string, string|bool>
 *     }
 * >
 */
interface Fields_Interface {
	/**
	 * Returns setting field data available for the language switcher.
	 *
	 * @since 3.9
	 *
	 * @return array[]
	 *
	 * @phpstan-return FieldsData
	 */
	public static function get(): array;

	/**
	 * Validates the given settings.
	 *
	 * @since 3.9
	 *
	 * @param array $settings Switcher settings.
	 * @return array
	 */
	public static function validate( array $settings ): array;
}
