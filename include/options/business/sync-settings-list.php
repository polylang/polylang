<?php
/**
 * @package Polylang
 */

/**
 * Class defining synchronization settings list option.
 *
 * @since 3.7
 */
class PLL_Sync_Settings_List_Option extends PLL_List_Option {
	/**
	 * Creates JSON schema of the option.
	 *
	 * @since 3.7
	 *
	 * @return array The schema.
	 */
	protected function create_schema(): array {
		$sync_settings = PLL_Settings_Sync::list_metas_to_sync();

		return array(
			'$schema'     => 'http://json-schema.org/draft-04/schema#',
			'title'       => $this->key(),
			'description' => $this->description,
			'type'        => 'array',
			'context'     => array( 'edit' ),
			'items' => array(
				'type' => $this->type,
				'enum' => array_keys( $sync_settings ),
			),
		);
	}
}
