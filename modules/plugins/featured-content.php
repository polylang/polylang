<?php

/**
 * Manages the compatibility with the Jetpack Twenty Fourteenn Featured content
 *
 * @since 2.4
 */
class PLL_Featured_Content {
	/**
	 * Constructor
	 *
	 * @since 2.4
	 */
	public function __construct() {
		add_filter( 'transient_featured_content_ids', array( $this, 'featured_content_ids' ) );
		add_filter( 'pll_filter_query_excluded_query_vars', array( $this, 'fix_featured_posts' ) );
		add_filter( 'option_featured-content', array( $this, 'option_featured_content' ) );
	}

	/**
	 * Tell whether the theme supports the featured content
	 *
	 * @since 2.4
	 *
	 * @return bool
	 */
	protected function is_active() {
		return 'twentyfourteen' === get_template() || ( defined( 'JETPACK__VERSION' ) && get_theme_support( 'featured-content' ) );
	}

	/**
	 * Get the theme featured posts filter name
	 *
	 * @since 2.4
	 *
	 * @return string
	 */
	protected function get_featured_posts_filter() {
		$theme_support = get_theme_support( 'featured-content' );

		if ( isset( $theme_support[0]['featured_content_filter'] ) ) {
			$theme_support[0]['filter'] = $theme_support[0]['featured_content_filter'];
			unset( $theme_support[0]['featured_content_filter'] );
		}

		return $theme_support[0]['filter'];
	}

	/**
	 * Rewrites the function Featured_Content::get_featured_post_ids()
	 *
	 * @since 1.4
	 *
	 * @param array $featured_ids Featured posts ids
	 * @return array modified featured posts ids ( include all languages )
	 */
	public function featured_content_ids( $featured_ids ) {
		if ( ! $this->is_active() || ! did_action( 'pll_init' ) || false !== $featured_ids ) {
			return $featured_ids;
		}

		$settings = Featured_Content::get_setting();

		if ( ! $term = wpcom_vip_get_term_by( 'name', $settings['tag-name'], 'post_tag' ) ) {
			return $featured_ids;
		}

		// Get featured tag translations
		$tags = PLL()->model->term->get_translations( $term->term_id );
		$ids = array();

		// Query for featured posts in all languages
		// One query per language to get the correct number of posts per language
		foreach ( $tags as $tag ) {
			$_ids = get_posts(
				array(
					'lang'        => 0, // avoid language filters
					'fields'      => 'ids',
					'numberposts' => Featured_Content::$max_posts,
					'tax_query'   => array(
						array(
							'taxonomy' => 'post_tag',
							'terms'    => (int) $tag,
						),
					),
				)
			);

			$ids = array_merge( $ids, $_ids );
		}

		$ids = array_map( 'absint', $ids );
		set_transient( 'featured_content_ids', $ids );

		return $ids;
	}

	/**
	 * Allow to filter the featured posts query per language
	 *
	 * @since 2.4
	 *
	 * @param array $excludes Query vars excluded from the language filter
	 * @return array
	 */
	public function fix_featured_posts( $excludes ) {
		if ( $this->is_active() && PLL() instanceof PLL_Frontend && doing_filter( $this->get_featured_posts_filter() ) ) {
			$excludes = array_diff( $excludes, array( 'post__in' ) );
		}
		return $excludes;
	}

	/**
	 * Translates the featured tag id in featured content settings
	 * Mainly to allow hiding it when requested in featured content options
	 * Acts only on frontend
	 *
	 * @since 1.4
	 *
	 * @param array $settings featured content settings
	 * @return array modified $settings
	 */
	public function option_featured_content( $settings ) {
		if ( $this->is_active() && PLL() instanceof PLL_Frontend && $settings['tag-id'] && $tr = pll_get_term( $settings['tag-id'] ) ) {
			$settings['tag-id'] = $tr;
		}

		return $settings;
	}
}
