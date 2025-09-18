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
	public static function key(): string {
		return 'default';
	}

	/**
	 * Filters the given list of languages, according to this proxy.
	 *
	 * @since 3.8
	 *
	 * @param PLL_Language[] $languages The list of language objects.
	 * @param array          $args      Optional arguments passed to `get_list()`.
	 * @return PLL_Language[]
	 */
	protected function filter_list( array $languages, array $args ): array { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $languages;
	}
}
