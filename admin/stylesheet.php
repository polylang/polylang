<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Stylesheet
 *
 * Represents a CSS file.
 *
 * @since 3.0
 */
class PLL_Stylesheet {
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
	 * @var string
	 */
	private $media;

	/**
	 * PLL_Script constructor.
	 *
	 * @param string   $handle Used to retrieve the scripts from the registered or enqueued list.
	 * @param string   $path Path to the script.
	 * @param string[] $dependencies An array of scripts this script depends on. Default to empty array.
	 * @param string   $media Media types od media for which this stylesheet would apply. Default 'all'.
	 */
	public function __construct( $handle, $path, $dependencies = array(), $media = 'all' ) {
		$this->handle = $handle;
		$this->path = $path;
		$this->dependencies = $dependencies;
		$this->media = $media;
	}

	/**
	 * Registers a Polylang stylesheet in WordPress.
	 *
	 * @see https://developer.wordpress.org/reference/functions/wp_register_style/ wp_register_style().
	 *
	 * @return PLL_Stylesheet $this
	 */
	public function register() {
		wp_register_style( $this->handle, $this->path, $this->dependencies, POLYLANG_VERSION, $this->media );
		return $this;
	}

	/**
	 * Enqueues a Polylang stylesheet in WordPress.
	 *
	 * @see https://developer.wordpress.org/reference/functions/wp_enqueue_style/ wp_enqueue_style().
	 * @return PLL_Stylesheet $this
	 */
	public function enqueue() {
		if ( wp_style_is( $this->handle, 'registered' ) ) {
			wp_enqueue_style( $this->handle );
		}
		wp_enqueue_style( $this->handle, $this->path, $this->dependencies, POLYLANG_VERSION, $this->media );
		return $this;
	}

	/**
	 * @return string
	 */
	public function get_handle() {
		return $this->handle;
	}
}
