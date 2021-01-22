<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Styles_Queue
 *
 * Manages Polylang stylesheet files.
 */
class PLL_Styles_Queue extends PLL_Resource_Queue {
	/**
	 * Enqueues a Polylang resource in WordPress.
	 *
	 * @param string   $filename Will be used as scritpt's handle.
	 * @param string[] $dependencies Array of scripts handles the enqueued script depends on.
	 * @param mixed    $extra Extra parameters to pass to the resource.
	 * @return PLL_Script|PLL_Stylesheet
	 * @since 3.0
	 */
	public function enqueue( $filename, $dependencies = array(), $extra = false ) {
		$handle = $this->compute_handle( $filename );
		$path = $this->compute_path( $filename );
		if ( $extra ) {
			$resource = new PLL_Stylesheet( $handle, $path, $dependencies, $extra );
		} else {
			$resource = new PLL_Stylesheet( $handle, $path, $dependencies );
		}
		if ( wp_style_is( $handle, 'registered' ) ) {
			wp_enqueue_style( $handle );
		}
		wp_enqueue_style( $handle, $path, $dependencies, POLYLANG_VERSION, $extra );
		return $resource;
	}
}
