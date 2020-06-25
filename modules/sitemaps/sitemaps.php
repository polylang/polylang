<?php
/**
 * @package Polylang
 */

/**
 * Handles the WP sitemaps.
 *
 * @since 2.8
 */
class PLL_Sitemaps {
	/**
	 * Constructor.
	 *
	 * @since 2.8
	 *
	 * @param object $polylang Main Polylang object.
	 */
	public function __construct( $polylang ) {
		$this->model = &$polylang->model;
		$this->links_model = &$polylang->links_model;
	}

	/**
	 * Setups filters.
	 *
	 * @since 2.8
	 */
	public function init() {
		if ( $polylang->options['force_lang'] <= 1 ) {
			add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'query_args' ), 10, 2 );
			add_filter( 'wp_sitemaps_taxonomies_query_args', array( $this, 'query_args' ), 10, 2 );
		}
	}

	/**
	 * Adds all active languages to the query.
	 *
	 * @since 2.8
	 *
	 * @param array  $args      Array of WP_Query arguments.
	 * @param string $post_type Post type name.
	 * @return array
	 */
	public function query_args( $args, $post_type ) {
		if ( $this->model->is_translated_post_type( $post_type ) ) {
			$args['lang'] = implode( ',', $this->get_active_languages() );
		}
		return $args;
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
