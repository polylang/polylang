<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Scripts
 *
 * Responsible for enqueueing Polylang scripts for WordPress Dashboard pages.
 */
class PLL_Resource_Queue {
	/**
	 * The resource queue for Polylang javascript files.
	 *
	 * @var PLL_Resource_Queue
	 */
	public static $scripts;

	/**
	 * @var string Path to the file containing the plugin's header.
	 */
	private $plugin_file;

	/**
	 * @var string Class name.
	 */
	private $resource_type;

	/**
	 * @var string To be appended to script names, before extension.
	 */
	private $suffix;

	/**
	 * PLL_Scripts constructor.
	 *
	 * Sets up the root path to find Polylang scripts in by default.
	 *
	 * @since 3.0
	 *
	 * @param string $resource_type Class name, either {@see PLL_Script} or {@see PLL_Stylesheet}.
	 * @param string $plugin_file Plugin's Header filepath, to use in {@see https://developer.wordpress.org/reference/functions/plugins_url/ plugins_url()}.
	 */
	public function __construct( $resource_type, $plugin_file ) {
		$this->resource_type = $resource_type;
		$this->plugin_file = $plugin_file;
		$this->suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	}

	/**
	 * Enqueues a Polylang resource in WordPress.
	 *
	 * @since 3.0
	 *
	 * @param string   $filename Will be used as scritpt's handle.
	 * @param string[] $dependencies Array of scripts handles the enqueued script depends on.
	 * @param bool     $in_footer True to print this script in the website's footer, rather than in the HTML <head> element. Default false.
	 * @return PLL_Script
	 */
	public function enqueue( $filename, $dependencies = array(), $in_footer = false ) {
		$resource = $this->create( $filename, $dependencies, $in_footer );
		$resource->enqueue();
		return $resource;
	}

	/**
	 * Registers a Polylang resource in WordPress.
	 *
	 * @since 3.0
	 *
	 * @param string   $filename Will be used as scritpt's handle.
	 * @param string[] $dependencies Array of scripts handles the enqueued script depends on.
	 * @param bool     $in_footer True to print this script in the website's footer, rather than in the HTML <head> element. Default false.
	 * @return PLL_Script
	 */
	public function register( $filename, $dependencies, $in_footer ) {
		$resource = $this->create( $filename, $dependencies, $in_footer );
		$resource->register();
		return $resource;
	}

	/**
	 * Instantiate a new resource.
	 *
	 * @since 3.0
	 *
	 * @param string   $filename Name of the file to enqueue.
	 * @param string[] $dependencies Array of resources handles this resource depends on. Default empty array.
	 * @param mixed    $extra Additional parameters to instantiate this resource with. Default false.
	 * @return PLL_Script
	 */
	protected function create( $filename, $dependencies = array(), $extra = false ) {
		$handle = 'pll_' . str_replace( '-', '_', strtolower( $filename ) );
		$path = plugins_url( '/js/build/' . $filename . $this->suffix . '.js', $this->plugin_file );
		if ( $extra ) {
			$resource = new $this->resource_type( $handle, $path, $dependencies, $extra );
		} else {
			$resource = new $this->resource_type( $handle, $path, $dependencies );
		}
		return $resource;
	}
}
