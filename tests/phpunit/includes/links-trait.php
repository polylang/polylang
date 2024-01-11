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
	 * Subfolder name used in tests.
	 *
	 * @var string
	 */
	protected $subfolder_name = 'subfolder';

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

				return $url;
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

	/**
	 * Enables a WordPress installation in directory.
	 *
	 * @param bool $is_subfolder_install Whether or not the current test is in subfolder install.
	 *
	 * @return void
	 */
	protected function maybe_set_subfolder_install( $is_subfolder_install ) {
		if ( ! $is_subfolder_install ) {
			return;
		}

		// Fake WP install in subdir.
		update_option( 'siteurl', 'http://example.org/' . $this->subfolder_name );
		update_option( 'home', 'http://example.org' );
	}
}
