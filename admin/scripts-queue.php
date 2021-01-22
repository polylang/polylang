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
	 * @var PLL_Scripts_Queue
	 */
	public static $instance;

	/**
	 * @var string
	 */
	protected $extension = '.js';

	/**
	 * @return PLL_Scripts_Queue
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers a Polylang script in WordPress.
	 *
	 * @param string   $filename Script path relative to this queue's build folder.
	 * @param string[] $dependencies Array of scripts handles the enqueued script depends on.
	 * @param bool     $in_footer True to print this scripts in the website's footer. False to print it in the HTML head instead.
	 * @return string Handle of the registered script.
	 * @since 3.0
	 */
	public static function register( $filename, $dependencies, $in_footer ) {
		$handle = self::get_instance()->compute_handle( $filename );
		$path = self::get_instance()->compute_path( $filename );
		wp_register_script( $handle, $path, $dependencies, POLYLANG_VERSION, $in_footer );
		return $handle;
	}

	/**
	 * Localizes a Polylang script in WordPress.
	 *
	 * @param string          $filename Script path relative to this queue's build folder.
	 * @param string          $object_name A valid name for a javascript variable.
	 * @param string|string[] $value Array to be serialized as a javascript object. Can accept unlimited nesting levels.
	 * @return string
	 * @throws InvalidArgumentException If $value is not a string nor an array.
	 * @since 3.0
	 */
	public static function localize( $filename, $object_name, $value ) {
		$handle = self::get_instance()->compute_handle( $filename );
		if ( is_string( $value ) ) {
			$value = esc_html( $value );
		} elseif ( is_array( $value ) ) {
			array_walk_recursive( $value, 'esc_html' );
		} else {
			throw new InvalidArgumentException( 'wp_localize_script() only accepts strings or arrays as value objects.' );
		}
		wp_localize_script( $handle, $object_name, $value );
		return $handle;
	}

	/**
	 * Enqueues a Polylang resource in WordPress.
	 *
	 * @param string   $filename Script path relative to this queue's build folder.
	 * @param string[] $dependencies Array of scripts handles the enqueued script depends on.
	 * @param mixed    $in_footer True to print this scripts in the website's footer. False to print it in the HTML head instead.
	 * @return string Handle of the enqueued script.
	 * @since 3.0
	 */
	public static function enqueue( $filename, $dependencies = array(), $in_footer = false ) {
		$handle = self::get_instance()->compute_handle( $filename );
		$path = self::get_instance()->compute_path( $filename );
		wp_enqueue_script( $handle, $path, $dependencies, POLYLANG_VERSION, $in_footer );
		return $handle;
	}
}
