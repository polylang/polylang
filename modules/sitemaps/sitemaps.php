<?php
/**
 * @package Polylang
 */

/**
 * Handles the core sitemaps.
 *
 * @since 2.8
 */
class PLL_Sitemaps {
	/**
	 * A reference to the current language.
	 *
	 * @since 2.8
	 *
	 * @var PLL_Language
	 */
	protected $curlang;

	/**
	 * A reference to the PLL_Links_Model instance.
	 *
	 * @since 2.8
	 *
	 * @var PLL_Links_Model
	 */
	protected $links_model;

	/**
	 * A reference to the PLL_Model instance.
	 *
	 * @since 2.8
	 *
	 * @var PLL_Model
	 */
	protected $model;

	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Constructor.
	 *
	 * @since 2.8
	 *
	 * @param object $polylang Main Polylang object.
	 */
	public function __construct( &$polylang ) {
		$this->curlang = &$polylang->curlang;
		$this->links_model = &$polylang->links_model;
		$this->model = &$polylang->model;
		$this->options = &$polylang->options;
	}

	/**
	 * Setups actions and filters.
	 *
	 * @since 2.8
	 */
	public function init() {
		add_filter( 'pll_home_url_white_list', array( $this, 'home_url_white_list' ) );

		if ( $this->options['force_lang'] < 2 ) {
			add_filter( 'pll_set_language_from_query', array( $this, 'set_language_from_query' ), 10, 2 );
			add_filter( 'rewrite_rules_array', array( $this, 'rewrite_rules' ) );
			add_filter( 'wp_sitemaps_add_provider', array( $this, 'replace_provider' ) );
		} else {
			add_filter( 'wp_sitemaps_index_entry', array( $this, 'index_entry' ) );
			add_filter( 'wp_sitemaps_stylesheet_url', array( $this->links_model, 'site_url' ) );
			add_filter( 'wp_sitemaps_stylesheet_index_url', array( $this->links_model, 'site_url' ) );
			add_filter( 'home_url', array( $this, 'sitemap_url' ) );
		}
	}

	/**
	 * Assigns the current language to the default language when the sitemap url
	 * doesn't include any language.
	 *
	 * @since 2.8
	 *
	 * @param string|bool $lang  Current language code, false if not set yet.
	 * @param object      $query Main WP query object.
	 * @return string|bool
	 */
	public function set_language_from_query( $lang, $query ) {
		if ( isset( $query->query['sitemap'] ) && empty( $query->query['lang'] ) ) {
			$lang = $this->options['default_lang'];
		}
		return $lang;
	}

	/**
	 * Whitelists the home url filter for the sitemaps
	 *
	 * @since 2.8
	 *
	 * @param array $whitelist White list.
	 * @return array;
	 */
	public function home_url_white_list( $whitelist ) {
		$whitelist[] = array( 'file' => 'class-wp-sitemaps-posts' );
		return $whitelist;
	}

	/**
	 * Filters the sitemaps rewrite rules to take the languages into account.
	 *
	 * @since 2.8
	 *
	 * @param array $rules Rewrite rules.
	 * @return array
	 */
	public function rewrite_rules( $rules ) {
		global $wp_rewrite;

		$newrules = array();

		$languages = $this->model->get_languages_list( array( 'fields' => 'slug' ) );
		if ( $this->options['hide_default'] ) {
			$languages = array_diff( $languages, array( $this->options['default_lang'] ) );
		}

		if ( ! empty( $languages ) ) {
			$slug = $wp_rewrite->root . ( $this->options['rewrite'] ? '^' : '^language/' ) . '(' . implode( '|', $languages ) . ')/';
		}

		foreach ( $rules as $key => $rule ) {
			if ( isset( $slug ) && false !== strpos( $rule, 'sitemap=$matches[1]' ) ) {
				$newrules[ str_replace( '^wp-sitemap', $slug . 'wp-sitemap', $key ) ] = str_replace(
					array( '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '[1]', '?' ),
					array( '[9]', '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '?lang=$matches[1]&' ),
					$rule
				); // Should be enough!
			}

			$newrules[ $key ] = $rule;
		}
		return $newrules;
	}

	/**
	 * Replaces a sitemap provider by our decorator.
	 *
	 * @since 2.8
	 *
	 * @param WP_Sitemaps_Provider $provider Instance of a WP_Sitemaps_Provider.
	 * @return WP_Sitemaps_Provider
	 */
	public function replace_provider( $provider ) {
		if ( $provider instanceof WP_Sitemaps_Provider ) {
			$provider = new PLL_Multilingual_Sitemaps_Provider( $provider, $this->links_model );
		}
		return $provider;
	}

	/**
	 * Filters the sitemap index entries for subdomains and multiple domains.
	 *
	 * @since 2.8
	 *
	 * @param array $sitemap_entry Sitemap entry for the post.
	 * return array
	 */
	public function index_entry( $sitemap_entry ) {
		$sitemap_entry['loc'] = $this->links_model->site_url( $sitemap_entry['loc'] );
		return $sitemap_entry;
	}

	/**
	 * Makes sure that the sitemap urls are always evaluated on the current domain.
	 *
	 * @since 2.8.4
	 *
	 * @param string $url A sitemap url.
	 * @return string
	 */
	public function sitemap_url( $url ) {
		if ( false !== strpos( $url, '/wp-sitemap' ) ) {
			$url = $this->links_model->site_url( $url );
		}
		return $url;
	}
}
