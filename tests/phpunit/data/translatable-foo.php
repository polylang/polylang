<?php
defined( 'ABSPATH' ) || exit;

/**
 * Translatable custom table.
 */
class PLLTest_Translatable_Foo extends PLL_Translatable_Object {

	protected $tax_language = 'foo_language';
	protected $object_type = 'foo';
	protected $type = 'foo';
	protected $cache_type = 'foos';

	/**
	 * Returns database-related informations that can be used in some of this class methods.
	 * These are specific to the table containing the objects.
	 *
	 * @since 3.4.3
	 *
	 * @return string[] {
	 *     @type string $table         Name of the table.
	 *     @type string $id_column     Name of the column containing the object's ID.
	 *     @type string $default_alias Default alias corresponding to the object's table.
	 * }
	 * @phpstan-return DBInfo
	 */
	protected function get_db_infos() {
		return array(
			'table'         => $GLOBALS['wpdb']->prefix . 'foo',
			'id_column'     => 'id',
			'default_alias' => $GLOBALS['wpdb']->prefix . 'foo',
		);
	}
}
