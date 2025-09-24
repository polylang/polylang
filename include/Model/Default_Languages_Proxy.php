<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Model;

use PLL_Language;

defined( 'ABSPATH' ) || exit;

/**
 * Default proxy the list of languages.
 *
 * @since 3.8
 */
class Default_Languages_Proxy extends Abstract_Languages_Proxy {
	/**
	 * Returns the proxy's key.
	 *
	 * @since 3.8
	 *
	 * @return string
	 *
	 * @phpstan-return non-falsy-string
	 */
	public function key(): string {
		return 'default';
	}

	/**
	 * Returns the list of available languages after passing it through this proxy.
	 *
	 * @since 3.8
	 *
	 * @param array $args Optional arguments to pass to `Languages::get_list()`.
	 * @return array List of `PLL_Language` objects or `PLL_Language` object properties.
	 */
	public function get_list( array $args = array() ): array {
		return $this->languages->get_list( $args );
	}
}
