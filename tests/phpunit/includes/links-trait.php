<?php

/**
 * Trait to ease tests made in directory as well as removing path from flag URL.
 */
trait PLL_Links_Trait {
	/**
	 * Original `siteurl` option value.
	 *
	 * @var string
	 */
	protected $orig_siteurl = '';

	/**
	 * Filters `plugins_url` because `WP_CONTENT_URL` is already defined before the option `siteurl` is changed during tests for directory.
	 * Also remove full path `POLYLANG_DIR` added to flag URL.
	 *
	 * @return void
	 */
	protected function filter_plugins_url() {
		$this->orig_siteurl = get_option( 'siteurl' );

		add_filter(
			'plugins_url',
			function ( $url ) {
				$siteurl = get_option( 'siteurl' );
				if ( false === strpos( $url, $siteurl ) ) {
					$url = str_replace( $this->orig_siteurl, $siteurl, $url );
				}

				return str_replace( POLYLANG_DIR . '/', '/polylang/', $url );
			},
			-1
		);
	}
}
