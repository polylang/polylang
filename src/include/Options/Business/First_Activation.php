<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Syntex\Polylang\Options\Abstract_Option;
use WP_Syntex\Polylang\Options\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining the first activation option.
 *
 * @since 3.7
 */
class First_Activation extends Abstract_Option {
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'first_activation'
	 */
	public static function key(): string {
		return 'first_activation';
	}

	/**
	 * Returns the default value.
	 *
	 * @since 3.7
	 *
	 * @return int
	 *
	 * @phpstan-return int<0, max>
	 */
	protected function get_default() {
		return time();
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: 'integer', minimum: 0, maximum: int<0, max>, readonly: true}
	 */
	protected function get_data_structure(): array {
		return array(
			'type'     => 'integer',
			'minimum'  => 0,
			'maximum'  => PHP_INT_MAX,
			'readonly' => true,
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
		return __( 'Time of first activation of Polylang.', 'polylang' );
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
		return $this->format_single_value_for_site_health_info( wp_date( get_option( 'date_format' ), $this->get() ) );
	}
}
