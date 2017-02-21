<?php

/**
 * Displays a language list
 *
 * @since 1.2
 */
class PLL_Walker_List extends Walker {
	var $db_fields = array( 'parent' => 'parent', 'id' => 'id' );

	/**
	 * Outputs one element
	 *
	 * @since 1.2
	 *
	 * @see Walker::start_el
	 */
	function start_el( &$output, $element, $depth = 0, $args = array(), $current_object_id = 0 ) {
		$output .= sprintf(
			'%6$s<li class="%1$s"><a lang="%2$s" hreflang="%2$s" href="%3$s">%4$s%5$s</a></li>%7$s',
			esc_attr( implode( ' ', $element->classes ) ),
			esc_attr( $element->locale ),
			esc_url( $element->url ),
			$element->flag,
			$args['show_flags'] && $args['show_names'] ? '<span style="margin-left:0.3em;">' . esc_html( $element->name ) . '</span>' : esc_html( $element->name ),
			$args['item_spacing'] === 'discard' ? '' : "\t",
			$args['item_spacing'] === 'discard' ? '' : "\n"
		);
	}

	/**
	 * Overrides Walker::display_element as it expects an object with a parent property
	 *
	 * @since 1.2
	 *
	 * @see Walker::display_element
	 */
	function display_element( $element, &$children_elements, $max_depth, $depth = 0, $args, &$output ) {
		$element = (object) $element; // Make sure we have an object
		$element->parent = $element->id = 0; // Don't care about this
		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
	}

	/**
	 * Overrides Walker:walk to set depth argument
	 *
	 * @since 1.2
	 *
	 * @param array $elements elements to display
	 * @param array $args
	 * @return string
	 */
	function walk( $elements, $args = array() ) {
		return parent::walk( $elements, -1, $args );
	}
}
