<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Blocks\Language_Switcher\Standard;

use WP_Syntex\Polylang\Switcher\Assets;
use WP_Syntex\Polylang\Switcher\Switcher;
use WP_Syntex\Polylang\Switcher\Settings\Settings;
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
	 * Adds the required hooks.
	 *
	 * @since 3.9
	 *
	 * @return self
	 */
	public function init() {
		parent::init();

		add_action( 'init', array( $this, 'register_editor_style' ) );

		return $this;
	}

	/**
	 * Registers the editor style for the language switcher block.
	 *
	 * @since 3.9
	 *
	 * @return void
	 */
	public function register_editor_style(): void {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		Assets::register_styles();
		Assets::register_scripts();

		wp_register_style(
			'pll-language-switcher-editor-style',
			plugins_url( 'css/build/language-switcher-editor-style' . $suffix . '.css', POLYLANG_ROOT_FILE ),
			array( Assets::FRONTEND_ASSET_HANDLE ),
			POLYLANG_VERSION
		);
	}

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

		$attributes['unique_id']   = 'dropdown' === $attributes['layout'] ? 'lang_choice_' . $dropdown_id : '';
		$attributes['show_labels'] = 'no' === $attributes['show_labels'] ? '' : $attributes['show_labels'];
		$attributes['alignment']   = 'left'; // ???

		$settings = new Settings( $attributes );
		$switcher = new Switcher( $settings, $this->links );

		$switcher_output = $switcher->get();

		if ( empty( $switcher_output ) ) {
			return '';
		}

		$switcher_output = $this->apply_flag_styles( $switcher_output, $attributes );

		$aria_label = __( 'Choose a language', 'polylang' );
		if ( 'dropdown' === $attributes['layout'] ) {
			$switcher_output = '<label class="screen-reader-text" for="' . esc_attr( 'lang_choice_' . $settings->unique_id ) . '">' . esc_html( $aria_label ) . '</label>' . $switcher_output;

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

	/**
	 * Applies flag block attributes to switcher markup.
	 *
	 * Uses WP_HTML_Tag_Processor so the dropdown SVG toggle can stay in the markup
	 * (WP_HTML_Processor aborts on foreign content).
	 *
	 * @since 3.9
	 *
	 * @param string $html       Switcher HTML.
	 * @param array  $attributes Block attributes.
	 * @return string
	 */
	private function apply_flag_styles( string $html, array $attributes ): string {
		if ( empty( $attributes['show_flags'] ) ) {
			return $html;
		}

		$aspect_ratio  = str_replace( ':', '', $attributes['flag_aspect_ratio'] ?? '3:2' );
		$border_radius = max( 0, min( 100, (int) ( $attributes['flag_border_radius'] ?? 0 ) ) );
		$flag_width    = max( 1, min( 1000, (int) ( $attributes['flag_width'] ?? 18 ) ) );
		$style         = sprintf(
			'--pll-flag-border-radius:%1$d;--pll-flag-width:%2$dpx',
			$border_radius,
			$flag_width
		);

		if ( '' === $attributes['show_labels'] ) {
			return $this->apply_flag_styles_to_flag_images( $html, $aspect_ratio, $style );
		}

		$style .= sprintf(
			';--pll-flag-margin-right:%1$s',
			$attributes['flag_margin_right'] ?? '0px'
		);

		return $this->apply_flag_styles_to_flag_spans( $html, $aspect_ratio, $style );
	}

	/**
	 * Applies flag styling to pll-switcher-flag spans (flags with labels).
	 *
	 * @since 3.9
	 *
	 * @param string $html          Switcher HTML.
	 * @param string $aspect_ratio  Aspect ratio token without colon (e.g. `32`, `11`).
	 * @param string $style         Inline CSS custom properties for the flag.
	 * @return string
	 */
	private function apply_flag_styles_to_flag_spans( string $html, string $aspect_ratio, string $style ): string {
		$processor = new \WP_HTML_Tag_Processor( $html );

		while ( $processor->next_tag( array( 'tag_name' => 'SPAN', 'class_name' => 'pll-switcher-flag' ) ) ) {
			$processor->set_attribute( 'data-aspect-ratio', $aspect_ratio );
			$processor->set_attribute( 'style', $style );

			if ( $processor->next_tag( array( 'tag_name' => 'IMG' ) ) ) {
				$processor->remove_attribute( 'width' );
				$processor->remove_attribute( 'height' );
				$processor->remove_attribute( 'style' );
			}
		}

		return $processor->get_updated_html();
	}

	/**
	 * Applies flag styling to bare flag images (flags without labels).
	 *
	 * @since 3.9
	 *
	 * @param string $html          Switcher HTML.
	 * @param string $aspect_ratio  Aspect ratio token without colon (e.g. `32`, `11`).
	 * @param string $style         Inline CSS custom properties for the flag.
	 * @return string
	 */
	private function apply_flag_styles_to_flag_images( string $html, string $aspect_ratio, string $style ): string {
		$processor = new \WP_HTML_Tag_Processor( $html );

		while ( $processor->next_tag( array( 'tag_name' => 'IMG' ) ) ) {
			$processor->remove_attribute( 'width' );
			$processor->remove_attribute( 'height' );
			$processor->remove_attribute( 'style' );
			$processor->set_attribute( 'data-aspect-ratio', $aspect_ratio );
			$processor->set_attribute( 'style', $style );
		}

		return $processor->get_updated_html();
	}
}
