<?php
/**
 * @package Polylang-Pro
 */

use Brain\Monkey\Functions;

/**
 * Helpers for import upload integration tests.
 */
trait PLL_Import_Upload_Mock_Trait {
	/**
	 * Stubs `pll_is_uploaded_file()` for integration tests.
	 *
	 * @return void
	 */
	protected function mock_pll_is_uploaded_file() {
		Functions\when( 'pll_is_uploaded_file' )->justReturn( true );
	}

	/**
	 * Sets `$_FILES['importFileToUpload']` like a successful PHP upload.
	 *
	 * @param array $overrides Values to merge over the defaults.
	 * @return void
	 */
	protected function set_import_upload_file( array $overrides ) {
		$tmp_name = isset( $overrides['tmp_name'] ) && is_string( $overrides['tmp_name'] ) ? $overrides['tmp_name'] : '';
		$size     = '' !== $tmp_name && is_readable( $tmp_name ) ? (int) filesize( $tmp_name ) : 0;

		$_FILES['importFileToUpload'] = array_merge(
			array(
				'name'     => '',
				'type'     => '',
				'tmp_name' => '',
				'error'    => UPLOAD_ERR_OK,
				'size'     => $size > 0 ? $size : 1,
			),
			$overrides
		);
	}
}
