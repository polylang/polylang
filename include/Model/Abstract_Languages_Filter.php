<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Model;

use PLL_Language;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class allowing to filter the list of languages.
 *
 * @since 3.8
 */
abstract class Abstract_Languages_Filter {
	/**
	 * Filters a list of languages after putting it into local cache.
	 *
	 * @since 3.8
	 *
	 * @param PLL_Language[] $languages List of languages to filter.
	 * @param array          $args      Arguments.
	 * @return PLL_Language[]
	 */
	public function apply_after_cache( array $languages, array $args ): array { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $languages;
	}
}
