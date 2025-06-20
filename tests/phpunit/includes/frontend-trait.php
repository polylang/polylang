<?php

/**
 * Trait sharing utility methods for frontend tests.
 */
trait PLL_Frontend_Trait {
	/**
	 * Overrides WP_UnitTestCase::go_to().
	 *
	 * @param string $url The URL for the request.
	 */
	public function go_to( $url ) {
		// Copy paste of WP_UnitTestCase::go_to().
		$_GET  = array();
		$_POST = array();
		foreach ( array( 'query_string', 'id', 'postdata', 'authordata', 'day', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages', 'pagenow' ) as $v ) {
			if ( isset( $GLOBALS[ $v ] ) ) {
				unset( $GLOBALS[ $v ] );
			}
		}
		$parts = wp_parse_url( $url );
		if ( isset( $parts['scheme'] ) ) {
			$req = $parts['path'] ?? '';
			if ( isset( $parts['query'] ) ) {
				$req .= '?' . $parts['query'];
				// Parse the url query vars into $_GET.
				parse_str( $parts['query'], $_GET );
			}
		} else {
			$req = $url;
		}
		if ( ! isset( $parts['query'] ) ) {
			$parts['query'] = '';
		}

		$_SERVER['REQUEST_URI'] = $req;
		unset( $_SERVER['PATH_INFO'] );

		$this->flush_cache();
		unset( $GLOBALS['wp_query'], $GLOBALS['wp_the_query'] );

		// Insert Polylang specificity.
		unset( $GLOBALS['wp_actions']['pll_language_defined'] );
		unset( $this->frontend->curlang );
		$this->frontend->init();

		// Restart copy paste of WP_UnitTestCase::go_to().
		$GLOBALS['wp_the_query'] = new WP_Query();
		$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'];
		$GLOBALS['wp'] = new WP();

		/*
		 * Instead of using `_cleanup_query_vars()` to cleanup and repopulate query vars, trigger `setup_theme` and use
		 * `create_initial_taxonomies()`.
		 * See `PLL_Translated_Post::add_language_taxonomy_query_var()`.
		 */
		do_action( 'setup_theme' );
		create_initial_taxonomies();

		$GLOBALS['wp']->main( $parts['query'] );
	}
}
