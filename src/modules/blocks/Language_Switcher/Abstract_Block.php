<?php
/**
 * @package Polylang-Pro
 */

namespace WP_Syntex\Polylang\Blocks\Language_Switcher;

use PLL_Switcher;
use WP_Block_Type_Registry;

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
	 * Current lang to render the language switcher block in an admin context.
	 *
	 * @since 2.8
	 *
	 * @var string|null
	 */
	protected $admin_current_lang;

	/**
	 * Is it the edit context?
	 *
	 * @var bool
	 */
	protected $is_edit_context = false;

	/**
	 * Constructor
	 *
	 * @since 2.8
	 *
	 * @param \PLL_Base $polylang Polylang object.
	 */
	public function __construct( &$polylang ) {
		$this->model = &$polylang->model;
		$this->links = &$polylang->links;
	}

	/**
	 * Adds the required hooks.
	 *
	 * @since 3.2
	 *
	 * @return self
	 */
	public function init() {
		// Use rest_pre_dispatch_filter to get additional parameters for language switcher block.
		add_filter( 'rest_pre_dispatch', array( $this, 'get_rest_query_params' ), 10, 3 );

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

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$script_filename = 'js/build/blocks' . $suffix . '.js';
		$script_handle = 'pll_blocks';
		wp_register_script(
			$script_handle,
			plugins_url( $script_filename, POLYLANG_ROOT_FILE ),
			array(
				'wp-block-editor',
				'wp-blocks',
				'wp-components',
				'wp-element',
				'wp-i18n',
				'wp-server-side-render',
				'lodash',
			),
			POLYLANG_VERSION,
			true
		);

		wp_localize_script( $script_handle, 'pll_block_editor_blocks_settings', PLL_Switcher::get_switcher_options( 'block', 'string' ) );

		register_block_type(
			$this->get_path(),
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);

		// Translated strings used in JS code
		wp_set_script_translations( $script_handle, 'polylang-pro' );
	}

	/**
	 * Returns the REST parameters for language switcher block.
	 * Used to store the request's language and context locally.
	 * Previously was in the `PLL_Block_Editor_Switcher_Block` class.
	 *
	 * @see WP_REST_Server::dispatch()
	 *
	 * @since 2.8
	 *
	 * @param mixed            $result  Response to replace the requested version with. Can be anything
	 *                                  a normal endpoint can return, or null to not hijack the request.
	 * @param \WP_REST_Server  $server  Server instance.
	 * @param \WP_REST_Request $request Request used to generate the response.
	 * @return mixed
	 * @template T of \WP_REST_Request
	 * @phpstan-param T $request
	 */
	public function get_rest_query_params( $result, $server, $request ) {
		if ( pll_is_edit_rest_request( $request ) ) {
			$this->is_edit_context = true;

			$lang = $request->get_param( 'lang' );
			if ( is_string( $lang ) && ! empty( $lang ) ) {
				$this->admin_current_lang = $lang;
			}
		}
		return $result;
	}

	/**
	 * Adds the attributes to render the block correctly.
	 * Also specifies not to echo the switcher in any case.
	 *
	 * @since 3.2
	 *
	 * @param array $attributes The attributes of the currently rendered block.
	 * @return array The modified attributes.
	 */
	protected function set_attributes_for_block( $attributes ) {
		$attributes['echo'] = 0;
		if ( $this->is_edit_context ) {
			$attributes['admin_render']           = 1;
			$attributes['admin_current_lang']     = $this->admin_current_lang;
			$attributes['hide_if_empty']          = 0;
			$attributes['hide_if_no_translation'] = 0; // Force not to hide the language for the block preview even if the option is checked.
		}
		return $attributes;
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
		$autoloader = include POLYLANG_DIR . '/vendor/autoload.php';
		return dirname( $autoloader->findFile( get_class( $this ) ) );
	}
}
