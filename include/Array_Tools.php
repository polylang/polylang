<?php
/**
 * The Polylang public API.
 *
 * @package Polylang
 */

namespace WP_Syntex\Polylang;

defined( 'ABSPATH' ) || exit;

/**
 * Array tools.
 *
 * @since 3.7
 */
class Array_Tools {
	/**
	 * Merges two arrays recursively.
	 * Unlike `array_merge_recursive()`, this method doesn't change the type of the values.
	 *
	 * @since 3.6
	 * @since 3.7 Moved from `PLL_WPML_Config`.
	 *            Became static.
	 *
	 * @param array $array1 Array to merge into.
	 * @param array $array2 Array to merge.
	 * @return array
	 */
	public static function merge_recursive( array $array1, array $array2 ): array {
		foreach ( $array2 as $key => $value ) {
			if ( is_array( $value ) && isset( $array1[ $key ] ) && is_array( $array1[ $key ] ) ) {
				$array1[ $key ] = self::merge_recursive( $array1[ $key ], $value );
			} else {
				$array1[ $key ] = $value;
			}
		}

		return $array1;
	}

	/**
	 * Sets an array sub-value.
	 *
	 * Similar to `$array['foo']['baz']['test'] = 12` but can trigger `ArrayAccess::offsetSet()`.
	 * Example:
	 *     Array_Tools::set_sub_value(
	 *         array( 'foo' => array( 'bar' => 4, 'baz' => 7 ) ),
	 *         array( 'foo', 'baz', 'test' ),
	 *         12
	 *     )
	 *     Result: array(
	 *         'foo' => array(
	 *             'bar' => 4,
	 *             'baz' => array(
	 *                 'test' => 12,
	 *             ),
	 *         ),
	 *     )
	 *
	 * @since 3.7
	 *
	 * @param array $array The array.
	 * @param array $keys  List of sub-keys.
	 * @param mixed $value The value.
	 * @return array
	 *
	 * @phpstan-param array<string|int> $keys
	 */
	public static function set_sub_value( array $array, array $keys, $value ): array {
		if ( empty( $keys ) ) {
			return $array;
		}

		$sub_key = array_shift( $keys );

		if ( empty( $keys ) ) {
			$array[ $sub_key ] = $value;
			return $array;
		}

		if ( ! isset( $array[ $sub_key ] ) || ! is_array( $array[ $sub_key ] ) ) {
			$array[ $sub_key ] = array();
		}

		$array[ $sub_key ] = self::set_sub_value( $array[ $sub_key ], $keys, $value );

		return $array;
	}

	/**
	 * Unsets an array sub-value.
	 *
	 * Similar to `unset( $array['foo']['baz']['test'] )` but can trigger `ArrayAccess::offsetUnset()`.
	 * Example:
	 *     Array_Tools::set_sub_value(
	 *         array( 'foo' => array( 'bar' => 4, 'baz' => array( 'test' => 12 ) ) ),
	 *         array( 'foo', 'baz', 'test' )
	 *     )
	 *     Result: array(
	 *         'foo' => array(
	 *             'bar' => 4,
	 *             'baz' => array(),
	 *         ),
	 *     )
	 *
	 * @since 3.7
	 *
	 * @param array $array The array.
	 * @param array $keys  List of sub-keys.
	 * @return array
	 *
	 * @phpstan-param array<string|int> $keys
	 */
	public static function unset_sub_value( array $array, array $keys ): array {
		if ( empty( $keys ) ) {
			return $array;
		}

		$sub_key = array_shift( $keys );

		if ( ! array_key_exists( $sub_key, $array ) ) {
			return $array;
		}

		if ( empty( $keys ) || ! is_array( $array[ $sub_key ] ) ) {
			unset( $array[ $sub_key ] );
			return $array;
		}

		return self::unset_sub_value( $array[ $sub_key ], $keys );
	}
}
