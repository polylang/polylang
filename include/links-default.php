<?php
/**
 * @package Polylang
 */

/**
 * Links model for the default permalinks
 * for example mysite.com/?somevar=something&lang=en.
 *
 * @since 1.2
 */
class PLL_Links_Default extends PLL_Links_Model {
	/**
	 * Tells this child class of PLL_Links_Model does not use pretty permalinks.
	 *
	 * @var bool
	 */
	public $using_permalinks = false;

	/**
	 * Adds the language code in a url.
	 *
	 * @since 1.2
	 * @since 3.4 Accepts now a language slug.
	 *
	 * @param string                    $url      The url to modify.
	 * @param PLL_Language|string|false $language Language object or slug.
	 * @return string The modified url.
	 */
	public function add_language_to_link( $url, $language ) {
		if ( $language instanceof PLL_Language ) {
			$language = $language->slug;
		}

		return empty( $language ) || ( $this->options['hide_default'] && $this->options['default_lang'] === $language ) ? $url : add_query_arg( 'lang', $language, $url );
	}

	/**
	 * Removes the language information from an url.
	 *
	 * @since 1.2
	 *
	 * @param string $url The url to modify.
	 * @return string The modified url.
	 */
	public function remove_language_from_link( $url ) {
		return remove_query_arg( 'lang', $url );
	}

	/**
	 * Returns the link to the first page.
	 *
	 * @since 1.2
	 *
	 * @param string $url The url to modify.
	 * @return string The modified url.
	 */
	public function remove_paged_from_link( $url ) {
		return remove_query_arg( 'paged', $url );
	}

	/**
	 * Returns the link to the paged page.
	 *
	 * @since 1.5
	 *
	 * @param string $url  The url to modify.
	 * @param int    $page The page number.
	 * @return string The modified url.
	 */
	public function add_paged_to_link( $url, $page ) {
		return add_query_arg( array( 'paged' => $page ), $url );
	}

	/**
	 * Gets the language slug from the url if present.
	 *
	 * @since 1.2
	 * @since 2.0 Add the $url argument.
	 *
	 * @param string $url Optional, defaults to the current url.
	 * @return string Language slug.
	 */
	public function get_language_from_url( $url = '' ) {
		if ( empty( $url ) ) {
			$url = pll_get_requested_url();
		}

		$pattern = sprintf(
			'#[?&]lang=(?<lang>%s)(?:$|&)#',
			implode( '|', $this->model->get_languages_list( array( 'fields' => 'slug' ) ) )
		);
		return preg_match( $pattern, $url, $matches ) ? $matches['lang'] : ''; // $matches['lang'] is the slug of the requested language.
	}

	/**
	 * Returns the static front page url in the given language.
	 *
	 * @since 1.8
	 * @since 3.4 Accepts now an array of language properties.
	 *
	 * @param PLL_Language|array $language Language object or array of language properties.
	 * @return string The static front page url.
	 */
	public function front_page_url( $language ) {
		if ( $language instanceof PLL_Language ) {
			$language = $language->to_array();
		}

		if ( $this->options['hide_default'] && $language['is_default'] ) {
			return trailingslashit( $this->home );
		}
		$url = home_url( '/?page_id=' . $language['page_on_front'] );
		return $this->options['force_lang'] ? $this->add_language_to_link( $url, $language['slug'] ) : $url;
	}
}
