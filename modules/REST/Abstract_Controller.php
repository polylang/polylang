<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\REST;

use WP_Error;
use WP_REST_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract REST controller.
 *
 * @since 3.7
 */
abstract class Abstract_Controller extends WP_REST_Controller {
	/**
	 * Adds a status code to the given error and returns the error.
	 *
	 * @since 3.7
	 *
	 * @param WP_Error $error       A `WP_Error` object.
	 * @param int      $status_code Optional. A status code. Default is 400.
	 * @return WP_Error
	 */
	protected function add_status_to_error( WP_Error $error, int $status_code = 400 ): WP_Error {
		$error->add_data( array( 'status' => $status_code ) );
		return $error;
	}
}
