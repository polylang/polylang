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
	 * The resource queue for Polylang CSS files.
	 *
	 * @var PLL_Resource_Queue
	 */
	public static $styles;

	/**
	 * @var string Path to the file containing the plugin's header.
	 */
	protected $build_dir;

	/**
	 * @var string Class name.
	 */
	private $resource_type;

	/**
	 * @var string
	 */
	protected $extension;

	/**
	 * @var string To be appended to script names, before extension.
	 */
	protected $suffix;

	/**
	 * PLL_Scripts constructor.
	 *
	 * Sets up the root path to find Polylang scripts in by default.
	 *
	 * @param string $resource_type Class name, either {@see PLL_Script} or {@see PLL_Stylesheet}.
	 * @param string $build_dir Plugin's Header filepath, to use in {@see https://developer.wordpress.org/reference/functions/plugins_url/ plugins_url()}.
	 * @param string $extension
	 * @since 3.0
	 */
	public function __construct( $resource_type, $build_dir, $extension ) {
		$this->resource_type = $resource_type;
		$this->build_dir = $build_dir;
		$this->suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$this->extension = $extension;
	}

	/**
	 * Enqueues a Polylang resource in WordPress.
	 *
	 * @since 3.0
	 *
	 * @param string   $filename Will be used as scritpt's handle.
	 * @param string[] $dependencies Array of scripts handles the enqueued script depends on.
	 * @param mixed    $extra Extra parameters to pass to the resource.
	 * @return PLL_Script|PLL_Stylesheet
	 */
	public function enqueue( $filename, $dependencies = array(), $extra = false ) {
		$resource = $this->create( $filename, $dependencies, $extra );
		$resource->enqueue();
		return $resource;
	}

	/**
	 * Registers a Polylang resource in WordPress.
	 *
	 * @param string   $filename Will be used as scritpt's handle.
	 * @param string[] $dependencies Array of scripts handles the enqueued script depends on.
	 * @param mixed    $extra Extra parameter to pass to the resource.
	 * @return PLL_Script|PLL_Stylesheet
	 * @since 3.0
	 */
	public function register( $filename, $dependencies, $extra ) {
		$resource = $this->create( $filename, $dependencies, $extra );
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
	 * @return PLL_Script|PLL_Stylesheet
	 */
	protected function create( $filename, $dependencies = array(), $extra = false ) {
		$handle = $this->compute_handle( $filename );
		$path = $this->compute_path( $filename );
		if ( $extra ) {
			$resource = new $this->resource_type( $handle, $path, $dependencies, $extra );
		} else {
			$resource = new $this->resource_type( $handle, $path, $dependencies );
		}
		return $resource;
	}

	/**
	 * @param string $path Path of the resource file.
	 * @return string
	 * @since 3.0
	 */
	protected function compute_handle( $path ) {
		$parts = explode( '/', $path );
		$filename = array_pop( $parts );
		return 'pll_' . str_replace( '-', '_', strtolower( $filename ) );
	}

	/**
	 * @since 3.0
	 * @param string $filename Name of the resource file.
	 * @return string
	 */
	protected function compute_path( $filename ) {
		return $this->build_dir . $filename . $this->suffix . $this->extension;
	}
}
