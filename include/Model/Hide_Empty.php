<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Model;

/**
 * Class to filter the list of languages to only include non-empty languages.
 */
class Hide_Empty implements Languages_Proxy_Interface {
	/**
	 * Returns the proxy's key.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public function key(): string {
		return 'hide_empty';
	}

	/**
	 * Returns the list of non-empty languages after passing it through this proxy.
	 *
	 * @since 3.8
	 *
	 * @param \PLL_Language[] $languages List of languages to filter.
	 * @return \PLL_Language[] Filtered languages.
	 */
	public function filter( array $languages ): array {
		return array_filter(
			$languages,
			static function ( $lang ) {
				return $lang->get_tax_prop( 'language', 'count' ) > 0;
			}
		);
	}
}
