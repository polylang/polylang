<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use NOOP_Translations;
use PLL_Settings_Sync;
use WP_Syntex\Polylang\Options\Options;
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
	 * Adds information to the site health info array.
	 *
	 * @since 3.8
	 *
	 * @param array   $info    The current site health information.
	 * @param Options $options An instance of the Options class providing additional configuration.
	 *
	 * @return array The updated site health information.
	 */
	public function add_to_site_health_info( array $info, Options $options ): array {
		if ( empty( $options->get( self::key() ) ) ) {
			$value = '0: ' . __( 'Synchronization disabled', 'polylang' );
		} else {
			$value = implode( ', ', $this->get() );
		}

		return $this->get_site_health_info( $info, $value, self::key() );
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
		$GLOBALS['l10n']['polylang'] = new NOOP_Translations(); // Prevents loading the translations too early.
		$enum = array_keys( PLL_Settings_Sync::list_metas_to_sync() );
		unset( $GLOBALS['l10n']['polylang'] );

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
