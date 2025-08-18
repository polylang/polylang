<?php
/**
 * @package Polylang
 */

use WP_Syntex\Polylang\Options\Options;
use WP_Syntex\Polylang\Options\Registry as Options_Registry;

/**
 * Polylang activation/de-activation class.
 *
 * @since 1.7
 */
class PLL_Install extends PLL_Install_Base {
	/**
	 * Name of the plugin.
	 *
	 * @since 3.8
	 *
	 * @var string
	 */
	public $plugin_name;

	/**
	 * Version of the plugin.
	 *
	 * @since 3.8
	 *
	 * @var string
	 */
	public $plugin_version;

	/**
	 * Minimal WP version required to run the plugin.
	 *
	 * @since 3.8
	 *
	 * @var string
	 */
	public $min_wp_version;

	/**
	 * Minimal php version required to run the plugin.
	 *
	 * @since 3.8
	 *
	 * @var string
	 */
	public $min_php_version;

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param string $plugin_file     Path to the plugin's main file.
	 * @param string $plugin_version  Version of the plugin.
	 * @param string $min_wp_version  Minimal WP version required to run the plugin.
	 * @param string $min_php_version Minimal php version required to run the plugin.
	 */
	public function __construct( string $plugin_file, string $plugin_version, string $min_wp_version, string $min_php_version ) {
		$this->plugin_version  = $plugin_version;
		$this->min_wp_version  = $min_wp_version;
		$this->min_php_version = $min_php_version;

		if ( defined( 'POLYLANG_BASENAME' ) ) {
			$this->plugin_basename = POLYLANG_BASENAME;
		} else {
			$this->plugin_basename = plugin_basename( $plugin_file );
		}

		$this->plugin_name = ucwords( str_replace( '-', ' ', dirname( $this->plugin_basename ) ) );
	}

	/**
	 * Checks min PHP and WP version, displays a notice if a requirement is not met.
	 *
	 * @since 2.6.7
	 *
	 * @return bool
	 */
	public function can_activate() {
		global $wp_version;

		if ( version_compare( PHP_VERSION, $this->min_php_version, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
			return false;
		}

		if ( version_compare( $wp_version, $this->min_wp_version, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'wp_version_notice' ) );
			return false;
		}

		return true;
	}

	/**
	 * Displays a notice if PHP min version is not met.
	 *
	 * @since 2.6.7
	 *
	 * @return void
	 */
	public function php_version_notice() {
		load_plugin_textdomain( 'polylang' ); // Plugin i18n.

		printf(
			'<div class="error"><p>%s</p></div>',
			sprintf(
				/* translators: 1: Plugin name 2: Current PHP version 3: Required PHP version */
				esc_html__( '%1$s has deactivated itself because you are using an old version of PHP. You are using using PHP %2$s. %1$s requires PHP %3$s.', 'polylang' ),
				esc_html( $this->plugin_name ),
				PHP_VERSION,
				esc_html( $this->min_php_version )
			)
		);
	}

	/**
	 * Displays a notice if WP min version is not met.
	 *
	 * @since 2.6.7
	 *
	 * @return void
	 */
	public function wp_version_notice() {
		global $wp_version;

		load_plugin_textdomain( 'polylang' ); // Plugin i18n.

		printf(
			'<div class="error"><p>%s</p></div>',
			sprintf(
				/* translators: 1: Plugin name 2: Current WordPress version 3: Required WordPress version */
				esc_html__( '%1$s has deactivated itself because you are using an old version of WordPress. You are using using WordPress %2$s. %1$s requires at least WordPress %3$s.', 'polylang' ),
				esc_html( $this->plugin_name ),
				esc_html( $wp_version ),
				esc_html( $this->min_wp_version )
			)
		);
	}

	/**
	 * Adds the required hooks.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public static function add_hooks() {
		// register an action when plugin is activating.
		register_activation_hook( POLYLANG_BASENAME, array( 'PLL_Wizard', 'start_wizard' ) );

		parent::add_hooks();
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
		add_action( 'pll_init_options_for_blog', array( Options_Registry::class, 'register' ) );
		$options = new Options();

		if ( ! empty( $options['version'] ) ) {
			// Check if we will be able to upgrade.
			if ( version_compare( $options['version'], POLYLANG_VERSION, '<' ) ) {
				( new PLL_Upgrade( $options ) )->can_activate();
			}
		} else {
			$options['version'] = POLYLANG_VERSION;
		}

		$options->save(); // Force save here to prevent any conflicts with another instance of `Options`.

		if ( false === get_option( 'pll_language_from_content_available' ) ) {
			update_option(
				'pll_language_from_content_available',
				0 === $options['force_lang'] ? 'yes' : 'no'
			);
		}

		// Avoid 1 query on every pages if no wpml strings is registered.
		if ( ! get_option( 'polylang_wpml_strings' ) ) {
			update_option( 'polylang_wpml_strings', array() );
		}

		// Don't use flush_rewrite_rules at network activation. See #32471.
		// Thanks to RavanH for the trick. See https://polylang.wordpress.com/2015/06/10/polylang-1-7-6-and-multisite/.
		// Rewrite rules are created at next page load :)
		delete_option( 'rewrite_rules' );
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
		delete_option( 'rewrite_rules' ); // Don't use flush_rewrite_rules at network activation. See #32471.
	}
}
