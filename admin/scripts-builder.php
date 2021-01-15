<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Scripts
 *
 * Responsible for enqueueing Polylang scripts for WordPress Dashboard pages.
 */
class PLL_Scripts_Builder {
	/**
	 * @var string Path to the file containing the plugin's header.
	 */
	public $plugin_file;

	/**
	 * @var string To be appended to script names, before extension.
	 */
	private $suffix;

	/**
	 * PLL_Scripts constructor.
	 *
	 * Sets up the root path to find Polylang scripts in by default.
	 */
	public function __construct() {
		$this->plugin_file = POLYLANG_FILE;
		$this->suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	}

	/**
	 * Wraps {@see https://developer.wordpress.org/reference/functions/wp_enqueue_script/ wp_enqueue_script()}.
	 *
	 * @param string   $filename Will be used as scritpt's handle.
	 * @param string[] $dependencies Array of scripts handles the enqueued script depends on.
	 * @param bool     $in_footer True to print this script in the website's footer, rather than in the HTML <head> element. Default false.
	 * @return PLL_Script
	 */
	function enqueue( $filename, $dependencies = array(), $in_footer = false ) {
		$script = $this->create_script( $filename, $dependencies, $in_footer );
		$script->enqueue();
		return $script;
	}

	function register( $filename, $dependencies, $in_footer ) {
		$script = $this->create_script( $filename, $dependencies, $in_footer );
		$script->register();
		return $script;
	}

	/**
	 * @param $filename
	 * @param array    $dependencies
	 * @return PLL_Script
	 */
	protected function create_script( $filename, $dependencies = array(), $in_footer = false ) {
		$handle = 'pll_' . str_replace( '-', '_', strtolower( $filename ) );
		$path = plugins_url( '/js/build/' . $filename . $this->suffix . '.js', $this->plugin_file );
		return new PLL_Script( $handle, $path, $dependencies, $in_footer );
	}
}
