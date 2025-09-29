<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Model;

use PLL_Language;

defined( 'ABSPATH' ) || exit;

/**
 * Class allowing to chain language proxies.
 *
 * @since 3.8
 */
class Languages_Proxies {
	/**
	 * @var Languages
	 */
	protected $languages;

	/**
	 * @var Languages_Proxy_Interface[]
	 *
	 * @phpstan-var array<non-falsy-string, Languages_Proxy_Interface>
	 */
	private $proxies = array();

	/**
	 * @var string[]
	 */
	protected $stack = array();

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param Languages $languages Languages' model.
	 * @param array     $proxies   List of registered proxies.
	 * @param string    $parent    Key of the first item of the proxies stack to traverse.
	 */
	public function __construct( Languages $languages, array $proxies, string $parent ) {
		$this->languages = $languages;
		$this->proxies   = $proxies;
		$this->stack[]   = $parent;
	}

	/**
	 * Returns the list of available languages after passing it through proxies.
	 *
	 * @since 3.8
	 *
	 * @param array $args Optional arguments to pass to `Languages::get_list()`.
	 * @return array List of `PLL_Language` objects or `PLL_Language` object properties.
	 */
	public function get_list( array $args = array() ): array {
		$all_args = $args;
		unset( $args['fields'] );

		$languages = $this->languages->get_list( $args );

		foreach ( $this->stack as $key ) {
			if ( ! isset( $this->proxies[ $key ] ) ) {
				continue;
			}
			$languages = $this->proxies[ $key ]->filter( $languages );
		}

		$languages = array_values( $languages ); // Re-index.

		return $this->languages->maybe_convert_list( $languages, $all_args );
	}

	/**
	 * Stacks a proxy that will filter the list of languages.
	 *
	 * @since 3.8
	 *
	 * @param string $key Proxy's key.
	 * @return Languages_Proxies
	 */
	public function filter( string $key ): Languages_Proxies {
		$this->stack[] = $key;
		return $this;
	}
}
