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

		add_filter( 'pll_languages_list', array( $this, 'pll_languages_list' ), 4 ); // After PLL_Static_Pages.
		add_filter( 'pll_after_languages_cache', array( $this, 'pll_after_languages_cache' ) );

		// Adds our domains or subdomains to allowed hosts for safe redirection.
		add_filter( 'allowed_redirect_hosts', array( $this, 'allowed_redirect_hosts' ) );
	}

	/**
	 * Adds the language code in url.
	 *
	 * @since 1.2
	 *
	 * @param string       $url  The url to modify.
	 * @param PLL_Language $lang The language object.
	 * @return string Modified url.
	 */
	abstract public function add_language_to_link( $url, $lang );

	/**
	 * Returns the url without language code.
	 *
	 * @since 1.2
	 *
	 * @param string $url The url to modify.
	 * @return string Modified url.
	 */
	abstract public function remove_language_from_link( $url );

	/**
	 * Returns the link to the first page.
	 *
	 * @since 1.2
	 *
	 * @param string $url The url to modify.
	 * @return string Modified url.
	 */
	abstract public function remove_paged_from_link( $url );

	/**
	 * Returns the link to a paged page.
	 *
	 * @since 1.5
	 *
	 * @param string $url  The url to modify.
	 * @param int    $page The page number.
	 * @return string Modified url.
	 */
	abstract public function add_paged_to_link( $url, $page );

	/**
	 * Returns the language based on language code in url.
	 *
	 * @since 1.2
	 * @since 2.0 add $url argument.
	 *
	 * @param string $url Optional, defaults to thej current url.
	 * @return string Language slug.
	 */
	abstract public function get_language_from_url( $url = '' );

	/**
	 * Returns the static front page url.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Language $lang The language object.
	 * @return string The static front page url.
	 */
	abstract public function front_page_url( $lang );

	/**
	 * Changes the language code in url.
	 *
	 * @since 1.5
	 *
	 * @param string       $url  The url to modify.
	 * @param PLL_Language $lang The language object.
	 * @return string Modified url.
	 */
	public function switch_language_in_link( $url, $lang ) {
		$url = $this->remove_language_from_link( $url );
		return $this->add_language_to_link( $url, $lang );
	}

	/**
	 * Get hosts managed on the website.
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
	 *
	 * @param PLL_Language $lang PLL_Language object.
	 * @return string
	 */
	public function home_url( $lang ) {
		$url = trailingslashit( $this->home );
		return $this->options['hide_default'] && $lang->slug == $this->options['default_lang'] ? $url : $this->add_language_to_link( $url, $lang );
	}

	/**
	 * Sets the home urls in PLL_Language.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Language $language PLL_Language object.
	 * @return void
	 */
	protected function set_home_url( $language ) {
		// We should always have a default language here, except, temporarily, in PHPUnit tests. The test here protects against PHP notices.
		if ( isset( $this->options['default_lang'] ) ) {
			$search_url = $this->home_url( $language );
			$home_url = empty( $language->page_on_front ) || $this->options['redirect_lang'] ? $search_url : $this->front_page_url( $language );
			$language->set_home_url( $search_url, $home_url );
		}
	}

	/**
	 * Sets the home urls and flags before the languages are persistently cached.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Language[] $languages Array of PLL_Language objects.
	 * @return PLL_Language[] Array of PLL_Language objects with home url and flag.
	 */
	public function pll_languages_list( $languages ) {
		foreach ( $languages as $language ) {
			$this->set_home_url( $language );
			$language->set_flag();
		}
		return $languages;
	}

	/**
	 * Sets the home urls when not cached.
	 * Sets the home urls scheme.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Language[] $languages Array of PLL_Language objects.
	 * @return PLL_Language[] Array of PLL_Language objects.
	 */
	public function pll_after_languages_cache( $languages ) {
		foreach ( $languages as $language ) {
			// Get the home urls when not cached.
			if ( ( defined( 'PLL_CACHE_LANGUAGES' ) && ! PLL_CACHE_LANGUAGES ) || ( defined( 'PLL_CACHE_HOME_URL' ) && ! PLL_CACHE_HOME_URL ) ) {
				$this->set_home_url( $language );
			}

			// Ensures that the ( possibly cached ) home and flag urls use the right scheme http or https.
			$language->set_url_scheme();
		}
		return $languages;
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
}
