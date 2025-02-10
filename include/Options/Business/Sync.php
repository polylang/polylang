<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Syntex\Polylang\Options\Primitive\Abstract_List;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining synchronization settings list option.
 *
 * @since 3.7
 *
 * @phpstan-import-type SchemaType from \WP_Syntex\Polylang\Options\Abstract_Option
 */
class Sync extends Abstract_List {
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'sync'
	 */
	public static function key(): string {
		return 'sync';
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: 'array', items: array{type: SchemaType, enum: non-empty-list<non-falsy-string>}}
	 */
	protected function get_data_structure(): array {
		add_filter( 'lang_dir_for_domain', '__return_false' ); // Prevents loading the translations too early.
		$enum = array_keys( \PLL_Settings_Sync::list_metas_to_sync() );
		remove_filter( 'lang_dir_for_domain', '__return_false' );
		unload_textdomain( 'polylang', true ); // Required to allow `_load_textdomain_just_in_time()` to load the translations.
		return array(
			'type'  => 'array',
			'items' => array(
				'type' => $this->get_type(),
				'enum' => $enum,
			),
		);
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 * @since 3.7
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( 'List of data to synchronize.', 'polylang' );
	}
}
