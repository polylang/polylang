<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Script_Queue
 *
 * Registers, enqueue and localizes Polylang scripts in WordPress.
 *
 * @since 3.0
 */
class PLL_Script_Queue extends PLL_Resource_Queue {

	/**
	 * @param $filename
	 * @return mixed
	 */
	protected function compute_path($filename)
	{
		$build_dir = plugins_url('/js/build/', $this->build_dir);
		return $build_dir . $filename . $this->suffix . '.js';
	}
}
