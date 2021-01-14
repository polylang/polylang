<?php


class PLL_Styles_Queue extends PLL_Resource_Queue
{
	protected function compute_path($filename)
	{
		$build_dir = plugins_url( 'css/build/', $this->build_dir );
		return $build_dir . $filename . $this->suffix . '.css';
	}

}
