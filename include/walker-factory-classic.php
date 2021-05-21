<?php
/**
 * @package Polylang Pro
 */

/**
 * Class PLL_Walker_Factory_Classic
 *
 * @since 3.1
 */
class PLL_Walker_Factory_Classic implements PLL_Walker_Factory_Interface {
	/**
	 * Return a Walker instance to match the arguments.
	 *
	 * @since 3.1
	 *
	 * @param array $args {
	 *   @type string $dropdown         The list is displayed as dropdown if set, defaults to 0.
	 *   @type string $current_language The current language's locale, used to set the selected value.
	 * }.
	 * @return PLL_Walker_Dropdown|PLL_Walker_List
	 */
	public function get_walker( $args ) {
		if ( $args['dropdown'] ) {
			$walker = new PLL_Walker_Dropdown( 'lang_choice_' . $args['dropdown'], $args['current_language'] );
		} else {
			$walker = new PLL_Walker_List();
		}
		return $walker;
	}
}
