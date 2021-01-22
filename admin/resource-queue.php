<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Scripts
 *
 * Responsible for enqueueing Polylang scripts for WordPress Dashboard pages.
 */
abstract class PLL_Resource_Queue {
	/**
	 * @var string Path to the file containing the plugin's header.
	 */
	protected $build_dir;

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
	 * @since 3.0
	 */
	public function __construct() {
		$this->build_dir = plugins_url( '/', POLYLANG_BASENAME );
		$this->suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
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
