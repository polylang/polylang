<?php

use Brain\Monkey\Functions;

/**
 * Trait containing commonly used mocks.
 */
trait PLL_Mocks_Trait {

	/**
	 * Mocks `PLL_CACHE_LANGUAGES` and `PLL_CACHE_HOME_URL` constants.
	 *
	 * @param bool $cache_languages Value of the constant `PLL_CACHE_LANGUAGES`.
	 * @param bool $cache_home_url  Value of the constant `PLL_CACHE_HOME_URL`.
	 * @return void
	 */
	private function mock_cache_url_constants( $cache_languages, $cache_home_url ) {
		Functions\when( 'pll_get_constant' )->alias(
			function ( $constant_name, $default = null ) use ( $cache_languages, $cache_home_url ) {
				switch ( $constant_name ) {
					case 'PLL_CACHE_LANGUAGES':
						return $cache_languages;
					case 'PLL_CACHE_HOME_URL':
						return $cache_home_url;
					default:
						return defined( $constant_name ) ? constant( $constant_name ) : $default;
				}
			}
		);
	}
}
