<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Scripts_Queue
 *
 * Manages Polylang javascript files.
 */
class PLL_Scripts_Queue extends PLL_Resource_Queue {
	/**
	 * Instantiate a new resource.
	 *
	 * @param string   $filename Name of the file to enqueue.
	 * @param string[] $dependencies Array of resources handles this resource depends on. Default empty array.
	 * @param mixed    $extra Additional parameters to instantiate this resource with. Default false.
	 * @return PLL_Script|PLL_Stylesheet
	 * @since 3.0
	 */
	protected function create( $filename, $dependencies = array(), $extra = false ) {
		$handle = $this->compute_handle( $filename );
		$path = $this->compute_path( $filename );
		if ( $extra ) {
			$resource = new PLL_Script( $handle, $path, $dependencies, $extra );
		} else {
			$resource = new PLL_Script( $handle, $path, $dependencies );
		}
		return $resource;
	}
}
