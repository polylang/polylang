<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Blocks\Language_Switcher;

use PLL_Language;
use WP_Block_Type_Registry;
use WP_HTML_Tag_Processor;

/**
 * Abstract class for language switcher block.
 *
 * @since 3.2
 * @since 3.8 Moved to Polylang Core and renamed to Language_Switcher\Abstract_Block.
 */
abstract class Abstract_Block {
	/**
	 * @var \PLL_Links
	 */
	protected $links;

	/**
	 * @var \PLL_Model
	 */
	protected $model;

	/**
	 * Current language.
	 *
	 * @var PLL_Language|false|null
	 */
	private $current_language;

	/**
	 * Constructor
	 *
	 * @since 2.8
	 *
	 * @param \PLL_Base $polylang Polylang object.
	 */
	public function __construct( &$polylang ) {
		$this->model            = &$polylang->model;
		$this->links            = &$polylang->links;
		$this->current_language = &$polylang->curlang;
	}

	/**
	 * Adds the required hooks.
	 *
	 * @since 3.2
	 *
	 * @return self
	 */
	public function init() {
		// Register language switcher block.
		add_action( 'init', array( $this, 'register' ) );

		return $this;
	}

	/**
	 * Returns the block name with the Polylang's namespace.
	 *
	 * @since 3.2
	 *
	 * @return string The block name.
	 */
	abstract protected function get_block_name();

	/**
	 * Renders the Polylang's block on server.
	 *
	 * @since 3.2
	 * @since 3.3 Accepts two new parameters, $content and $block.
	 *
	 * @param array     $attributes The block attributes.
	 * @param string    $content    The saved content.
	 * @param \WP_Block $block      The parsed block.
	 * @return string The HTML string output to serve.
	 */
	abstract public function render( $attributes, $content, $block );

	/**
	 * Returns the path to the block JSON file directory.
	 * The directory name being used to register a block.
	 *
	 * @since 3.8
	 *
	 * @return string The path to the block.
	 */
	abstract protected function get_path(): string;

	/**
	 * Registers the Polylang's block.
	 *
	 * @since 2.8
	 * @since 3.2 Renamed and now handle any type of block registration based on a dynamic name.
	 *
	 * @return void
	 */
	public function register() {
		if ( WP_Block_Type_Registry::get_instance()->is_registered( $this->get_block_name() ) ) {
			// Don't register a block more than once or WordPress send an error. See https://github.com/WordPress/wordpress-develop/blob/5.9/src/wp-includes/class-wp-block-type-registry.php#L82-L90
			return;
		}

		if ( ! register_block_type(
			$this->get_path(),
			array(
				'render_callback' => array( $this, 'render' ),
			)
		) ) {
			return;
		}

		$script_handle   = 'pll_blocks';
		$suffix          = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$script_filename = 'js/build/blocks' . $suffix . '.js';
		$src             = plugins_url( $script_filename, POLYLANG_ROOT_FILE );

		wp_register_script(
			$script_handle,
			$src ?: false,
			array(
				'wp-block-editor',
				'wp-blocks',
				'wp-components',
				'wp-element',
				'wp-i18n',
			),
			POLYLANG_VERSION,
			true
		);

		// Translated strings used in JS code
		wp_set_script_translations( $script_handle, 'polylang' );

		// Fallback to default language if current language is not set, usually happens in Site Editor.
		$current_language = $this->current_language;

		if ( ! $current_language ) {
			$current_language = $this->model->get_default_language();
		}

		if ( ! $current_language ) {
			// Should not happen since the module is loaded only if there are languages.
			return;
		}

		if ( str_contains( wp_scripts()->get_inline_script_data( $script_handle, 'after' ), 'pllEditorCurrentLanguageSlug' ) ) {
			return;
		}

		wp_add_inline_script(
			$script_handle,
			'let pllEditorCurrentLanguageSlug = ' . wp_json_encode( $current_language->slug ) . ';',
			'after'
		);
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
	protected function apply_flag_styles_to_markup( string $html, array $attributes ): string {
		if ( empty( $attributes['show_flags'] ) ) {
			return $html;
		}

		$border_radius = max( 0, min( 100, (int) ( $attributes['flag_border_radius'] ?? 0 ) ) );
		$flag_width    = $attributes['flag_width'] ?? '18px';
		$flag_style    = safecss_filter_attr(
			sprintf(
				'--pll-flag-border-radius:%1$d;--pll-flag-width:%2$s',
				$border_radius,
				$flag_width
			)
		);

		$label_style = '';
		if ( ! empty( $attributes['show_labels'] ) ) {
			$label_style = safecss_filter_attr(
				sprintf(
					'--pll-flag-label-spacing:%1$s',
					$attributes['flag_label_spacing'] ?? '0.3em'
				)
			);
		}

		$processor = new WP_HTML_Tag_Processor( $html );

		while ( $processor->next_tag( array( 'tag_name' => 'SPAN', 'class_name' => 'pll-switcher-flag' ) ) ) {
			$style = $processor->get_attribute( 'style' ) ?? '';
			/** @var string $style `WP_HTML_Tag_Processor::get_attribute()` returns string|null for non-boolean attributes. */
			$processor->set_attribute( 'style', '' === $style ? $flag_style : rtrim( $style, ';' ) . ';' . $flag_style );

			if ( '' !== $label_style && $processor->next_tag( array( 'tag_name' => 'SPAN', 'class_name' => 'pll-switcher-label' ) ) ) {
				$style = $processor->get_attribute( 'style' ) ?? '';
				/** @var string $style `WP_HTML_Tag_Processor::get_attribute()` returns string|null for non-boolean attributes. */
				$processor->set_attribute( 'style', '' === $style ? $label_style : rtrim( $style, ';' ) . ';' . $label_style );
			}
		}

		return $processor->get_updated_html();
	}
}
