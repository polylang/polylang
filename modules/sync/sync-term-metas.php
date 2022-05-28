<?php
/**
 * @package Polylang
 */

/**
 * A class to manage copy and synchronization of term metas
 *
 * @since 2.3
 */
class PLL_Sync_Term_Metas extends PLL_Sync_Metas {

	/**
	 * Constructor.
	 *
	 * @since 2.3
	 * @since 3.3 Changed method's signature.
	 *
	 * @param  PLL_Model $model Instance of PLL_Model.
	 * @return void
	 */
	public function __construct( PLL_Model $model ) {
		$this->meta_type = 'term';

		parent::__construct( $model );
	}
}
