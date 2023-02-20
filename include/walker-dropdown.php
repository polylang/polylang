<?php
/**
 * @package Polylang
 */

/**
 * Displays languages in a dropdown list
 *
 * @since 1.2
 * @since 3.4 Extends `PLL_Walker` now.
 */
class PLL_Walker_Dropdown extends PLL_Walker {
	/**
	 * Database fields to use.
	 *
	 * @see https://developer.wordpress.org/reference/classes/walker/#properties Walker::$db_fields.
	 *
	 * @var string[]
	 */
	public $db_fields = array( 'parent' => 'parent', 'id' => 'id' );

	/**
	 * Outputs one element.
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
		$value_type = $args['value'];
		$output .= sprintf(
			"\t" . '<option value="%1$s"%2$s%3$s>%4$s</option>' . "\n",
			'url' === $value_type ? esc_url( $element->$value_type ) : esc_attr( $element->$value_type ),
			! empty( $element->locale ) ? sprintf( ' lang="%s"', esc_attr( $element->locale ) ) : '',
			selected( isset( $args['selected'] ) && $args['selected'] === $element->$value_type, true, false ),
			esc_html( $element->name )
		);
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
	 * @param array $elements  An array of `PLL_language` or `stdClass` elements.
	 * @param int   $max_depth The maximum hierarchical depth.
	 * @param mixed ...$args   Additional arguments.
	 * @return string The hierarchical item output.
	 *
	 * @phpstan-param array<PLL_Language|stdClass> $elements
	 */
	public function walk( $elements, $max_depth, ...$args ) { // // phpcs:ignore WordPressVIPMinimum.Classes.DeclarationCompatibility.DeclarationCompatibility
		$output = '';

		$this->maybe_fix_walk_args( $max_depth, $args );

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
