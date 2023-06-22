<?php

/**
 * Trait to ease tests made with WP installed in a subfolder.
 */
trait PLL_Test_Links_Trait {
	/**
	 * Permalink structure.
	 *
	 * @var string
	 */
	protected $structure = '/%postname%/';

	/**
	 * Filters `plugins_url` because `WP_CONTENT_URL` is already defined before the option `siteurl` is changed during tests for directory.
	 * Also remove full path `POLYLANG_DIR` added to flag URL.
	 *
	 * @return void
	 */
	protected function filter_plugins_url() {
		$orig_siteurl = get_option( 'siteurl' );

		add_filter(
			'plugins_url',
			function ( $url ) use ( $orig_siteurl ) {
				$siteurl = get_option( 'siteurl' );
				if ( false === strpos( $url, $siteurl ) ) {
					$url = str_replace( $orig_siteurl, $siteurl, $url );
				}

				return str_replace( POLYLANG_DIR . '/', '/polylang/', $url );
			},
			-1
		);
	}

	/**
	 * Initializes `PLL_Links_Domain` according to permalink structure.
	 *
	 * @global $wp_rewrite
	 *
	 * @return void
	 */
	protected function init_links_model() {
		global $wp_rewrite;

		// Switch to pretty permalinks.
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $this->structure );
		$this->links_model = self::$model->get_links_model();
	}
}
