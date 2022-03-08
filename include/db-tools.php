<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit; // @phpstan-ignore-line

/**
 * Small set of tools to work with the database.
 *
 * @since 3.2
 */
class PLL_Db_Tools {

	/**
	 * Changes an array of values into a comma separated list, ready to be used in a `IN ()` clause.
	 * Only string and integers and supported for now.
	 *
	 * @since 3.2
	 *
	 * @param  array<int|string> $values An array of values.
	 * @return string                    A comma separated list of values.
	 */
	public static function prepare_values_list( $values ) {
		$values = array_map( array( __CLASS__, 'prepare_value' ), (array) $values );

		return implode( ',', $values );
	}

	/**
	 * Wraps a value in escaped double quotes or casts as an integer.
	 * Only string and integers and supported for now.
	 *
	 * @since  3.2
	 * @global wpdb $wpdb
	 *
	 * @param  int|string $value A value.
	 * @return int|string
	 */
	public static function prepare_value( $value ) {
		if ( ! is_numeric( $value ) ) {
			return $GLOBALS['wpdb']->prepare( '%s', $value );
		}

		return (int) $value;
	}
}
