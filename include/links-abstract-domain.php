<?php
/**
 * @package Polylang
 */

/**
 * Links model for use when using one domain or subdomain per language.
 *
 * @since 2.0
 */
abstract class PLL_Links_Abstract_Domain extends PLL_Links_Permalinks {

	/**
	 * Constructor.
	 *
	 * @since 2.0
	 *
	 * @param PLL_Model $model Instance of PLL_Model.
	 */
	public function __construct( &$model ) {
		parent::__construct( $model );

		// Avoid cross domain requests (mainly for custom fonts).
		add_filter( 'content_url', array( $this, 'site_url' ) );
		add_filter( 'theme_root_uri', array( $this, 'site_url' ) ); // The above filter is not sufficient with WPMU Domain Mapping.
		add_filter( 'plugins_url', array( $this, 'site_url' ) );
		add_filter( 'rest_url', array( $this, 'site_url' ) );
		add_filter( 'upload_dir', array( $this, 'upload_dir' ) );

		// Set the correct domain for each language.
		add_filter( 'pll_language_flag_url', array( $this, 'site_url' ) );
	}

	/**
	 * Returns the language based on the language code in url.
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

		$host = wp_parse_url( $url, PHP_URL_HOST );
		return ( $lang = array_search( $host, $this->get_hosts() ) ) ? $lang : '';
	}

	/**
	 * Modifies an url to use the domain associated to the current language.
	 *
	 * @since 1.8
	 *
	 * @param string $url The url to modify.
	 * @return string The modified url.
	 */
	public function site_url( $url ) {
		$lang = $this->get_language_from_url();

		$lang = $this->model->get_language( $lang );

		return $this->add_language_to_link( $url, $lang );
	}

	/**
	 * Fixes the domain for the upload directory.
	 *
	 * @since 2.0.6
	 *
	 * @param array $uploads Array of information about the upload directory. @see wp_upload_dir().
	 * @return array
	 */
	public function upload_dir( $uploads ) {
		$lang = $this->get_language_from_url();
		$lang = $this->model->get_language( $lang );
		$uploads['url'] = $this->add_language_to_link( $uploads['url'], $lang );
		$uploads['baseurl'] = $this->add_language_to_link( $uploads['baseurl'], $lang );
		return $uploads;
	}

	/**
	 * Adds home and search URLs to language data before the object is created.
	 *
	 * @since 3.4.1
	 *
	 * @param array $additional_data Array of language additional data.
	 * @param array $language        Language data.
	 * @return array Language data with home and search URLs added.
	 */
	public function set_language_home_urls( $additional_data, $language ) {
		$language = array_merge( $language, $additional_data );
		$additional_data['search_url'] = $this->home_url( $language['slug'] );
		$additional_data['home_url']   = $additional_data['search_url'];
		return $additional_data;
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
		return $this->home_url( $language['slug'] );
	}
}
