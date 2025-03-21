<?php
/**
 * @package Polylang
 */

/**
 * Displays a language list
 *
 * @since 1.2
 * @since 3.4 Extends `PLL_Walker` now.
 */
class PLL_Walker_List extends PLL_Walker {
	/**
	 * Database fields to use.
	 *
	 * @see https://developer.wordpress.org/reference/classes/walker/#properties Walker::$db_fields.
	 *
	 * @var string[]
	 */
	public $db_fields = array( 'parent' => 'parent', 'id' => 'id' );

	/**
	 * Outputs one element
	 *
	 * @since 1.2
	 *
	 * @param string   $output            Passed by reference. Used to append additional content.
	 * @param stdClass $element           The data object.
	 * @param int      $depth             Depth of the item.
	 * @param array    $args              An array of additional arguments.
	 * @param int      $current_object_id ID of the current item.
	 * @return void
	 */
	public function start_el( &$output, $element, $depth = 0, $args = array(), $current_object_id = 0 ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		// Link attributes.
		$link_atts = sprintf(
			'lang="%1$s" hreflang="%1$s" href="%2$s"',
			esc_attr( $element->locale ),
			esc_url( $element->url )
		);

		if ( ! empty( $element->link_classes ) ) {
			$link_atts .= sprintf(
				' class="%s"',
				esc_attr( implode( ' ', $element->link_classes ) )
			);
		}
		if ( ! empty( $element->current_lang ) ) {
			$link_atts .= ' aria-current="true"';
		}

		// Text label.
		if ( $args['show_flags'] && $args['show_names'] ) {
			$label = sprintf(
				'<span style="margin-%1$s:0.3em;">%2$s</span>',
				is_rtl() ? 'right' : 'left',
				esc_html( $element->name )
			);
		} else {
			$label = esc_html( $element->name );
		}

		$output .= sprintf(
			'%5$s<li class="%1$s"><a %2$s>%3$s%4$s</a></li>%6$s',
			esc_attr( implode( ' ', $element->classes ) ),
			$link_atts,
			$element->flag,
			$label,
			'discard' === $args['item_spacing'] ? '' : "\t",
			'discard' === $args['item_spacing'] ? '' : "\n"
		);
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
		$this->maybe_fix_walk_args( $max_depth, $args );

		return parent::walk( $elements, $max_depth, $args );
	}
}
