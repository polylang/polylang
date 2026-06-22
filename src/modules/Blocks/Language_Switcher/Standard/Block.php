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

		add_action( 'init', array( Assets::class, 'register_styles' ) );
		add_action( 'init', array( Assets::class, 'register_scripts' ) );

		return $this;
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

		$attributes['unique_id']    = 'select' === $attributes['layout'] ? 'lang_choice_' . $dropdown_id : '';
		$attributes['show_wrapper'] = true;
		$attributes['alignment']    = 'none';

		$settings        = new Settings( $attributes );
		$switcher_output = ( new Switcher( $settings, $this->links ) )->get();

		if ( empty( $switcher_output ) ) {
			return '';
		}

		$switcher_output = $this->apply_flag_styles( $switcher_output, $attributes );

		return $this->apply_block_wrapper_attributes(
			$switcher_output,
			get_block_wrapper_attributes()
		);
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

		$border_radius = max( 0, min( 100, (int) ( $attributes['flag_border_radius'] ?? 0 ) ) );
		$flag_width    = $attributes['flag_width'] ?? '18px';
		$flag_style    = sprintf(
			'--pll-flag-border-radius:%1$d;--pll-flag-width:%2$s',
			$border_radius,
			$flag_width
		);

		$label_style = '';
		if ( '' !== $attributes['show_labels'] ) {
			$label_style = sprintf(
				'--pll-flag-label-spacing:%1$s',
				$attributes['flag_label_spacing'] ?? '0.3em'
			);
		}

		$processor = new \WP_HTML_Tag_Processor( $html );

		while ( $processor->next_tag( array( 'tag_name' => 'SPAN', 'class_name' => 'pll-switcher-flag' ) ) ) {
			$processor->set_attribute( 'style', $flag_style );

			if ( '' !== $label_style && $processor->next_tag( array( 'tag_name' => 'SPAN', 'class_name' => 'pll-switcher-label' ) ) ) {
				$processor->set_attribute( 'style', $label_style );
			}
		}

		return $processor->get_updated_html();
	}

	/**
	 * Merges block wrapper attributes onto the switcher root element.
	 *
	 * Block supports (background, spacing, etc.) must apply to the switcher
	 * wrapper itself — not to an extra full-width `<div>` around it.
	 *
	 * @since 3.9
	 *
	 * @param string $html                Switcher HTML.
	 * @param string $wrapper_attributes  Attributes from `get_block_wrapper_attributes()`.
	 * @return string
	 */
	private function apply_block_wrapper_attributes( string $html, string $wrapper_attributes ): string {
		if ( '' === trim( $wrapper_attributes ) ) {
			return $html;
		}

		$processor = new \WP_HTML_Tag_Processor( $html );

		if ( ! $processor->next_tag() ) {
			return sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $html );
		}

		foreach ( $this->parse_html_attributes( $wrapper_attributes ) as $name => $value ) {
			if ( 'class' === $name ) {
				$existing = $processor->get_attribute( 'class' ) ?? '';
				$value    = trim( $existing . ' ' . $value );
			} elseif ( 'style' === $name ) {
				$existing = $processor->get_attribute( 'style' ) ?? '';
				/** @var string $existing `WP_HTML_Tag_Processor::get_attribute()` returns string|null for non-boolean attributes */
				$value = '' === $existing ? $value : rtrim( $existing, ';' ) . ';' . $value;
			}

			$processor->set_attribute( $name, $value );
		}

		return $processor->get_updated_html();
	}

	/**
	 * Parses an HTML attributes string.
	 *
	 * @since 3.9
	 *
	 * @param string $attributes_string HTML attributes.
	 * @return array<string, string>
	 */
	private function parse_html_attributes( string $attributes_string ): array {
		$attributes = array();
		$processor  = new \WP_HTML_Tag_Processor( '<div ' . $attributes_string . '></div>' );

		if ( ! $processor->next_tag() ) {
			return $attributes;
		}

		foreach ( array( 'class', 'style', 'id' ) as $name ) {
			/** @var string|null $value `WP_HTML_Tag_Processor::get_attribute()` returns string|null for non-boolean attributes */
			$value = $processor->get_attribute( $name );

			if ( null !== $value && '' !== $value ) {
				$attributes[ $name ] = $value;
			}
		}

		return $attributes;
	}
}
