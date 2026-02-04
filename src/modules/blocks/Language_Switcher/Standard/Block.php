<?php
/**
 * @package Polylang-Pro
 */

namespace WP_Syntex\Polylang\Blocks\Language_Switcher\Standard;

use PLL_Switcher;
use WP_Syntex\Polylang\Blocks\Language_Switcher\Abstract_Block;

/**
 * Language switcher block.
 *
 * @since 2.8
 * @since 3.2 Extends now the PLL_Abstract_Language_Switcher_Block abstract class.
 * @since 3.8 Moved to Polylang Core and renamed to Language_Switcher\Standard\Block.
 */
class Block extends Abstract_Block {

	/**
	 * Returns the language switcher block name with the Polylang's namespace.
	 *
	 * @since 3.2
	 *
	 * @return string The block name.
	 */
	protected function get_block_name() {
		return 'polylang/language-switcher';
	}

	/**
	 * Renders the `polylang/language-switcher` block on server.
	 *
	 * @since 2.8
	 * @since 3.2 Renamed according to its parent abstract class.
	 * @since 3.3 Accepts two new parameters, $content and $block.
	 *
	 * @param array     $attributes The block attributes.
	 * @param string    $content The saved content. Unused.
	 * @param \WP_Block $block The parsed block. Unused.
	 * @return string Returns the language switcher.
	 */
	public function render( $attributes, $content, $block ) { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		static $dropdown_id = 0;
		++$dropdown_id;

		// Sets a unique id for dropdown in PLL_Switcher::the_language().
		$attributes['dropdown'] = empty( $attributes['dropdown'] ) ? 0 : $dropdown_id;

		$attributes = $this->set_attributes_for_block( $attributes );

		$attributes['raw'] = false;
		$switcher = new PLL_Switcher();
		$switcher_output = $switcher->the_languages( $this->links, $attributes );

		if ( empty( $switcher_output ) ) {
			return '';
		}

		$aria_label = __( 'Choose a language', 'polylang' );
		if ( $attributes['dropdown'] ) {
			$switcher_output = '<label class="screen-reader-text" for="' . esc_attr( 'lang_choice_' . $attributes['dropdown'] ) . '">' . esc_html( $aria_label ) . '</label>' . $switcher_output;

			$wrap_tag = '<div %1$s>%2$s</div>';
		} else {
			$wrap_tag = '<nav role="navigation" aria-label="' . esc_attr( $aria_label ) . '"><ul %1$s>%2$s</ul></nav>';
		}

		$wrap_attributes = get_block_wrapper_attributes();

		return sprintf( $wrap_tag, $wrap_attributes, $switcher_output );
	}

	/**
	 * Returns the path to the block JSON file directory.
	 * The directory name being used to register a block.
	 *
	 * @since 3.8
	 *
	 * @return string The path to the block.
	 */
	protected function get_path(): string {
		return __DIR__;
	}
}
