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
	public function __construct( $polylang ) {
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
		add_filter( 'pll_set_language_from_query', array( $this, 'set_language_from_query' ), 10, 2 );
		add_filter( 'pll_home_url_white_list', array( $this, 'home_url_white_list' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'rewrite_rules' ) );
		add_filter( 'wp_sitemaps_register_providers', array( $this, 'providers' ), 99 ); // 99 in an attempt to have all sitemaps providers in our filter.
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
			if ( false !== strpos( $rule, 'sitemap=$matches[1]' ) ) {
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
	 * Replaces the list of sitemaps providers by our decorators.
	 *
	 * @since 2.8
	 *
	 * @param array $providers Array of Sitemaps provider objects keyed by their name.
	 * @return array
	 */
	public function providers( $providers ) {
		$new_providers = array();

		foreach ( $providers as $key => $provider ) {
			if ( $provider instanceof WP_Sitemaps_Provider ) {
				$new_providers[ $key ] = new PLL_Multilingual_Sitemaps_Provider( $provider, $this->links_model );
			} else {
				$new_providers[ $key ] = $provider; // Just in case some 3rd party doesn't extend WP_Sitemaps_Provider.
			}
		}

		return $new_providers;
	}
}
