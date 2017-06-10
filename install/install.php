<?php

/**
 * Polylang activation / de-activation class
 *
 * @since 1.7
 */
class PLL_Install extends PLL_Install_Base {

	/**
	 * plugin activation for multisite
	 *
	 * @since 0.1
	 */
	public function activate( $networkwide ) {
		global $wp_version;

		Polylang::define_constants();

		load_plugin_textdomain( 'polylang', false, basename( POLYLANG_DIR ).'/languages' ); // plugin i18n

		if ( version_compare( $wp_version, PLL_MIN_WP_VERSION , '<' ) ) {
			die( sprintf( '<p style = "font-family: sans-serif; font-size: 12px; color: #333; margin: -5px">%s</p>',
				/* translators: %s are WordPress version numbers */
				sprintf( esc_html__( 'You are using WordPress %s. Polylang requires at least WordPress %s.', 'polylang' ),
					esc_html( $wp_version ),
					PLL_MIN_WP_VERSION
				)
			) );
		}
		$this->do_for_all_blogs( 'activate', $networkwide );
	}

	/**
	 * get default Polylang options
	 *
	 * @since 1.8
	 *
	 * return array
	 */
	static public function get_default_options() {
		return array(
			'browser'       => 1, // Default language for the front page is set by browser preference
			'rewrite'       => 1, // Remove /language/ in permalinks ( was the opposite before 0.7.2 )
			'hide_default'  => 1, // Remove URL language information for default language ( was the opposite before 2.1.5 )
			'force_lang'    => 1, // Add URL language information ( was 0 before 1.7 )
			'redirect_lang' => 0, // Do not redirect the language page to the homepage
			'media_support' => 1, // Support languages and translation for media by default
			'uninstall'     => 0, // Do not remove data when uninstalling Polylang
			'sync'          => array(), // Synchronisation is disabled by default ( was the opposite before 1.2 )
			'post_types'    => array(),
			'taxonomies'    => array(),
			'domains'       => array(),
			'version'       => POLYLANG_VERSION,
		);
	}

	/**
	 * plugin activation
	 *
	 * @since 0.5
	 */
	protected function _activate() {
		if ( $options = get_option( 'polylang' ) ) {
			// check if we will be able to upgrade
			if ( version_compare( $options['version'], POLYLANG_VERSION, '<' ) ) {
				$upgrade = new PLL_Upgrade( $options );
				$upgrade->can_activate();
			}
		}
		// defines default values for options in case this is the first installation
		else {
			update_option( 'polylang', self::get_default_options() );
		}

		// avoid 1 query on every pages if no wpml strings is registered
		if ( ! get_option( 'polylang_wpml_strings' ) ) {
			update_option( 'polylang_wpml_strings', array() );
		}

		// don't use flush_rewrite_rules at network activation. See #32471
		// thanks to RavanH for the trick. See https://polylang.wordpress.com/2015/06/10/polylang-1-7-6-and-multisite/
		// rewrite rules are created at next page load :)
		delete_option( 'rewrite_rules' );
	}

	/**
	 * plugin deactivation
	 *
	 * @since 0.5
	 */
	protected function _deactivate() {
		delete_option( 'rewrite_rules' ); // don't use flush_rewrite_rules at network activation. See #32471
	}
}
