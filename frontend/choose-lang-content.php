<?php
/**
 * @package Polylang
 */

/**
 * Choose the language when it is set from content
 * The language is set either in parse_query with priority 2 or in wp with priority 5
 *
 * @since 1.2
 */
class PLL_Choose_Lang_Content extends PLL_Choose_Lang {

	/**
	 * Defers the language choice to the 'wp' action (when the content is known)
	 *
	 * @since 1.8
	 */
	public function init() {
		parent::init();

		if ( ! did_action( 'pll_language_defined' ) ) {
			// Set the languages from content
			add_action( 'wp', array( $this, 'wp' ), 5 ); // Priority 5 for post types and taxonomies registered in wp hook with default priority

			// If no language found, choose the preferred one
			add_filter( 'pll_get_current_language', array( $this, 'pll_get_current_language' ) );
		}
	}

	/**
	 * Overwrites parent::set_language to remove the 'wp' action if the language is set before
	 *
	 * @since 1.2
	 *
	 * @param object $curlang current language
	 */
	protected function set_language( $curlang ) {
		parent::set_language( $curlang );
		remove_action( 'wp', array( $this, 'wp' ), 5 ); // won't attempt to set the language a 2nd time
	}

	/**
	 * Returns the language based on the queried content
	 *
	 * @since 1.2
	 *
	 * @return object|bool detected language, false if none was found
	 */
	protected function get_language_from_content() {
		// No language set for 404
		if ( is_404() || ( is_attachment() && ! $this->options['media_support'] ) ) {
			return $this->get_preferred_language();
		}

		if ( $var = get_query_var( 'lang' ) ) {
			$lang = explode( ',', $var );
			$lang = $this->model->get_language( reset( $lang ) ); // Choose the first queried language
		}

		elseif ( ( is_single() || is_page() || ( is_attachment() && $this->options['media_support'] ) ) && ( ( $var = get_queried_object_id() ) || ( $var = get_query_var( 'p' ) ) || ( $var = get_query_var( 'page_id' ) ) || ( $var = get_query_var( 'attachment_id' ) ) ) ) {
			$lang = $this->model->post->get_language( $var );
		}

		else {
			foreach ( $this->model->get_translated_taxonomies() as $taxonomy ) {
				if ( $var = get_query_var( get_taxonomy( $taxonomy )->query_var ) ) {
					$lang = $this->model->term->get_language( $var, $taxonomy );
				}
			}
		}

		/**
		 * Filter the language before it is set from the content
		 *
		 * @since 0.9
		 *
		 * @param bool|object $lang language object or false if none was found
		 */
		return apply_filters( 'pll_get_current_language', isset( $lang ) ? $lang : false );
	}

	/**
	 * Sets the language for home page
	 * Add the lang query var when querying archives with no language code
	 *
	 * @since 1.2
	 *
	 * @param object $query instance of WP_Query
	 */
	public function parse_main_query( $query ) {
		if ( $query !== $GLOBALS['wp_the_query'] ) {
			return;
		}

		$qv = $query->query_vars;

		// Homepage is requested, let's set the language
		// Take care to avoid posts page for which is_home = 1
		if ( empty( $query->query ) && ( is_home() || is_page() ) ) {
			$this->home_language();
			$this->home_requested();
		}

		parent::parse_main_query( $query );

		$is_archive = ( count( $query->query ) == 1 && ! empty( $qv['paged'] ) ) ||
			$query->is_date ||
			$query->is_author ||
			( ! empty( $qv['post_type'] ) && $query->is_post_type_archive && $this->model->is_translated_post_type( $qv['post_type'] ) );

		// Sets the language in case we hide the default language
		// Use $query->query['s'] as is_search is not set when search is empty
		// http://wordpress.org/support/topic/search-for-empty-string-in-default-language
		if ( $this->options['hide_default'] && ! isset( $qv['lang'] ) && ( $is_archive || isset( $query->query['s'] ) || ( count( $query->query ) == 1 && ! empty( $qv['feed'] ) ) ) ) {
			$this->set_language( $this->model->get_language( $this->options['default_lang'] ) );
			$this->set_curlang_in_query( $query );
		}
	}

	/**
	 * Sets the language from content
	 *
	 * @since 1.2
	 */
	public function wp() {
		// Nothing to do if the language has already been set ( although normally the filter has been removed )
		if ( ! $this->curlang && $curlang = $this->get_language_from_content() ) {
			parent::set_language( $curlang );
		}
	}

	/**
	 * If no language found by get_language_from_content, return the preferred one
	 *
	 * @since 0.9
	 *
	 * @param object|bool $lang Language found in get_language_from_content
	 * @return object Language
	 */
	public function pll_get_current_language( $lang ) {
		return ! $lang ? $this->get_preferred_language() : $lang;
	}
}
