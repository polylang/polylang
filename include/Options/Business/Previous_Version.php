<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Syntex\Polylang\Options\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining the "previous version" option.
 *
 * @since 3.7
 */
class Previous_Version extends Version {
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'previous_version'
	 */
	public static function key(): string {
		return 'previous_version';
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 * @since 3.7
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( "Polylang's previous version.", 'polylang' );
	}

	/**
	 * Adds information to the site health info array.
	 *
	 * @since 3.8
	 *
	 * @param Options $options An instance of the Options class providing additional configuration.
	 *
	 * @return array The updated site health information.
	 */
	public function get_site_health_info( Options $options ): array { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$value ='';
		if ( ! $this->get() ) {
			$value = __( 'This is the first activation', 'polylang' );
		}

		return $this->format_single_value_for_site_health_info( $value );
	}
}
