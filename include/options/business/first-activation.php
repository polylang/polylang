<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Syntex\Polylang\Options\Abstract_Option;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining the first activation option.
 *
 * @since 3.7
 */
class First_Activation extends Abstract_Option {
	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Optional. Option value.
	 *
	 * @phpstan-param non-falsy-string $key
	 */
	public function __construct( string $key, $value = null ) {
		parent::__construct( $key, $value, time() );
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: 'integer', minimum: 0, maximum: int}
	 */
	protected function get_specific_schema(): array {
		return array(
			'type'    => 'integer',
			'minimum' => 0,
			'maximum' => PHP_INT_MAX,
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
}
