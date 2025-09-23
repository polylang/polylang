<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Model;

use PLL_Language;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class allowing to proxy the list of languages.
 *
 * @since 3.8
 */
abstract class Abstract_Languages_Proxy {
	/**
	 * @var Languages
	 */
	protected $languages;

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param Languages $languages The object to proxy.
	 */
	public function __construct( Languages $languages ) {
		$this->languages = $languages;
	}

	/**
	 * Returns the proxy's key.
	 *
	 * @since 3.8
	 *
	 * @return string
	 *
	 * @phpstan-return non-falsy-string
	 */
	abstract public static function key(): string;

	/**
	 * Returns the list of available languages after passing it through this proxy.
	 *
	 * @since 3.8
	 *
	 * @param array $args Optional arguments to pass to `Languages::get_list()`.
	 * @return array List of `PLL_Language` objects or `PLL_Language` object properties.
	 */
	abstract public function get_list( array $args = array() ): array;
}
