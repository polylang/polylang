<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Blocks\Language_Switcher\Navigation;

use WP_Block;
use PLL_Switcher;
use SplObjectStorage;
use WP_HTML_Tag_Processor;
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
	 * Trusted locale and label, keyed by the inner navigation blocks created by this switcher.
	 * Blocks parsed from saved content are never the same instances, so they can't be found here.
	 *
	 * @var SplObjectStorage
	 * @phpstan-var SplObjectStorage<WP_Block, array{locale: string, label: string}>
	 */
	private $internal_blocks;

	/**
	 * Constructor.
	 *
	 * @since 3.8.10
	 *
	 * @param \PLL_Base $polylang Polylang object.
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		$this->internal_blocks = new SplObjectStorage();
	}

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
		add_filter( 'render_block_core/navigation-link', array( $this, 'render_custom_attributes' ), 10, 3 );
		add_filter( 'render_block_core/navigation-submenu', array( $this, 'render_custom_attributes' ), 10, 3 );
		add_action( 'init', array( $this, 'register_editor_style' ) );

		return $this;
	}

	/**
	 * Registers the editor style for the navigation language switcher block.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public function register_editor_style(): void {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_style(
			'pll-navigation-language-switcher-editor-style',
			plugins_url( 'css/build/navigation-language-switcher-editor-style' . $suffix . '.css', POLYLANG_ROOT_FILE ),
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
		$attributes        = $this->set_attributes_for_block( $attributes );
		$switcher          = new PLL_Switcher();
		$switcher_elements = (array) $switcher->the_languages( $this->links, array_merge( $attributes, array( 'raw' => true ) ) );

		if ( empty( $switcher_elements ) ) {
			return '';
		}

		$generated_class = wp_apply_generated_classname_support( $block->block_type )['class'];

		if ( $attributes['dropdown'] ) {
			$inner_nav_link_blocks = array();
			$top_level_lang        = reset( $switcher_elements );
			foreach ( $switcher_elements as $switcher_element ) {
				$inner_nav_link_blocks[] = $this->create_inner_block(
					'core/navigation-link',
					$this->get_core_block_attributes( $switcher_element ),
					$switcher_element,
					$block->context,
					$attributes
				);

				if ( $switcher_element['current_lang'] && ! $attributes['hide_current'] ) {
					$top_level_lang = $switcher_element;
				}
			}

			$submenu_attributes               = $this->get_core_block_attributes( $top_level_lang );
			$submenu_attributes['className'] .= ' ' . $generated_class;
			$submenu_block                    = $this->create_inner_block(
				'core/navigation-submenu',
				$submenu_attributes,
				$top_level_lang,
				$block->context,
				$attributes,
				$inner_nav_link_blocks
			);
			$output                           = $submenu_block->render();
		} else {
			$output = '';

			foreach ( $switcher_elements as $switcher_element ) {
				$link_attributes               = $this->get_core_block_attributes( $switcher_element );
				$link_attributes['className'] .= ' ' . $generated_class;
				$output                       .= $this->create_inner_block(
					'core/navigation-link',
					$link_attributes,
					$switcher_element,
					$block->context,
					$attributes
				)->render();
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
		if ( ! $this->internal_blocks->offsetExists( $instance ) ) {
			return $block_content;
		}

		$snapshot = $this->internal_blocks->offsetGet( $instance );
		$this->internal_blocks->offsetUnset( $instance );

		$content_tags = new WP_HTML_Tag_Processor( $block_content );

		if ( 'core/navigation-submenu' === $instance->name ) {
			// If `openSubmenusOnClick`, the submenu is rendered as a button, so there are no `<a>` to process.
			if ( empty( $instance->context['openSubmenusOnClick'] ) && $content_tags->next_tag( array( 'tag_name' => 'a' ) ) ) {
				$content_tags->set_attribute( 'hreflang', $snapshot['locale'] );
				$content_tags->set_attribute( 'lang', $snapshot['locale'] );
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
			$content_tags->set_attribute( 'hreflang', $snapshot['locale'] );
			$content_tags->set_attribute( 'lang', $snapshot['locale'] );
		}

		$overridden_block_content = $content_tags->get_updated_html();

		return str_replace(
			static::PLACEHOLDER,
			$snapshot['label'],
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
	 * Creates an inner navigation block and stores the locale and label to render for it.
	 *
	 * @since 3.8.10
	 *
	 * @param string     $block_name      Core block name.
	 * @param array      $core_attributes Attributes to be rendered by core.
	 * @param array      $switcher_item   Array of a switcher item data.
	 * @param array      $context         Block context.
	 * @param array      $attributes      Array of polylang/navigation-language-switcher attributes.
	 * @param WP_Block[] $inner_blocks    Optional inner blocks.
	 * @return WP_Block
	 */
	private function create_inner_block( $block_name, $core_attributes, $switcher_item, $context, $attributes, $inner_blocks = array() ) {
		$block = new WP_Block(
			array(
				'blockName'   => $block_name,
				'attrs'       => $core_attributes,
				'innerBlocks' => $inner_blocks,
			),
			$context
		);

		$this->internal_blocks->offsetSet(
			$block,
			array(
				'locale' => $switcher_item['locale'],
				'label'  => $this->get_link_label( $switcher_item, $attributes ),
			)
		);

		return $block;
	}

	/**
	 * Builds the language switcher link label from a trusted switcher item.
	 *
	 * @since 3.8.10
	 *
	 * @param array $switcher_item Array of a switcher item data.
	 * @param array $attributes    Array of polylang/navigation-language-switcher attributes.
	 * @return string
	 */
	private function get_link_label( $switcher_item, $attributes ) {
		$show_flags = ! empty( $attributes['show_flags'] );
		$show_names = ! empty( $attributes['show_names'] );
		$link_label = '';

		if ( $show_flags ) {
			$link_label .= $switcher_item['flag'];
		}

		if ( $show_names ) {
			$name        = esc_html( $switcher_item['name'] );
			$link_label .= $show_flags ? ' ' . $name : $name;
		}

		return $link_label;
	}

	/**
	 * Returns attributes that fit for core/navigation-link or core/navigation-submenu and specific to polylang/navigation-language-switcher.
	 *
	 * @since 3.6
	 *
	 * @param array $switcher_item Array of a switcher item data.
	 * @return array Attributes to be rendered by core.
	 */
	private function get_core_block_attributes( $switcher_item ) {
		return array(
			'label'     => static::PLACEHOLDER,
			'url'       => $switcher_item['url'],
			'className' => trim( implode( ' ', (array) $switcher_item['classes'] ) ),
		);
	}
}
