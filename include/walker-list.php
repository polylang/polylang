<?php
/**
 * @package Polylang
 */

/**
 * Displays a language list
 *
 * @since 1.2
 */
class PLL_Walker_List extends Walker {
	public $db_fields = array( 'parent' => 'parent', 'id' => 'id' );

	/**
	 * Outputs one element
	 *
	 * @since 1.2
	 *
	 * @param string $output            Passed by reference. Used to append additional content.
	 * @param object $element           The data object.
	 * @param int    $depth             Depth of the item.
	 * @param array  $args              An array of additional arguments.
	 * @param int    $current_object_id ID of the current item.
	 */
	public function start_el( &$output, $element, $depth = 0, $args = array(), $current_object_id = 0 ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$output .= sprintf(
			'%6$s<li class="%1$s"><a lang="%2$s" hreflang="%2$s" href="%3$s">%4$s%5$s</a></li>%7$s',
			esc_attr( implode( ' ', $element->classes ) ),
			esc_attr( $element->locale ),
			esc_url( $element->url ),
			$element->flag,
			$args['show_flags'] && $args['show_names'] ? sprintf( '<span style="margin-%1$s:0.3em;">%2$s</span>', is_rtl() ? 'right' : 'left', esc_html( $element->name ) ) : esc_html( $element->name ),
			'discard' === $args['item_spacing'] ? '' : "\t",
			'discard' === $args['item_spacing'] ? '' : "\n"
		);
	}

	/**
	 * Overrides Walker::display_element as it expects an object with a parent property
	 *
	 * @since 1.2
	 *
	 * @param object $element           Data object.
	 * @param array  $children_elements List of elements to continue traversing.
	 * @param int    $max_depth         Max depth to traverse.
	 * @param int    $depth             Depth of current element.
	 * @param array  $args              An array of arguments.
	 * @param string $output            Passed by reference. Used to append additional content.
	 */
	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
		$element = (object) $element; // Make sure we have an object
		$element->parent = $element->id = 0; // Don't care about this
		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
	}

	/**
	 * Overrides Walker:walk to set depth argument
	 *
	 * @since 1.2
	 * @since 2.6.7 Use $max_depth and ...$args parameters to follow the move of WP 5.3
	 *
	 * @param array $elements  An array of elements.
	 * @param int   $max_depth The maximum hierarchical depth.
	 * @param mixed ...$args   Additional arguments.
	 * @return string The hierarchical item output.
	 */
	public function walk( $elements, $max_depth, ...$args ) { // phpcs:ignore WordPressVIPMinimum.Classes.DeclarationCompatibility.DeclarationCompatibility
		if ( is_array( $max_depth ) ) {
			// Backward compatibility with Polylang < 2.6.7
			if ( WP_DEBUG ) {
				trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions
					sprintf(
						'%s was called incorrectly. The method expects an integer as second parameter since Polylang 2.6.7',
						__METHOD__
					)
				);
			}
			$args = $max_depth;
			$max_depth = -1;
		} else {
			$args = isset( $args[0] ) ? $args[0] : array();
		}

		return parent::walk( $elements, $max_depth, $args );
	}
}
