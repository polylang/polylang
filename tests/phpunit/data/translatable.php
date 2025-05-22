<?php
defined( 'ABSPATH' ) || exit;

/**
 * Translatable custom table.
 */
class PLLTest_Translatable extends PLL_Translatable_Object {
	protected $key;

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param PLL_Model $model Instance of `PLL_Model`.
	 * @param string    $key   Optional. A custom key. Default is `'foo'`.
	 */
	public function __construct( PLL_Model $model, string $key = 'foo' ) {
		$this->tax_language = "{$key}_language";
		$this->object_type  = $key;
		$this->type         = $key;
		$this->cache_type   = "{$key}s";
		$this->key          = $key;

		parent::__construct( $model );
	}

	/**
	 * Returns database-related information that can be used in some of this class methods.
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
			'table'         => $GLOBALS['wpdb']->prefix . $this->key,
			'id_column'     => 'id',
			'default_alias' => $GLOBALS['wpdb']->prefix . $this->key,
		);
	}
}
