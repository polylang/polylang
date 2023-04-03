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
	 * Constructor.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Model $model Instance of `PLL_Model`.
	 */
	public function __construct( PLL_Model &$model ) {
		$this->db = array(
			'table'         => $GLOBALS['wpdb']->prefix . 'foo',
			'id_column'     => 'id',
			'default_alias' => $GLOBALS['wpdb']->prefix . 'foo',
		);

		parent::__construct( $model );
	}
}
