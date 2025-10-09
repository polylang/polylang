<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Syntex\Polylang\Options\Primitive\Abstract_String;
use WP_Syntex\Polylang\Options\Primitive\Site_Health_String_Trait;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining the "version" option.
 *
 * @since 3.7
 */
class Version extends Abstract_String {
	use Site_Health_String_Trait;
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'version'
	 */
	public static function key(): string {
		return 'version';
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 * @since 3.7
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( "Polylang's version.", 'polylang' );
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: 'string', readonly: true, readonly: true}
	 */
	protected function get_data_structure(): array {
		return array_merge( parent::get_data_structure(), array( 'readonly' => true ) );
	}
}
