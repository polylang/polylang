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
	 * Returns SQL query that fetches the IDs of the objects without language.
	 *
	 * @since 3.4
	 *
	 * @param int[] $language_ids List of language `term_taxonomy_id`.
	 * @param int   $limit        Max number of objects to return. `-1` to return all of them.
	 * @param array $args         An array of translated object types.
	 * @return string
	 *
	 * @phpstan-param array<positive-int> $language_ids
	 * @phpstan-param -1|positive-int $limit
	 * @phpstan-param array<string> $args
	 */
	protected function get_objects_with_no_lang_sql( array $language_ids, $limit, array $args = array() ) {
		if ( empty( $args ) ) {
			$args = $this->get_translated_object_types();
		}

		$db = $this->get_db_infos();

		return sprintf(
			"SELECT {$db['table']}.{$db['id_column']} FROM {$db['table']}
			WHERE {$db['table']}.{$db['id_column']} NOT IN (
				SELECT object_id FROM {$GLOBALS['wpdb']->term_relationships} WHERE term_taxonomy_id IN (%s)
			)
			AND {$db['type_column']} IN (%s)
			%s",
			PLL_Db_Tools::prepare_values_list( $language_ids ),
			PLL_Db_Tools::prepare_values_list( $args ),
			$limit >= 1 ? sprintf( 'LIMIT %d', $limit ) : ''
		);
	}

	/**
	 * Returns true if Polylang manages languages for this object type.
	 *
	 * @since 3.4
	 *
	 * @param string|string[] $object_type Object type (taxonomy name) name or array of object type names.
	 * @return bool
	 *
	 * @phpstan-param non-empty-string|non-empty-string[] $object_type
	 */
	public function is_translated_object_type( $object_type ) {
		$object_types = $this->get_translated_object_types( false );
		return ! empty( array_intersect( (array) $object_type, $object_types ) );
	}
}
