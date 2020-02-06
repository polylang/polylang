<?php

/**
 * Polylang activation / de-activation class
 *
 * @since 1.7
 */
class PLL_Install extends PLL_Install_Base {

	/**
	 * Checks min PHP and WP version, displays a notice if a requirement is not met.
	 *
	 * @since 2.6.7
	 */
	public function can_activate() {
		global $wp_version;

		if ( version_compare( PHP_VERSION, PLL_MIN_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
			return false;
		}

		if ( version_compare( $wp_version, PLL_MIN_WP_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'wp_version_notice' ) );
			return false;
		}

		return true;
	}

	/**
	 * Displays a notice if PHP min version is not met.
	 *
	 * @since 2.6.7
	 */
	public function php_version_notice() {
		load_plugin_textdomain( 'polylang', false, basename( POLYLANG_DIR ) . '/languages' ); // Plugin i18n.

		printf(
			'<div class="error"><p>%s</p></div>',
			sprintf(
				/* translators: 1: Plugin name 2: Current PHP version 3: Required PHP version */
				esc_html__( '%1$s has deactivated itself because you are using an old PHP version. You are using using PHP %2$s. %1$s requires PHP %3$s.', 'polylang' ),
				esc_html( POLYLANG ),
				PHP_VERSION,
				esc_html( PLL_MIN_PHP_VERSION )
			)
		);
	}

	/**
	 * Displays a notice if WP min version is not met.
	 *
	 * @since 2.6.7
	 */
	public function wp_version_notice() {
		global $wp_version;

		load_plugin_textdomain( 'polylang', false, basename( POLYLANG_DIR ) . '/languages' ); // Plugin i18n.

		printf(
			'<div class="error"><p>%s</p></div>',
			sprintf(
				/* translators: 1: Plugin name 2: Current WordPress version 3: Required WordPress version */
				esc_html__( '%1$s has deactivated itself because you are using an old WordPress version. You are using using WordPress %2$s. %1$s requires at least WordPress %3$s.', 'polylang' ),
				esc_html( POLYLANG ),
				esc_html( $wp_version ),
				esc_html( PLL_MIN_WP_VERSION )
			)
		);
	}

	/**
	 * Get default Polylang options
	 *
	 * @since 1.8
	 *
	 * return array
	 */
	public static function get_default_options() {
		return array(
			'browser'          => 1, // Default language for the front page is set by browser preference
			'rewrite'          => 1, // Remove /language/ in permalinks ( was the opposite before 0.7.2 )
			'hide_default'     => 1, // Remove URL language information for default language ( was the opposite before 2.1.5 )
			'force_lang'       => 1, // Add URL language information ( was 0 before 1.7 )
			'redirect_lang'    => 0, // Do not redirect the language page to the homepage
			'media_support'    => 1, // Support languages and translation for media by default
			'uninstall'        => 0, // Do not remove data when uninstalling Polylang
			'sync'             => array(), // Synchronisation is disabled by default ( was the opposite before 1.2 )
			'post_types'       => array(),
			'taxonomies'       => array(),
			'domains'          => array(),
			'version'          => POLYLANG_VERSION,
			'first_activation' => time(),
		);
	}

	/**
	 * Plugin activation
	 *
	 * @since 0.5
	 */
	protected function _activate() {
		if ( $options = get_option( 'polylang' ) ) {
			// Check if we will be able to upgrade
			if ( version_compare( $options['version'], POLYLANG_VERSION, '<' ) ) {
				$upgrade = new PLL_Upgrade( $options );
				$upgrade->can_activate();
			}
		}
		// Defines default values for options in case this is the first installation
		else {
			update_option( 'polylang', self::get_default_options() );
		}

		// Avoid 1 query on every pages if no wpml strings is registered
		if ( ! get_option( 'polylang_wpml_strings' ) ) {
			update_option( 'polylang_wpml_strings', array() );
		}

		// Don't use flush_rewrite_rules at network activation. See #32471
		// Thanks to RavanH for the trick. See https://polylang.wordpress.com/2015/06/10/polylang-1-7-6-and-multisite/
		// Rewrite rules are created at next page load :)
		delete_option( 'rewrite_rules' );
	}

	/**
	 * Plugin deactivation
	 *
	 * @since 0.5
	 */
	protected function _deactivate() {
		delete_option( 'rewrite_rules' ); // Don't use flush_rewrite_rules at network activation. See #32471
	}
}
