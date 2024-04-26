<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Option\Business;

use WP_Syntex\Polylang\Options\Option\Primitive\List_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining synchronization settings list option.
 *
 * @since 3.7
 *
 * @phpstan-import-type Schema from \WP_Syntex\Polylang\Options\Option\Abstract_Option
 */
class Sync extends List_Type {
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
					'enum' => array_keys( \PLL_Settings_Sync::list_metas_to_sync() ),
				),
			)
		);
	}
}
