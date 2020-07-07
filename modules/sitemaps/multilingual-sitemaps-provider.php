<?php
/**
 * @package Polylang
 */

/**
 * Decorator to add multilingual capability to sitemaps providers
 *
 * @since 2.8
 */
class PLL_Multilingual_Sitemaps_Provider extends WP_Sitemaps_Provider {
	/**
	 * The decorated sitemaps provider.
	 *
	 * @since 2.8
	 *
	 * @var WP_Sitemaps_Provider
	 */
	protected $provider;

	/**
	 * The PLL_Links_Model instance.
	 *
	 * @since 2.8
	 *
	 * @var PLL_Links_Model
	 */
	protected $links_model;

	/**
	 * The PLL_Model instance.
	 *
	 * @since 2.8
	 *
	 * @var PLL_Model
	 */
	protected $model;


	/**
	 * Language used to filter queries for the sitemap index.
	 *
	 * @since 2.8
	 *
	 * @var string
	 */
	private $filter_lang;

	/**
	 * Constructor.
	 *
	 * @since 2.8
	 *
	 * @param WP_Sitemaps_Provider $provider    An instance of a WP_Sitemaps_Provider child class.
	 * @param PLL_Links_Model      $links_model The PLL_Links_Model instance.
	 */
	public function __construct( $provider, $links_model ) {
		$this->name = $provider->name;
		$this->object_type = $provider->object_type;

		$this->provider = $provider;
		$this->links_model = $links_model;
		$this->model = $links_model->model;
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

	/**
	 * Filters the query arguments to add the language.
	 *
	 * @since 2.8
	 *
	 * @param array $args Sitemap provider WP_Query or WP_Term_Query arguments.
	 * @return array
	 */
	public function query_args( $args ) {
		if ( isset( $this->filter_lang ) ) {
			$args['lang'] = $this->filter_lang;
		}
		return $args;
	}

	/**
	 * Gets data for a given sitemap type.
	 *
	 * @since 2.8
	 *
	 * @param string $object_subtype_name Object subtype name if any.
	 * @param string $lang                Optionnal language name.
	 * @return array
	 */
	protected function get_sitemap_data( $object_subtype_name, $lang = '' ) {
		$object_subtype_name = (string) $object_subtype_name;

		if ( ! empty( $lang ) ) {
			$this->filter_lang = $lang;
		}

		$return = array(
			'name'  => implode( '-', array_filter( array( $object_subtype_name, $lang ) ) ),
			'pages' => $this->get_max_num_pages( $object_subtype_name ),
		);

		unset( $this->filter_lang );
		return $return;
	}

	/**
	 * Gets data about each sitemap type.
	 *
	 * @since 2.8
	 *
	 * @return array[] Array of sitemap types including object subtype name and number of pages.
	 */
	public function get_sitemap_type_data() {
		$sitemap_data = array();

		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'query_args' ) );
		add_filter( 'wp_sitemaps_taxonomies_query_args', array( $this, 'query_args' ) );

		$object_subtypes = $this->get_object_subtypes();

		if ( empty( $object_subtypes ) ) {
			foreach ( $this->get_active_languages() as $language ) {
				$sitemap_data[] = $this->get_sitemap_data( '', $language );
			}
		}

		foreach ( array_keys( $object_subtypes ) as $object_subtype_name ) {
			switch ( $this->provider->name ) {
				case 'posts':
					if ( $this->model->is_translated_post_type( $object_subtype_name ) ) {
						foreach ( $this->get_active_languages() as $language ) {
							$sitemap_data[] = $this->get_sitemap_data( $object_subtype_name, $language );
						}
					} else {
						$sitemap_data[] = $this->get_sitemap_data( $object_subtype_name );
					}
					break;
				case 'taxonomies':
					if ( $this->model->is_translated_taxonomy( $object_subtype_name ) ) {
						foreach ( $this->get_active_languages() as $language ) {
							$sitemap_data[] = $this->get_sitemap_data( $object_subtype_name, $language );
						}
					} else {
						$sitemap_data[] = $this->get_sitemap_data( $object_subtype_name );
					}
					break;
			}
		}

		return $sitemap_data;
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
		$parts = explode( '-', $name );
		$lang = end( $parts );
		$lang = $this->model->get_language( $lang ); // Validates that we got an existing language code.

		if ( $lang ) {
			$name = preg_replace( '#(-?' . $lang->slug . ')$#', '', $name );
			$url = $this->provider->get_sitemap_url( $name, $page );
			$url = $this->links_model->add_language_to_link( $url, $lang );
		}

		return $url;
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
