<?php
/**
 * @package Polylang
 */

/**
 * A class to manage manage the language cookie
 *
 * @since 2.9
 */
class PLL_Cookie {
	/**
	 * Parses the cookie parameters
	 *
	 * @since 2.9
	 *
	 * @param array $args {@see PLL_Cookie::set()}
	 * @return array
	 */
	protected static function parse_args( $args ) {
		/**
		 * Filter the Polylang cookie duration
		 * /!\ this filter may be fired *before* the theme is loaded
		 *
		 * @since 1.8
		 *
		 * @param int $duration cookie duration in seconds
		 */
		$expiration = apply_filters( 'pll_cookie_expiration', YEAR_IN_SECONDS );

		$defaults = array(
			'expires'  => time() + $expiration,
			'path'     => COOKIEPATH,
			'domain'   => COOKIE_DOMAIN, // Cookie domain must be set to false for localhost ( default value for COOKIE_DOMAIN ) thanks to Stephen Harris.
			'secure'   => is_ssl(),
			'httponly' => false,
			'samesite' => 'Lax',
		);

		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Sets the cookie.
	 *
	 * @since 2.9
	 *
	 * @param string $lang Language cookie value.
	 * @param array  $args {
	 *   Optional. Array of arguments for setting the cookie.
	 *
	 *   @type string $path     Cookie path, defaults to COOKIEPATH.
	 *   @type string $domain   Cookie domain, defaults to COOKIE_DOMAIN
	 *   @type bool   $secure   Should the cookie be sent only over https?
	 *   @type bool   $httponly Should the cookie accessed only over http protocol? Defaults to false.
	 *   @type string $samesite Either 'Strict', 'Lax' or 'None', defaults to 'Lax'.
	 * }
	 */
	public static function set( $lang, $args = array() ) {
		$args = self::parse_args( $args );

		if ( ! headers_sent() && PLL_COOKIE !== false && self::get() !== $lang ) {
			if ( version_compare( PHP_VERSION, '7.3', '<' ) ) {
				$args['path'] .= '; SameSite=' . $args['samesite']; // Hack to set SameSite value in PHP < 7.3. Doesn't work with newer versions.
				setcookie( PLL_COOKIE, $lang, $args['expires'], $args['path'], $args['domain'], $args['secure'], $args['httponly'] );
			} else {
				setcookie( PLL_COOKIE, $lang, $args );
			}
		}
	}

	/**
	 * Returns the language cookie value.
	 *
	 * @since 2.9
	 *
	 * @return string
	 */
	public static function get() {
		return isset( $_COOKIE[ PLL_COOKIE ] ) ? sanitize_key( $_COOKIE[ PLL_COOKIE ] ) : '';
	}
}
