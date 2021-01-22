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
	 * @return string
	 */
	public function get_handle() {
		return $this->handle;
	}
}
