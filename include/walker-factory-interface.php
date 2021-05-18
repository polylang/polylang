<?php
/**
 * @package Polylang-Pro
 */

/**
 * Interface PLL_Walker_Facotry_Interface
 *
 * @since 3.1
 */
interface PLL_Walker_Factory_Interface {
	/**
	 * Return an instance of a Walker matching the given arguments.
	 *
	 * @since 3.1
	 *
	 * @param array $args {
	 *   @type string $dropdown The list is displayed as dropdown if set, defaults to 0
	 * }.
	 * @return Walker
	 */
	public function get_walker( $args );
}
