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
	 * @var PLL_Styles_Queue
	 */
	public static $instance;

	/**
	 * @var string
	 */
	protected $extension = '.css';

	/**
	 * @return PLL_Styles_Queue
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Enqueues a Polylang resource in WordPress.
	 *
	 * @param string   $filename Stylesheet path relative to this queue's build folder.
	 * @param string[] $dependencies Array of stylesheets handles the enqueued script depends on.
	 * @param string   $media Media type or query this stylesheet applis to. Default 'all'.
	 * @return string
	 * @since 3.0
	 */
	public static function enqueue( $filename, $dependencies = array(), $media = 'all' ) {
		$handle = self::get_instance()->compute_handle( $filename );
		$path = self::get_instance()->compute_path( $filename );
		wp_enqueue_style( $handle, $path, $dependencies, POLYLANG_VERSION, $media );
		return $handle;
	}
}
