<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Model;

use PLL_Language;

defined( 'ABSPATH' ) || exit;

/**
 * Interface allowing to proxy the list of languages.
 *
 * @since 3.8
 */
interface Languages_Proxy_Interface {
	/**
	 * Returns the proxy's key.
	 *
	 * @since 3.8
	 *
	 * @return string
	 *
	 * @phpstan-return non-falsy-string
	 */
	public function key(): string;

	/**
	 * Returns the list of available languages after passing it through this proxy.
	 *
	 * @since 3.8
	 *
	 * @param PLL_Language[] $languages List of languages to filter.
	 * @return PLL_Language[]
	 */
	public function filter( array $languages ): array;
}
