<?php
/**
 * @package Polylang
 */

/**
 * A generic activation / de-activation class compatble with multisite
 *
 * @since 1.7
 */
class PLL_Install_Base {
	/**
	 * The plugin basename.
	 *
	 * @var string
	 */
	protected $plugin_basename;

	/**
	 * Constructor
	 *
	 * @since 1.7
	 *
	 * @param string $plugin_basename Plugin basename
	 */
	public function __construct( $plugin_basename ) {
		$this->plugin_basename = $plugin_basename;

		// Manages plugin activation and deactivation
		register_activation_hook( $plugin_basename, array( $this, 'activate' ) );
		register_deactivation_hook( $plugin_basename, array( $this, 'deactivate' ) );

		// Site creation on multisite.
		add_action( 'wp_insert_site', array( $this, 'new_site' ) );
	}

	/**
	 * Allows to detect plugin deactivation
	 *
	 * @since 1.7
	 *
	 * @return bool true if the plugin is currently beeing deactivated
	 */
	public function is_deactivation() {
		return isset( $_GET['action'], $_GET['plugin'] ) && 'deactivate' === $_GET['action'] && $this->plugin_basename === $_GET['plugin']; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Activation or deactivation for all blogs
	 *
	 * @since 1.2
	 *
	 * @param string $what        Either 'activate' or 'deactivate'
	 * @param bool   $networkwide
	 * @return void
	 */
	protected function do_for_all_blogs( $what, $networkwide ) {
		// Network
		if ( is_multisite() && $networkwide ) {
			global $wpdb;

			foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) as $blog_id ) {
				switch_to_blog( $blog_id );
				'activate' == $what ? $this->_activate() : $this->_deactivate();
			}
			restore_current_blog();
		}

		// Single blog
		else {
			'activate' == $what ? $this->_activate() : $this->_deactivate();
		}
	}

	/**
	 * Plugin activation for multisite
	 *
	 * @since 1.7
	 *
	 * @param bool $networkwide
	 * @return void
	 */
	public function activate( $networkwide ) {
		$this->do_for_all_blogs( 'activate', $networkwide );
	}

	/**
	 * Plugin activation
	 *
	 * @since 0.5
	 *
	 * @return void
	 */
	protected function _activate() {
		// Can be overriden in child class
	}

	/**
	 * Plugin deactivation for multisite
	 *
	 * @since 0.1
	 *
	 * @param bool $networkwide
	 * @return void
	 */
	public function deactivate( $networkwide ) {
		$this->do_for_all_blogs( 'deactivate', $networkwide );
	}

	/**
	 * Plugin deactivation
	 *
	 * @since 0.5
	 *
	 * @return void
	 */
	protected function _deactivate() {
		// Can be overriden in child class
	}

	/**
	 * Site creation on multisite ( to set default options )
	 *
	 * @since 2.6.8
	 *
	 * @param WP_Site $new_site New site object.
	 * @return void
	 */
	public function new_site( $new_site ) {
		switch_to_blog( $new_site->id );
		$this->_activate();
		restore_current_blog();
	}
}
