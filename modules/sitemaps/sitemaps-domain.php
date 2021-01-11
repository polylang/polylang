<?php
/**
 * @package Polylang
 */

/**
 * Handles the core sitemaps for subdomains and multiple domains.
 *
 * @since 3.0
 */
class PLL_Sitemaps_Domain extends PLL_Abstract_Sitemaps {
	/**
	 * @var PLL_Links_Abstract_Domain
	 */
	protected $links_model;

	/**
	 * Constructor.
	 *
	 * @since 3.0
	 *
	 * @param object $polylang Main Polylang object.
	 */
	public function __construct( &$polylang ) {
		$this->links_model = &$polylang->links_model;
	}

	/**
	 * Setups actions and filters.
	 *
	 * @since 3.0
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

		add_filter( 'wp_sitemaps_index_entry', array( $this, 'index_entry' ) );
		add_filter( 'wp_sitemaps_stylesheet_url', array( $this->links_model, 'site_url' ) );
		add_filter( 'wp_sitemaps_stylesheet_index_url', array( $this->links_model, 'site_url' ) );
		add_filter( 'home_url', array( $this, 'sitemap_url' ) );
	}

	/**
	 * Filters the sitemap index entries for subdomains and multiple domains.
	 *
	 * @since 2.8
	 *
	 * @param array $sitemap_entry Sitemap entry for the post.
	 * @return array
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
