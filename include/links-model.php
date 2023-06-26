<?php
/**
 * @package Polylang
 */

/**
 * Links model abstract class.
 *
 * @since 1.5
 */
abstract class PLL_Links_Model {
	/**
	 * True if the child class uses pretty permalinks, false otherwise.
	 *
	 * @var bool
	 */
	public $using_permalinks;

	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * Stores the home url before it is filtered.
	 *
	 * @var string
	 */
	public $home;

	/**
	 * Constructor.
	 *
	 * @since 1.5
	 *
	 * @param PLL_Model $model PLL_Model instance.
	 */
	public function __construct( &$model ) {
		$this->model   = &$model;
		$this->options = &$model->options;

		$this->home = home_url();

		// Hooked with normal priority because it needs to be run after static pages is set in language data.
		add_filter( 'pll_additional_language_data', array( $this, 'set_language_home_urls' ), 10, 2 );

		// Adds our domains or subdomains to allowed hosts for safe redirection.
		add_filter( 'allowed_redirect_hosts', array( $this, 'allowed_redirect_hosts' ) );

		// Allows secondary domains for home and search URLs in `PLL_Language`.
		add_filter( 'pll_language_home_url', array( $this, 'set_language_home_url' ), 10, 2 );
		add_filter( 'pll_language_search_url', array( $this, 'set_language_search_url' ), 10, 2 );
	}

	/**
	 * Adds the language code in url.
	 *
	 * @since 1.2
	 * @since 3.4 Accepts now a language slug.
	 *
	 * @param string                    $url  The url to modify.
	 * @param PLL_Language|string|false $lang Language object or slug.
	 * @return string The modified url.
	 */
	abstract public function add_language_to_link( $url, $lang );

	/**
	 * Returns the url without language code.
	 *
	 * @since 1.2
	 *
	 * @param string $url The url to modify.
	 * @return string The modified url.
	 */
	abstract public function remove_language_from_link( $url );

	/**
	 * Returns the link to the first page.
	 *
	 * @since 1.2
	 *
	 * @param string $url The url to modify.
	 * @return string The modified url.
	 */
	abstract public function remove_paged_from_link( $url );

	/**
	 * Returns the link to a paged page.
	 *
	 * @since 1.5
	 *
	 * @param string $url  The url to modify.
	 * @param int    $page The page number.
	 * @return string The modified url.
	 */
	abstract public function add_paged_to_link( $url, $page );

	/**
	 * Returns the language based on the language code in the url.
	 *
	 * @since 1.2
	 * @since 2.0 Add the $url argument.
	 *
	 * @param string $url Optional, defaults to the current url.
	 * @return string The language slug.
	 */
	abstract public function get_language_from_url( $url = '' );

	/**
	 * Returns the static front page url in a given language.
	 *
	 * @since 1.8
	 * @since 3.4 Accepts now an array of language properties.
	 *
	 * @param PLL_Language|array $language Language object or array of language properties.
	 * @return string The static front page url.
	 */
	abstract public function front_page_url( $language );

	/**
	 * Changes the language code in url.
	 *
	 * @since 1.5
	 *
	 * @param string       $url  The url to modify.
	 * @param PLL_Language $lang The language object.
	 * @return string The modified url.
	 */
	public function switch_language_in_link( $url, $lang ) {
		$url = $this->remove_language_from_link( $url );
		return $this->add_language_to_link( $url, $lang );
	}

	/**
	 * Get the hosts managed on the website.
	 *
	 * @since 1.5
	 *
	 * @return string[] The list of hosts.
	 */
	public function get_hosts() {
		return array( wp_parse_url( $this->home, PHP_URL_HOST ) );
	}

	/**
	 * Returns the home url in a given language.
	 *
	 * @since 1.3.1
	 * @since 3.4 Accepts now a language slug.
	 *
	 * @param PLL_Language|string $language Language object or slug.
	 * @return string
	 */
	public function home_url( $language ) {
		if ( $language instanceof PLL_Language ) {
			$language = $language->slug;
		}

		$url = trailingslashit( $this->home );

		return $this->options['hide_default'] && $language === $this->options['default_lang'] ? $url : $this->add_language_to_link( $url, $language );
	}

	/**
	 * Adds home and search URLs to language data before the object is created.
	 *
	 * @since 3.4
	 *
	 * @param array $additional_data Array of language additional data.
	 * @param array $language        Language data.
	 * @return array Language data with home and search URLs added.
	 */
	public function set_language_home_urls( $additional_data, $language ) {
		$language = array_merge( $language, $additional_data );
		$additional_data['search_url'] = $this->set_language_search_url( '', $language );
		$additional_data['home_url']   = $this->set_language_home_url( '', $language );

		return $additional_data;
	}

	/**
	 * Adds our domains or subdomains to allowed hosts for safe redirect.
	 *
	 * @since 1.4.3
	 *
	 * @param string[] $hosts Allowed hosts.
	 * @return string[] Modified list of allowed hosts.
	 */
	public function allowed_redirect_hosts( $hosts ) {
		return array_unique( array_merge( $hosts, array_values( $this->get_hosts() ) ) );
	}

	/**
	 * Returns language home URL property according to the current domain.
	 *
	 * @since 3.4.4
	 *
	 * @param string $url      Home URL.
	 * @param array  $language Array of language props.
	 * @return string Filtered home URL.
	 */
	public function set_language_home_url( $url, $language ) {
		if ( empty( $language['page_on_front'] ) || $this->options['redirect_lang'] ) {
			return $this->home_url( $language['slug'] );
		}

		return $this->front_page_url( $language );
	}

	/**
	 * Returns language search URL property according to the current domain.
	 *
	 * @since 3.4.4
	 *
	 * @param string $url      Search URL.
	 * @param array  $language Array of language props.
	 * @return string Filtered search URL.
	 */
	public function set_language_search_url( $url, $language ) {
		return $this->home_url( $language['slug'] );
	}
}
