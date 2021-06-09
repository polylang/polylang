<?php
/**
 * @package Polylang
 */

/**
 * This class handles the deprecated filter 'block_editor_preload_paths'
 * to replace it by the new filter 'block_editor_rest_api_preload_paths'
 * and is used for backward compatibility with WP < 5.8.
 *
 * @since 3.1
 */
class PLL_Block_Editor_Filter_Preload_Paths {

	/**
	 * @var callable
	 */
	private $callback;

	/**
	 * PLL_Block_Editor_Filter_Preload_Paths constructor.
	 *
	 * @since 3.1
	 *
	 * @param callable $callback         Function or method to be executed when the filter is triggered.
	 * @param int      $priority         Priority of execution for the given callback. Default 10.
	 * @param int      $arguments_number Number of arguments to pass to the callback. Default 1.
	 */
	public function __construct( $callback, $priority = 10, $arguments_number = 1 ) {
		$this->callback = $callback;

		if ( class_exists( 'WP_Block_Editor_Context' ) ) { // Since WP 5.8.
			add_filter( 'block_editor_rest_api_preload_paths', array( $this, 'block_editor_rest_api_preload_paths' ), $priority, $arguments_number );
		} else {
			add_filter( 'block_editor_preload_paths', $callback, $priority, $arguments_number );
		}
	}

	/**
	 * Filters the array of REST API paths that will be used to preloaded common data to use with the block editor.
	 *
	 * Converts the WP_Block_Editor_Context object to a WP_Post if provided in the context.
	 *
	 * @since 3.1
	 *
	 * @param string[]                $preload_paths        The preload paths loaded by the Block Editor.
	 * @param WP_Block_Editor_Context $block_editor_context The post resource data.
	 * @return array|mixed|string[] (string|string[])[]
	 */
	public function block_editor_rest_api_preload_paths( $preload_paths, $block_editor_context = null ) {
		if ( null === $block_editor_context ) {
			return call_user_func( $this->callback, $preload_paths );
		} elseif ( ! empty( $block_editor_context->post ) ) {
			return call_user_func_array( $this->callback, array( $preload_paths, $block_editor_context->post ) );
		}

		return call_user_func_array( $this->callback, array( $preload_paths, $block_editor_context ) );
	}
}
