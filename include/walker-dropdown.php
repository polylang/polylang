<?php
/**
 * @package Polylang
 */

/**
 * Displays languages in a dropdown list
 *
 * @since 1.2
 */
class PLL_Walker_Dropdown extends Walker {
	/**
	 * Database fields to use.
	 *
	 * @see https://developer.wordpress.org/reference/classes/walker/#properties Walker::$db_fields.
	 *
	 * @var array
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
		$value = $args['value'];
		$output .= sprintf(
			"\t" . '<option value="%1$s"%2$s%3$s>%4$s</option>' . "\n",
			esc_attr( $element->$value ),
			method_exists( $element, 'get_locale' ) ? sprintf( ' lang="%s"', esc_attr( $element->get_locale( 'display' ) ) ) : '',
			selected( isset( $args['selected'] ) && $args['selected'] === $element->$value, true, false ),
			esc_html( $element->name )
		);
	}

	/**
	 * Overrides Walker::display_element as expects an object with a parent property
	 *
	 * @since 1.2
	 *
	 * @param stdClass $element           Data object.
	 * @param array    $children_elements List of elements to continue traversing.
	 * @param int      $max_depth         Max depth to traverse.
	 * @param int      $depth             Depth of current element.
	 * @param array    $args              An array of arguments.
	 * @param string   $output            Passed by reference. Used to append additional content.
	 * @return void
	 */
	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
		$element = (object) $element; // Make sure we have an object
		$element->parent = $element->id = 0; // Don't care about this
		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
	}

	/**
	 * Starts the output of the dropdown list
	 *
	 * @since 1.2
	 * @since 2.6.7 Use $max_depth and ...$args parameters to follow the move of WP 5.3
	 *
	 * List of parameters accepted in $args:
	 *
	 * flag     => display the selected language flag in front of the dropdown if set to 1, defaults to 0
	 * value    => the language field to use as value attribute, defaults to 'slug'
	 * selected => the selected value, mandatory
	 * name     => the select name attribute, defaults to 'lang_choice'
	 * id       => the select id attribute, defaults to $args['name']
	 * class    => the class attribute
	 * disabled => disables the dropdown if set to 1
	 *
	 * @param array $elements  An array of elements.
	 * @param int   $max_depth The maximum hierarchical depth.
	 * @param mixed ...$args   Additional arguments.
	 * @return string The hierarchical item output.
	 */
	public function walk( $elements, $max_depth, ...$args ) { // // phpcs:ignore WordPressVIPMinimum.Classes.DeclarationCompatibility.DeclarationCompatibility
		$output = '';

		if ( is_array( $max_depth ) ) { // @phpstan-ignore-line
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

		$args = wp_parse_args( $args, array( 'value' => 'slug', 'name' => 'lang_choice' ) );

		if ( ! empty( $args['flag'] ) ) {
			$current = wp_list_filter( $elements, array( $args['value'] => $args['selected'] ) );
			$lang = reset( $current );
			$output = sprintf(
				'<span class="pll-select-flag">%s</span>',
				empty( $lang->flag ) ? esc_html( $lang->slug ) : $lang->flag
			);
		}

		$output .= sprintf(
			'<select name="%1$s"%2$s%3$s%4$s>' . "\n" . '%5$s' . "\n" . '</select>' . "\n",
			esc_attr( $args['name'] ),
			isset( $args['id'] ) && ! $args['id'] ? '' : ' id="' . ( empty( $args['id'] ) ? esc_attr( $args['name'] ) : esc_attr( $args['id'] ) ) . '"',
			empty( $args['class'] ) ? '' : ' class="' . esc_attr( $args['class'] ) . '"',
			disabled( empty( $args['disabled'] ), false, false ),
			parent::walk( $elements, $max_depth, $args )
		);

		return $output;
	}
}
