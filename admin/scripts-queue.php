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
	 * Registers a Polylang resource in WordPress.
	 *
	 * @param string   $filename Will be used as scritpt's handle.
	 * @param string[] $dependencies Array of scripts handles the enqueued script depends on.
	 * @param mixed    $extra Extra parameter to pass to the resource.
	 * @return PLL_Script|PLL_Stylesheet
	 * @since 3.0
	 */
	public function register( $filename, $dependencies, $extra ) {
		$handle = $this->compute_handle( $filename );
		$path = $this->compute_path( $filename );
		if ( $extra ) {
			$resource = new PLL_Script( $handle, $path, $dependencies, $extra );
		} else {
			$resource = new PLL_Script( $handle, $path, $dependencies );
		}
		wp_register_script( $handle, $path, $dependencies, POLYLANG_VERSION, $extra );

		return $resource;
	}

	public function localize( $filename, $object_name, $value ) {
		$handle = $this->compute_handle( $filename );
		if ( is_string( $value ) ) {
			$value = esc_html( $value );
		} elseif ( is_array( $value ) ) {
			array_walk_recursive( $value, 'esc_html' );
		} else {
			throw new InvalidArgumentException( 'wp_localize_script() only accepts strings or arrays as value objects.' );
		}
		wp_localize_script( $handle, $object_name, $value );
	}

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
			$resource = new PLL_Script( $handle, $path, $dependencies, $extra );
		} else {
			$resource = new PLL_Script( $handle, $path, $dependencies );
		}
		if ( wp_script_is( $handle, 'registered' ) ) {
			wp_enqueue_script( $handle );
		}
		wp_enqueue_script( $handle, $path, $dependencies, POLYLANG_VERSION, $extra );
		return $resource;
	}
}
