<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Blocks\Language_Switcher\Navigation;

use WP_Block;
use PLL_Language;
use WP_HTML_Tag_Processor;
use WP_Syntex\Polylang\Switcher\Assets;
use WP_Syntex\Polylang\Switcher\Switcher;
use WP_Syntex\Polylang\Switcher\Element\Abstract_Element;
use WP_Syntex\Polylang\Switcher\Element\Nav as Element;
use WP_Syntex\Polylang\Switcher\Settings\Menu as Settings;
use WP_Syntex\Polylang\Blocks\Language_Switcher\Abstract_Block;

/**
 * Language switcher block for navigation.
 *
 * @since 3.2
 * @since 3.8 Moved to Polylang Core and renamed to Language_Switcher\Navigation\Block.
 */
class Block extends Abstract_Block {
	/**
	 * Placeholder used to add language name or flag after WordPress renders the link labels.
	 *
	 * @var string
	 */
	const PLACEHOLDER = '%pll%';

	/**
	 * Adds the required hooks specific to the navigation language switcher.
	 *
	 * @since 3.2
	 *
	 * @return self
	 */
	public function init() {
		parent::init();

		add_action( 'rest_api_init', array( $this, 'register_switcher_menu_item_options_meta_rest_field' ) );
		add_filter( 'block_type_metadata', array( $this, 'register_custom_attributes' ) );
		add_filter( 'render_block_core/navigation-link', array( $this, 'render_custom_attributes' ), 10, 3 );
		add_filter( 'render_block_core/navigation-submenu', array( $this, 'render_custom_attributes' ), 10, 3 );
		add_action( 'init', array( $this, 'register_styles' ) );

		return $this;
	}

	/**
	 * Registers the styles for the navigation language switcher block.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public function register_styles(): void {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_style(
			'pll-navigation-language-switcher',
			plugins_url( 'css/build/navigation-language-switcher' . $suffix . '.css', POLYLANG_ROOT_FILE ),
			array(),
			POLYLANG_VERSION
		);

		wp_register_style(
			'pll-navigation-language-switcher-editor',
			plugins_url( 'css/build/navigation-language-switcher-editor' . $suffix . '.css', POLYLANG_ROOT_FILE ),
			array(),
			POLYLANG_VERSION
		);
	}

	/**
	 * Returns the navigation language switcher block name with the Polylang's namespace.
	 *
	 * @since 3.2
	 *
	 * @return string The block name.
	 */
	protected function get_block_name() {
		return 'polylang/navigation-language-switcher';
	}

	/**
	 * Renders the `polylang/navigation-language-switcher` block on server.
	 *
	 * @since 3.1
	 * @since 3.3 Accepts two new parameters, $content and $block.
	 *
	 * @param array    $attributes The block attributes.
	 * @param string   $content The saved content. Unused.
	 * @param WP_Block $block The parsed block.
	 * @return string The HTML string output to serve.
	 */
	public function render( $attributes, $content, $block ) {
		$attributes = $this->set_attributes_for_block( $attributes );
		$settings   = new Settings( $attributes );
		$elements   = ( new Switcher( $settings, $this->links ) )->get_elements();

		if ( empty( $elements ) ) {
			return '';
		}

		if ( 'dropdown' === $settings->layout ) {
			$inner_nav_link_blocks = array();

			foreach ( $elements as $element ) {
				$nav_link_block_args = array(
					'blockName' => 'core/navigation-link',
					'attrs'     => $this->get_core_block_attributes( $attributes, $element ),
				);

				$inner_nav_link_blocks[] = new WP_Block( $nav_link_block_args, $block->context );
			}

			$top_level_element = $this->get_top_level_element( $settings );

			if ( empty( $top_level_element ) ) {
				return '';
			}

			$submenu_attributes               = $this->get_core_block_attributes( $attributes, $top_level_element );
			$submenu_attributes['className'] .= ' ' . wp_apply_generated_classname_support( $block->block_type )['class'];
			$submenu_block_args               = array(
				'blockName'   => 'core/navigation-submenu',
				'attrs'       => $submenu_attributes,
				'innerBlocks' => $inner_nav_link_blocks,
			);

			$submenu_block = new WP_Block( $submenu_block_args, $block->context );
			$output        = $submenu_block->render();
		} else {
			$output = '';

			foreach ( $elements as $element ) {
				$link_attributes               = $this->get_core_block_attributes( $attributes, $element );
				$link_attributes['className'] .= ' ' . wp_apply_generated_classname_support( $block->block_type )['class'];
				$nav_link_block_args           = array(
					'blockName' => 'core/navigation-link',
					'attrs'     => $link_attributes,
				);

				$link_block  = new WP_Block( $nav_link_block_args, $block->context );
				$output     .= $link_block->render();
			}
		}

		return $output;
	}

