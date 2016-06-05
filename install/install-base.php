<?php

/**
 * a generic activation / de-activation class compatble with multisite
 *
 * @since 1.7
 */
class PLL_Install_Base {
	protected $plugin_basename;

	/**
	 * constructor
	 *
	 * @since 1.7
	 */
	public function __construct( $plugin_basename ) {
		$this->plugin_basename = $plugin_basename;

		// manages plugin activation and deactivation
		register_activation_hook( $plugin_basename, array( $this, 'activate' ) );
		register_deactivation_hook( $plugin_basename, array( $this, 'deactivate' ) );

		// blog creation on multisite
		add_action( 'wpmu_new_blog', array( $this, 'wpmu_new_blog' ), 5 ); // before WP attempts to send mails which can break on some PHP versions
	}

	/**
	 * allows to detect plugin deactivation
	 *
	 * @since 1.7
	 *
	 * @return bool true if the plugin is currently beeing deactivated
	 */
	public function is_deactivation() {
		return isset( $_GET['action'], $_GET['plugin'] ) && 'deactivate' == $_GET['action'] && $this->plugin_basename == $_GET['plugin'];
	}

	/**
	 * activation or deactivation for all blogs
	 *
	 * @since 1.2
	 *
	 * @param string $what either 'activate' or 'deactivate'
	 */
	protected function do_for_all_blogs( $what, $networkwide ) {
		// network
		if ( is_multisite() && $networkwide ) {
			global $wpdb;

			foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) as $blog_id ) {
				switch_to_blog( $blog_id );
				'activate' == $what ? $this->_activate() : $this->_deactivate();
			}
			restore_current_blog();
		}

		// single blog
		else {
			'activate' == $what ? $this->_activate() : $this->_deactivate();
		}
	}

	/**
	 * plugin activation for multisite
	 *
	 * @since 1.7
	 */
	public function activate( $networkwide ) {
		$this->do_for_all_blogs( 'activate', $networkwide );
	}

	/**
	 * plugin activation
	 *
	 * @since 0.5
	 */
	protected function _activate() {
		// can be overriden in child class
	}

	/**
	 * plugin deactivation for multisite
	 *
	 * @since 0.1
	 */
	public function deactivate( $networkwide ) {
		$this->do_for_all_blogs( 'deactivate', $networkwide );
	}

	/**
	 * plugin deactivation
	 *
	 * @since 0.5
	 */
	protected function _deactivate() {
		// can be overriden in child class
	}

	/**
	 * blog creation on multisite ( to set default options )
	 *
	 * @since 0.9.4
	 *
	 * @param int $blog_id
	 */
	public function wpmu_new_blog( $blog_id ) {
		switch_to_blog( $blog_id );
		$this->_activate();
		restore_current_blog();
	}
}
