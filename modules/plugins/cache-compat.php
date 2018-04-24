<?php

/**
 * A class to manage specific compatibility issue with cache plugins
 * Tested with WP Rocket 2.10.7
 *
 * @since 2.3
 */
class PLL_Cache_Compat {
	/**
	 * Setups actions
	 *
	 * @since 2.3
	 */
	public function init() {
		if ( PLL_COOKIE ) {
			add_action( 'wp_print_footer_scripts', array( $this, 'add_cookie_script' ) );
		}
		add_action( 'wp', array( $this, 'do_not_cache_site_home' ) );
	}

	/**
	 * Currently all tested cache plugins don't send cookies with cached pages
	 * This makes us impossible know the language of the last browsed page
	 * This functions allows to create the cookie in javascript as a workaround
	 *
	 * @since 2.3
	 */
	public function add_cookie_script() {
		$domain = ( 2 == PLL()->options['force_lang'] ) ? parse_url( PLL()->links_model->home, PHP_URL_HOST ) : COOKIE_DOMAIN;
		$js = sprintf( '
			var date = new Date();
			date.setTime( date.getTime() + %d );
			document.cookie = "%s=%s; expires=" + date.toUTCString() + "; path=%s%s";',
			esc_js( apply_filters( 'pll_cookie_expiration', YEAR_IN_SECONDS ) ),
			esc_js( PLL_COOKIE ),
			esc_js( pll_current_language() ),
			esc_js( COOKIEPATH ),
			$domain ? '; domain=' . esc_js( $domain ) : ''
		);
		echo '<script type="text/javascript">' . $js . '</script>';
	}

	/**
	 * Informs cache plugins not to cache the home in the default language
	 * When the detection of the browser preferred language is active
	 *
	 * @since 2.3
	 */
	public function do_not_cache_site_home() {
		if ( ! defined( 'DONOTCACHEPAGE' ) && PLL()->options['browser'] && PLL()->options['hide_default'] && is_front_page() && pll_current_language() === pll_default_language() ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}
}
