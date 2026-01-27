<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Primitive;

use WP_Syntex\Polylang\Options\Abstract_Option;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining a map option.
 *
 * @since 3.8
 */
abstract class Abstract_Map extends Abstract_Option {
	/**
	 * Option value.
	 *
	 * @var array
	 */
	protected $value;

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.8
	 *
	 * @return array Partial schema.
	 */
	protected function get_data_structure(): array {
		return array_merge(
			$this->get_inner_structure(),
			array(
				'type' => 'object', // Correspond to associative array in PHP, @see{https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types}.
			)
		);
	}

	/**
	 * Removes a key from the map.
	 *
	 * @since 3.8
	 *
	 * @param string $key The key to remove.
	 * @return bool True if the key has been removed. False otherwise.
	 */
	public function remove( string $key ): bool {
		if ( ! array_key_exists( $key, $this->value ) ) {
			return false;
		}

		$this->value[ $key ] = $this->reset_value( $key );

		return true;
	}

	/**
	 * Returns the JSON schema part specific to the inner structure of this option.
	 *
	 * @since 3.8
	 *
	 * @return array Partial schema.
	 */
	abstract protected function get_inner_structure(): array;

	/**
	 * Returns the reset value for a key.
	 *
	 * @since 3.8
	 *
	 * @param string $key The key to reset.
	 * @return mixed The reset value.
	 */
	abstract protected function reset_value( string $key );
}
