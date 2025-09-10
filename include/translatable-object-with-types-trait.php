<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

/**
 * Trait to use for objects that can have one or more types.
 * This must be used with {@see PLL_Translatable_Object_With_Types_Interface}.
 *
 * @since 3.4
 */
trait PLL_Translatable_Object_With_Types_Trait {

	/**
	 * Fetches the IDs of the objects without language.
	 *
	 * @since 3.7
	 *
	 * @param int[] $language_ids List of language `term_taxonomy_id`.
	 * @param int   $limit        Max number of objects to return. `-1` to return all of them.
	 * @param array $args         An array of translated object types.
	 * @return string[]
	 *
	 * @phpstan-param array<positive-int> $language_ids
	 * @phpstan-param -1|positive-int $limit
	 * @phpstan-param array<string> $args
	 */
	protected function get_raw_objects_with_no_lang( array $language_ids, $limit, array $args = array() ) {
		global $wpdb;

		if ( empty( $args ) ) {
			$args = $this->get_translated_object_types();
		}

		$db = $this->get_db_infos();

		return $wpdb->get_col(
			$wpdb->prepare(
				sprintf(
					"SELECT %%i FROM %%i
					WHERE %%i NOT IN (
						SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN (%s)
					)
					AND %%i IN (%s)
					LIMIT %%d",
					implode( ',', array_fill( 0, count( $language_ids ), '%d' ) ),
					implode( ',', array_fill( 0, count( $args ), '%s' ) )
				),
				array_merge(
					array( $db['id_column'], $db['table'], $db['id_column'] ),
					$language_ids,
					array( $db['type_column'] ),
					$args,
					array( $limit >= 1 ? $limit : 4294967295 )
				)
			)
		);
	}

	/**
	 * Returns true if Polylang manages languages for this object type.
	 *
	 * @since 3.4
	 *
	 * @param string|string[] $object_type Object type (taxonomy name) name or array of object type names.
	 * @return bool
	 */
	public function is_translated_object_type( $object_type ) {
		$object_types = $this->get_translated_object_types( false );
		return ! empty( array_intersect( (array) $object_type, $object_types ) );
	}
}
