<?php
/**
 * @package Polylang
 */

/**
 * Class defining synchronization settings list option.
 *
 * @since 3.7
 *
 * @phpstan-import-type Schema from PLL_Abstract_Option
 */
class PLL_Sync_Settings_List_Option extends PLL_List_Option {
	/**
	 * Creates JSON schema of the option.
	 *
	 * @since 3.7
	 *
	 * @return array The schema.
	 *
	 * @phpstan-return Schema
	 */
	protected function create_schema(): array {
		return $this->build_schema(
			array(
				'type'  => 'array',
				'items' => array(
					'type' => $this->type,
					'enum' => array_keys( PLL_Settings_Sync::list_metas_to_sync() ),
				),
			)
		);
	}
}