	/**
	 * Register switcher menu item meta options as a REST API field.
	 *
	 * @since 3.2
	 *
	 * @return void
	 */
	public function register_switcher_menu_item_options_meta_rest_field() {
		register_post_meta(
			'nav_menu_item',
			'_pll_menu_item',
			array(
				'object_subtype' => 'nav_menu_item',
				'description'    => __( 'Language switcher settings', 'polylang' ),
				'single'         => true,
				'show_in_rest'   => array(
					'schema' => array(
						'type'                 => 'object',
						'additionalProperties' => array(
							'type' => 'boolean',
						),
					),
				),
			)
		);
	}

	/**
	 * Filters core/navigation-link and core/navigation-submenu attributes during registration to add our own.
	 *
	 * @since 3.6
	 *
	 * @param array $metadata Metadata for registering a block type.
	 *
	 * @return array The filtered metadata if about a core/navigation-link.
	 */
	public function register_custom_attributes( $metadata ) {
		if ( 'core/navigation-link' === $metadata['name'] || 'core/navigation-submenu' === $metadata['name'] ) {
			$pll_attributes = array(
				'hreflang'         => array(
					'type' => 'string',
				),
				'lang'             => array(
					'type' => 'string',
				),
				'pll_label_markup' => array(
					'type' => 'string',
				),
			);
			$metadata['attributes'] = array_merge( $metadata['attributes'], $pll_attributes );
		}

		return $metadata;
	}

	/**
	 * Renders a core/naviagation-link or core/naviagation-submenu block by adding hreflang and lang attributes to the <a> tag
	 * and also the language flag if required.
	 *
	 * @since 3.6
	 *
	 * @param string   $block_content The block content.
	 * @param array    $block         The full block, including name and attributes.
	 * @param WP_Block $instance      The block instance.
	 *
	 * @return string A formatted HTML string representing the core/navigation-link or core/navigation-submenu block.
	 */
	public function render_custom_attributes( $block_content, $block, $instance ) {
		if ( ! isset(
			$instance->attributes['pll_label_markup'],
			$instance->attributes['lang'],
			$instance->attributes['hreflang']
		)
		) {
			return $block_content;
		}

		$content_tags = new WP_HTML_Tag_Processor( $block_content );

		if ( 'core/navigation-submenu' === $instance->name ) {
			// If `openSubmenusOnClick`, the submenu is rendered as a button, so there are no `<a>` to process.
			if ( empty( $instance->context['openSubmenusOnClick'] ) && $content_tags->next_tag( array( 'tag_name' => 'a' ) ) ) {
				$content_tags->set_attribute( 'hreflang', $instance->attributes['hreflang'] );
				$content_tags->set_attribute( 'lang', $instance->attributes['lang'] );
			}
			if ( $content_tags->next_tag( array( 'tag_name' => 'button' ) ) ) {
				$content_tags->set_attribute(
					'aria-label',
					str_replace(
						static::PLACEHOLDER,
						__( 'Languages', 'polylang' ),
						(string) $content_tags->get_attribute( 'aria-label' )
					)
				);
			}
		} elseif ( $content_tags->next_tag( array( 'tag_name' => 'a' ) ) ) {
			$content_tags->set_attribute( 'hreflang', $instance->attributes['hreflang'] );
			$content_tags->set_attribute( 'lang', $instance->attributes['lang'] );
		}

		$overridden_block_content = $content_tags->get_updated_html();

		return str_replace(
			static::PLACEHOLDER,
			$instance->attributes['pll_label_markup'],
			$overridden_block_content
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
	 * Returns the top-level language element for a dropdown layout.
	 *
	 * @since 3.9
	 *
	 * @param Settings $settings Switcher settings.
	 * @return Element|null
	 */
	private function get_top_level_element( Settings $settings ): ?Element {
		$curlang = $this->links->curlang ?? $this->model->get_default_language();

		if ( ! $curlang instanceof PLL_Language ) {
			return null;
		}

		return new Element( $curlang, $settings, $this->links );
	}

	/**
	 * Returns attributes that fit for core/navigation-link or core/navigation-submenu and specific to polylang/navigation-language-switcher.
	 *
	 * @since 3.6
	 *
	 * @param array            $attributes Array of polylang/navigation-language-switcher attributes.
	 * @param Abstract_Element $element    Switcher element.
	 * @return array Attributes to be rendered by core.
	 */
	private function get_core_block_attributes( array $attributes, Abstract_Element $element ): array {
		$class_name = trim( implode( ' ', $element->item_classes ) );

		if ( ! empty( $attributes['show_flags'] ) ) {
			$aspect_ratio = $attributes['flag_aspect_ratio'] ?? '3:2';
			$class_name  .= ' pll-aspect-ratio-' . str_replace( ':', '', $aspect_ratio );
		}

		return array(
			'label'            => static::PLACEHOLDER,
			'url'              => $element->url,
			'lang'             => $element->locale,
			'hreflang'         => $element->locale,
			'pll_label_markup' => $this->apply_flag_styles_to_markup( $element->get_label(), $attributes ),
			'className'        => $class_name,
		);
	}
}
