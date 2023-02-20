<?php
/**
 * @package Polylang
 */

/**
 * A class for displaying various tree-like language structures.
 *
 * Extend the `PLL_Walker` class to use it, and implement some of the methods from `Walker`.
 * See: {https://developer.wordpress.org/reference/classes/walker/#methods}.
 *
 * @since 3.4
 */
class PLL_Walker extends Walker {
	/**
	 * Database fields to use.
	 *
	 * @see https://developer.wordpress.org/reference/classes/walker/#properties Walker::$db_fields.
	 *
	 * @var string[]
	 */
	public $db_fields = array( 'parent' => 'parent', 'id' => 'id' );

	/**
	 * Overrides Walker::display_element as it expects an object with a parent property.
	 *
	 * @since 1.2
	 * @since 3.4 Refactored and moved in `PLL_Walker`.
	 *
	 * @param PLL_Language|stdClass $element           Data object. `PLL_language` in our case.
	 * @param array                 $children_elements List of elements to continue traversing.
	 * @param int                   $max_depth         Max depth to traverse.
	 * @param int                   $depth             Depth of current element.
	 * @param array                 $args              An array of arguments.
	 * @param string                $output            Passed by reference. Used to append additional content.
	 * @return void
	 */
	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
		if ( $element instanceof PLL_Language ) {
			$element = $element->to_std_class();
		}

		$element->parent = $element->id = 0; // Don't care about this.

		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
	}

	/**
	 * Sets `PLL_Walker::walk()` arguments as it should
	 * and triggers an error in case of misuse of them.
	 *
	 * @since 3.4
	 *
	 * @param array|int $max_depth The maximum hierarchical depth. Passed by reference.
	 * @param array     $args      Additional arguments. Passed by reference.
	 * @return void
	 */
	protected function maybe_fix_walk_args( &$max_depth, &$args ) {
		if ( ! is_array( $max_depth ) ) {
			$args = isset( $args[0] ) ? $args[0] : array();
			return;
		}

		// Backward compatibility with Polylang < 2.6.7
		_doing_it_wrong(
			__CLASS__ . '::walk()',
			'The method expects an integer as second parameter.',
			'2.6.7'
		);
		$args      = $max_depth;
		$max_depth = -1;
	}
}
