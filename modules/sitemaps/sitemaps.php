<?php

/**
 * Handles the core sitemaps.
 *
 * @since 2.8
 */
class PLL_Sitemaps {
	/**
	 * A reference to teh PLL_Model instance.
	 *
	 * @since 2.8
	 *
	 * @var PLL_Model
	 */
	protected $model;

	/**
	 * A reference to the current language.
	 *
	 * @since 2.8
	 *
	 * @var PLL_Language
	 */
	protected $curlang;

	/**
	 * Constructor.
	 *
	 * @since 2.8
	 *
	 * @param object $polylang Main Polylang object.
	 */
	public function __construct( $polylang ) {
		$this->model = &$polylang->model;
		$this->curlang = &$polylang->curlang;
	}

	/**
	 * Setups actions and filters.
	 *
	 * @since 2.8
	 */
	public function init() {
		add_action( 'template_redirect', array( $this, 'set_language' ), 5 ); // Before WP_Sitemaps.
		add_filter( 'pll_home_url_white_list', array( $this, 'home_url_white_list' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'rewrite_rules' ) );
		add_filter( 'wp_sitemaps_register_providers', array( $this, 'providers' ), 99 ); // 99 in an attempt to have all sitemaps providers in our filter.
	}

	/**
	 * Sets the language for the current sitemap being rendered.
	 *
	 * @since 2.8
	 */
	public function set_language() {
		$qv = get_query_var( 'sitemap' );
		if ( $qv ) {
			$arr = explode( '-', $qv );
			$lang = end( $arr );
			$this->curlang = $this->model->get_language( $lang );
		}
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
		$whitelist[] = array( 'file' => 'sitemaps' );
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
		$new_rules = array();
		$languages = '(?:' . implode( '|', $this->get_active_languages() ) . ')';

		foreach ( $rules as $key => $rule ) {
			if ( false !== strpos( $rule, 'sitemap=$matches[1]' ) ) {
				$new_key = str_replace( 'wp-sitemap-([a-z]+?)', 'wp-sitemap-([a-z]+?-' . $languages . ')', $key );
				$new_rules[ $new_key ] = $rule;
			} else {
				$new_rules[ $key ] = $rule;
			}
		}
		return $new_rules;
	}

	/**
	 * Replaces the list of sitemaps providers by our decorators.
	 *
	 * @since 2.8
	 *
	 * @param array $providers Array of WP_Sitemaps_Provider objects keyed by their name.
	 * @return array
	 */
	public function providers( $providers ) {
		foreach ( $providers as $key => $provider ) {
			foreach ( $this->get_active_languages() as $lang ) {
				$new_providers[ "$key-$lang" ] = new PLL_Sitemaps_Provider_Decorator( $provider, $lang );
			}
		}
		return $new_providers;
	}

	/**
	 * Get active languages for the sitemap.
	 *
	 * @since 2.8
	 */
	protected function get_active_languages() {
		$languages = $this->model->get_languages_list();
		if ( wp_list_filter( $languages, array( 'active' => false ) ) ) {
			return wp_list_pluck( wp_list_filter( $languages, array( 'active' => false ), 'NOT' ), 'slug' );
		}
		return wp_list_pluck( $languages, 'slug' );
	}
}
