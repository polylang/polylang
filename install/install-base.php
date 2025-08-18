<?php
/**
 * @package Polylang
 */

/**
 * A generic activation/de-activation class compatible with multisite.
 *
 * @since 1.7
 */
class PLL_Install_Base {
	/**
	 * The plugin basename.
	 *
	 * @since 3.8 Changed visibility to public.
	 *
	 * @var string
	 */
	public $plugin_basename;

	/**
	 * Used to cache the list of sites on multi-site.
	 *
	 * @var array
	 */
	private static $sites = array();

	/**
	 * Constructor.
	 *
	 * @since 1.7
	 * @since 3.8 Doesn't add hooks anymore.
	 *
	 * @param string $plugin_basename Plugin basename.
	 */
	public function __construct( $plugin_basename ) {
		$this->plugin_basename = $plugin_basename;
	}

	/**
	 * Allows to detect plugin deactivation.
	 *
	 * @since 1.7
	 *
	 * @return bool True if the plugin is currently being deactivated.
	 */
	public function is_deactivation() {
		return isset( $_GET['action'], $_GET['plugin'] ) && 'deactivate' === $_GET['action'] && $this->plugin_basename === $_GET['plugin']; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Adds the required hooks.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public static function add_hooks() {
		// Manages plugin activation and deactivation
		register_activation_hook( POLYLANG_BASENAME, array( static::class, 'activate' ) );
		register_deactivation_hook( POLYLANG_BASENAME, array( static::class, 'deactivate' ) );

		// Site creation on multisite.
		add_action( 'wp_initialize_site', array( static::class, 'new_site' ), 50 ); // After WP (prio 10).
	}

	/**
	 * Plugin activation for multisite.
	 *
	 * @since 1.7
	 * @since 3.8 Static method.
	 *
	 * @param bool $networkwide Whether the plugin is activated for all sites in the network or just the current site.
	 * @return void
	 */
	public static function activate( $networkwide ) {
		static::do_for_all_blogs( 'activate', $networkwide );
	}

	/**
	 * Plugin activation.
	 *
	 * @since 0.5
	 * @since 3.8 Static method.
	 *
	 * @return void
	 */
	protected static function _activate() {
		// Can be overridden in child class.
	}

	/**
	 * Plugin deactivation for multisite.
	 *
	 * @since 0.1
	 * @since 3.8 Static method.
	 *
	 * @param bool $networkwide Whether the plugin is deactivated for all sites in the network or just the current site.
	 * @return void
	 */
	public static function deactivate( $networkwide ) {
		static::do_for_all_blogs( 'deactivate', $networkwide );
	}

	/**
	 * Plugin deactivation.
	 *
	 * @since 0.5
	 * @since 3.8 Static method.
	 *
	 * @return void
	 */
	protected static function _deactivate() {
		// Can be overridden in child class.
	}

	/**
	 * Site creation on multisite (to set default options).
	 *
	 * @since 2.6.8
	 * @since 3.8 Static method.
	 *
	 * @param WP_Site $new_site New site object.
	 * @return void
	 */
	public static function new_site( $new_site ) {
		switch_to_blog( $new_site->id );
		static::_activate();
		restore_current_blog();
	}

	/**
	 * Activation or deactivation for all blogs.
	 *
	 * @since 1.2
	 * @since 3.8 Static method.
	 *
	 * @param string $what        Either 'activate' or 'deactivate'.
	 * @param bool   $networkwide Whether the plugin is (de)activated for all sites in the network or just the current site.
	 * @return void
	 */
	protected static function do_for_all_blogs( $what, $networkwide ) {
		if ( is_multisite() && $networkwide ) {
			// Network.
			foreach ( self::get_sites() as $blog_id ) {
				switch_to_blog( $blog_id );
				'activate' === $what ? static::_activate() : static::_deactivate();
			}
			restore_current_blog();
		} else {
			// Single blog.
			'activate' === $what ? static::_activate() : static::_deactivate();
		}
	}

	/**
	 * Returns (and cache) the list of sites on multi-site.
	 *
	 * @since 3.8
	 *
	 * @return array
	 */
	protected static function get_sites(): array {
		global $wpdb;

		if ( empty( self::$sites ) ) {
			self::$sites = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
		}

		return self::$sites;
	}
}
