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
 * @phpstan-import-type SchemaType from \WP_Syntex\Polylang\Options\Option\Abstract_Option
 */
class Sync extends List_Type {
	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: SchemaType, items: array{type: SchemaType, enum: non-empty-array<non-falsy-string>}}
	 */
	protected function get_specific_schema(): array {
		/** @phpstan-var non-empty-array<non-falsy-string> */
		$enum = array_keys( \PLL_Settings_Sync::list_metas_to_sync() );
		return array(
			'type'  => 'array',
			'items' => array(
				'type' => $this->type,
				'enum' => $enum,
			),
		);
	}
}
