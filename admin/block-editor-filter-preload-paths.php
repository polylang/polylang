<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Block_Editor_Filter_Preload_Paths
 */
class PLL_Block_Editor_Filter_Preload_Paths
{

	/**
	 * @var int
	 */
	private $arguments_number;

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
	public function __construct( $callback, $priority = 10, $arguments_number = 1 )
	{
		$this->callback = $callback;
		$this->arguments_number = $arguments_number;

		if ( version_compare( $GLOBALS['wp_version'], '5.8-alpha', '<' ) ) {
			add_filter( 'block_editor_preload_paths', $callback, $priority, $arguments_number );
		} else {
			add_filter( 'block_editor_rest_api_preload_paths', array( $this, 'block_editor_rest_api_preload_paths' ), $priority, 2);
		}
	}

	/**
	* Filters the preload REST requests by the current language of the post
	* Necessary otherwise subsequent REST requests filtered by the language
	* would not hit the preloaded requests
	*
 	* @since 3.1
 	* @param $preload_paths
	* @param WP_Block_Editor_Context $block_editor_context The post resource data.
	* @return array|mixed|string[] (string|string[])[]
	*
	*/
	public function block_editor_rest_api_preload_paths( $preload_paths, $block_editor_context )
	{
		if ( $this->arguments_number > 1 && null === $block_editor_context->post ) {
			return $preload_paths;
		}

		// Only two cases: one or two arguments
		$args = 1 === $this->arguments_number ? array( $preload_paths ) : array( $preload_paths, $block_editor_context->post );

		return call_user_func_array( $this->callback, $args );
	}
}
