<?php

/**
 * A class to manage copy and synchronization of term metas
 *
 * @since 2.3
 */
class PLL_Sync_Term_Metas extends PLL_Sync_Metas {

	/**
	 * Constructor
	 *
	 * @since 2.3
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->meta_type = 'term';

		parent::__construct( $polylang );
	}
}
