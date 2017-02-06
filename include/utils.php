<?php

/**
 * Base class for both admin
 *
 * @since 2.1
 */

class PLL_UTILS {

    /**
	 * Returns the http_host
	 *
	 * @since 2.1
	 */
	public function get_http_host() {
        if (empty( $_SERVER['HTTP_HOST'])) {
            // error is sent to PHP's system logger
            error_log("[polylang] HTTP_HOST is not set", 0);
            return ''
        }
        return $_SERVER['HTTP_HOST'])
	}
