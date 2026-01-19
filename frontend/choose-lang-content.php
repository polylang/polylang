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
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

		if ( ! did_action( 'pll_language_defined' ) ) {
			// Set the languages from content
			add_action( 'wp', array( $this, 'wp' ), 5 ); // Priority 5 for post types and taxonomies registered in wp hook with default priority
		}
	}

	/**
	 * Overwrites parent::set_language to remove the 'wp' action if the language is set before.
	 *
	 * @since 1.2
	 *
	 * @param PLL_Language|false $curlang Optional. Current language. Default is `false`.
	 * @return void
	 */
	protected function set_language( $curlang = false ): void {
		parent::set_language( $curlang );
		remove_action( 'wp', array( $this, 'wp' ), 5 ); // won't attempt to set the language a 2nd time
	}

	/**
	 * Returns the language based on the queried content.
	 *
	 * @since 1.2
	 * @since 3.8 Renamed from `get_language_from_content()`.
	 *
	 * @return PLL_Language|false
	 */
	protected function get_current_language() {
		// No language set for 404.
		if ( is_404() || ( is_attachment() && ! $this->options['media_support'] ) ) {
			return false;
		}

		$var = get_query_var( 'lang' );

		if ( ! empty( $var ) && is_string( $var ) ) {
			$lang = explode( ',', $var );
			return $this->model->get_language( reset( $lang ) ); // Choose the first queried language.
		}

		if ( is_singular() ) {
			foreach ( $this->get_singular_query_vars() as $var ) {
				if ( ! empty( $var ) && is_numeric( $var ) ) {
					return $this->model->post->get_language( (int) $var );
				}
			}
		}

		foreach ( $this->model->get_translated_taxonomies() as $taxonomy ) {
			$tax_object = get_taxonomy( $taxonomy );

			if ( empty( $tax_object ) || empty( $tax_object->query_var ) ) {
				continue;
			}

			$var = get_query_var( $tax_object->query_var );

			if ( ! is_string( $var ) || empty( $var ) ) {
				continue;
			}

			$term = get_term_by( 'slug', $var, $taxonomy );

			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			return $this->model->term->get_language( $term->term_id );
		}

		return false;
	}

	/**
	 * Returns the values of query vars corresponding to "singular" pages.
	 *
	 * @since 3.8
	 *
	 * @return Generator
	 */
	private function get_singular_query_vars(): Generator {
		yield get_queried_object_id();
		yield get_query_var( 'p' );
		yield get_query_var( 'page_id' );
		yield get_query_var( 'attachment_id' );
	}

	/**
	 * Sets the language for the home page.
	 * Adds the lang query var when querying archives with no language code.
	 *
	 * @since 1.2
	 *
	 * @param WP_Query $query Instance of WP_Query.
	 * @return void
	 */
	public function parse_main_query( $query ) {
		if ( empty( $GLOBALS['wp_the_query'] ) || $query !== $GLOBALS['wp_the_query'] ) {
			return;
		}

		$qv = $query->query_vars;

		// Homepage is requested, let's set the language
		// Take care to avoid posts page for which is_home = 1
		if ( empty( $query->query ) && ( is_home() || is_page() ) ) {
			$this->set_language( $this->get_home_language() );
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
			$this->set_language( $this->model->get_default_language() );
			$this->set_curlang_in_query( $query );
		}
	}

	/**
	 * Sets the language from content.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function wp(): void {
		parent::set_language();
	}
}
