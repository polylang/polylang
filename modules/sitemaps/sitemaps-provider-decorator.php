<?php
/**
 * @package Polylang
 */

/**
 * Sitemaps providers decorator
 *
 * @since 2.8
 */
class PLL_Sitemaps_Provider_Decorator extends WP_Sitemaps_Provider {
	/**
	 * The decorated sitemaps provider.
	 *
	 * @since 2.8
	 *
	 * @var WP_Sitemaps_Provider
	 */
	protected $provider;

	/**
	 * Language code.
	 *
	 * @since 2.8
	 *
	 * @var string
	 */
	protected $lang;

	/**
	 * Constructor.
	 *
	 * @since 2.8
	 *
	 * @param WP_Sitemaps_Provider $provider An instance of a WP_Sitemaps_Provider child class.
	 * @param string               $lang     Language code.
	 */
	public function __construct( $provider, $lang ) {
		$this->name = $provider->name . '-' . $lang;
		$this->object_type = $provider->object_type;

		$this->provider = $provider;
		$this->lang = $lang;
	}

	/**
	 * Gets a URL list for a sitemap.
	 *
	 * @since 2.8
	 *
	 * @param int    $page_num       Page of results.
	 * @param string $object_subtype Optional. Object subtype name. Default empty.
	 * @return array Array of URLs for a sitemap.
	 */
	public function get_url_list( $page_num, $object_subtype = '' ) {
		return $this->provider->get_url_list( $page_num, $object_subtype );
	}

	/**
	 * Gets the max number of pages available for the object type.
	 *
	 * @since 2.8
	 *
	 * @param string $object_subtype Optional. Object subtype. Default empty.
	 * @return int Total number of pages.
	 */
	public function get_max_num_pages( $object_subtype = '' ) {
		return $this->provider->get_max_num_pages( $object_subtype );
	}

	/**
	 * Gets the URL of a sitemap entry.
	 *
	 * @since 2.8
	 *
	 * @param string $name The name of the sitemap.
	 * @param int    $page The page of the sitemap.
	 * @return string The composed URL for a sitemap entry.
	 */
	public function get_sitemap_url( $name, $page ) {
		$url = $this->provider->get_sitemap_url( $name, $page );
		return str_replace( $this->provider->name, $this->name, $url );
	}

	/**
	 * Returns the list of supported object subtypes exposed by the provider.
	 *
	 * @since 2.8
	 *
	 * @return array List of object subtypes objects keyed by their name.
	 */
	public function get_object_subtypes() {
		return $this->provider->get_object_subtypes();
	}
}
