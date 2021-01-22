<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Script
 *
 * Represents a single script to be enqueue in WordPress dashboard.
 *
 * @since 3.0
 */
class PLL_Script {
	/**
	 * @var string
	 */
	private $handle;
	/**
	 * @var string
	 */
	private $path;
	/**
	 * @var string[]
	 */
	private $dependencies;
	/**
	 * @var bool
	 */
	private $in_footer;

	/**
	 * PLL_Script constructor.
	 *
	 * @param string   $handle Used to retrieve the scripts from the registered or enqueued list.
	 * @param string   $path Path to the script.
	 * @param string[] $dependencies An array of scripts this script depends on. Default to empty array.
	 * @param bool     $in_footer True to print script in the website's footer, false to print it in the HTML <head> element.
	 */
	public function __construct( $handle, $path, $dependencies = array(), $in_footer = false ) {
		$this->handle = $handle;
		$this->path = $path;
		$this->dependencies = $dependencies;
		$this->in_footer = $in_footer;
	}

	/**
	 * Localizes a Polylang script in WordPress.
	 *
	 * @see https://developer.wordpress.org/reference/functions/wp_localize_script/ wp_localize_script().
	 *
	 * @param string          $object_name A valid javascript variable name.
	 * @param string|string[] $value The javascript data to pass. Associative arrays are mapped as javascript objects, and can have nested arrays as well.
	 * @return PLL_Script $this
	 * @throws InvalidArgumentException When value is neither an array or a string.
	 */
	public function localize( $object_name, $value ) {
		if ( is_string( $value ) ) {
			$value = esc_html( $value );
		} elseif ( is_array( $value ) ) {
			array_walk_recursive( $value, 'esc_html' );
		} else {
			throw new InvalidArgumentException( 'wp_localize_script() only accepts strings or arrays as value objects.' );
		}
		wp_localize_script( $this->handle, $object_name, $value );
		return $this;
	}

	/**
	 * @return string
	 */
	public function get_handle() {
		return $this->handle;
	}
}
