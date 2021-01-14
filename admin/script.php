<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Script
 *
 * Represents a single script to be enqueue in WordPress dashboard.
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

	public function register() {
		wp_register_script( $this->handle, $this->path, $this->dependencies, POLYLANG_VERSION, $this->in_footer );
		return $this;
	}

	public function enqueue() {
		if ( wp_script_is( $this->handle, 'registered' ) ) {
			wp_enqueue_script( $this->handle );
		}
		wp_enqueue_script( $this->handle, $this->path, $this->dependencies, POLYLANG_VERSION, $this->in_footer );
		return $this;
	}

	public function localize( $object_name, $value ) {
		wp_localize_script( $this->handle, $object_name, $value );
		return $this;
	}
}
