<?php
/**
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
};

// Default directory to store user data such as custom flags
if ( ! defined( 'PLL_LOCAL_DIR' ) ) {
	define( 'PLL_LOCAL_DIR', WP_CONTENT_DIR . '/polylang' );
}

// Includes local config file if exists
if ( is_readable( PLL_LOCAL_DIR . '/pll-config.php' ) ) {
	include_once PLL_LOCAL_DIR . '/pll-config.php';
}

/**
 * Controls the plugin, as well as activation, and deactivation
 *
 * @since 0.1
 */
class Polylang {

	/**
	 * Constructor
	 *
	 * @since 0.1
	 */
	public function __construct() {
		require_once __DIR__ . '/functions.php'; // VIP functions

		// register an action when plugin is activating.
		register_activation_hook( POLYLANG_BASENAME, array( 'PLL_Wizard', 'start_wizard' ) );

		$install = new PLL_Install( POLYLANG_BASENAME );

		// Stopping here if we are going to deactivate the plugin ( avoids breaking rewrite rules )
		if ( $install->is_deactivation() || ! $install->can_activate() ) {
			return;
		}

		// Plugin initialization
		// Take no action before all plugins are loaded
		add_action( 'plugins_loaded', array( $this, 'init' ), 1 );

		// Override load text domain waiting for the language to be defined
		// Here for plugins which load text domain as soon as loaded :(
		if ( ! defined( 'PLL_OLT' ) || PLL_OLT ) {
			PLL_OLT_Manager::instance();
		}

		/*
		 * Loads the compatibility with some plugins and themes.
		 * Loaded as soon as possible as we may need to act before other plugins are loaded.
		 */
		if ( ! defined( 'PLL_PLUGINS_COMPAT' ) || PLL_PLUGINS_COMPAT ) {
			PLL_Integrations::instance();
		}
	}

	/**
	 * Tells whether the current request is an ajax request on frontend or not
	 *
	 * @since 2.2
	 *
	 * @return bool
	 */
	public static function is_ajax_on_front() {
		// Special test for plupload which does not use jquery ajax and thus does not pass our ajax prefilter
		// Special test for customize_save done in frontend but for which we want to load the admin
		$in = isset( $_REQUEST['action'] ) && in_array( sanitize_key( $_REQUEST['action'] ), array( 'upload-attachment', 'customize_save' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$is_ajax_on_front = wp_doing_ajax() && empty( $_REQUEST['pll_ajax_backend'] ) && ! $in; // phpcs:ignore WordPress.Security.NonceVerification

		/**
		 * Filters whether the current request is an ajax request on front.
		 *
		 * @since 2.3
		 *
		 * @param bool $is_ajax_on_front Whether the current request is an ajax request on front.
		 */
		return apply_filters( 'pll_is_ajax_on_front', $is_ajax_on_front );
	}

	/**
	 * Is the current request a REST API request?
	 * Inspired by WP::parse_request()
	 * Needed because at this point, the constant REST_REQUEST is not defined yet
	 *
	 * @since 2.4.1
	 *
	 * @return bool
	 */
	public static function is_rest_request() {
		// Handle pretty permalinks.
		$home_path       = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		$home_path_regex = sprintf( '|^%s|i', preg_quote( $home_path, '|' ) );

		$req_uri = trim( (string) wp_parse_url( pll_get_requested_url(), PHP_URL_PATH ), '/' );
		$req_uri = preg_replace( $home_path_regex, '', $req_uri );
		$req_uri = trim( $req_uri, '/' );
		$req_uri = str_replace( 'index.php', '', $req_uri );
		$req_uri = trim( $req_uri, '/' );

		// And also test rest_route query string parameter is not empty for plain permalinks.
		$query_string = array();
		wp_parse_str( (string) wp_parse_url( pll_get_requested_url(), PHP_URL_QUERY ), $query_string );
		$rest_route = isset( $query_string['rest_route'] ) ? trim( $query_string['rest_route'], '/' ) : false;

		return 0 === strpos( $req_uri, rest_get_url_prefix() . '/' ) || ! empty( $rest_route );
	}

	/**
	 * Tells if we are in the wizard process.
	 *
	 * @since 2.7
	 *
	 * @return bool
	 */
	public static function is_wizard() {
		return isset( $_GET['page'] ) && ! empty( $_GET['page'] ) && 'mlang_wizard' === sanitize_key( $_GET['page'] ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Defines constants
	 * May be overridden by a plugin if set before plugins_loaded, 1
	 *
	 * @since 1.6
	 *
	 * @return void
	 */
	public static function define_constants() {
		// Cookie name. no cookie will be used if set to false
		if ( ! defined( 'PLL_COOKIE' ) ) {
			define( 'PLL_COOKIE', 'pll_language' );
		}

		// Backward compatibility with Polylang < 2.3
		if ( ! defined( 'PLL_AJAX_ON_FRONT' ) ) {
			define( 'PLL_AJAX_ON_FRONT', self::is_ajax_on_front() );
		}

		// Admin
		if ( ! defined( 'PLL_ADMIN' ) ) {
			define( 'PLL_ADMIN', wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) || ( is_admin() && ! PLL_AJAX_ON_FRONT ) );
		}

		// Settings page whatever the tab except for the wizard which needs to be an admin process.
		if ( ! defined( 'PLL_SETTINGS' ) ) {
			define( 'PLL_SETTINGS', is_admin() && ( ( isset( $_GET['page'] ) && 0 === strpos( sanitize_key( $_GET['page'] ), 'mlang' ) && ! self::is_wizard() ) || ! empty( $_REQUEST['pll_ajax_settings'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}

	/**
	 * Polylang initialization
	 * setups models and separate admin and frontend
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function init() {
		global $polylang;

		self::define_constants();
		$options = get_option( 'polylang' );

		// Plugin upgrade
		if ( $options && version_compare( $options['version'], POLYLANG_VERSION, '<' ) ) {
			$upgrade = new PLL_Upgrade( $options );
			if ( ! $upgrade->upgrade() ) { // If the version is too old
				return;
			}
		}

		// In some edge cases, it's possible that no options were found in the database. Load default options as we need some.
		if ( ! $options ) {
			$options = PLL_Install::get_default_options();
		}

		// Make sure that this filter is *always* added before PLL_Model::get_languages_list() is called for the first time
		add_filter( 'pll_languages_list', array( 'PLL_Static_Pages', 'pll_languages_list' ), 2, 2 ); // Before PLL_Links_Model

		/**
		 * Filter the model class to use
		 * /!\ this filter is fired *before* the $polylang object is available
		 *
		 * @since 1.5
		 *
		 * @param string $class either PLL_Model or PLL_Admin_Model
		 */
		$class = apply_filters( 'pll_model', PLL_SETTINGS || self::is_wizard() ? 'PLL_Admin_Model' : 'PLL_Model' );
		$model = new $class( $options );

		if ( ! $model->has_languages() ) {
			/**
			 * Fires when no language has been defined yet
			 * Used to load overridden textdomains
			 *
			 * @since 1.2
			 */
			do_action( 'pll_no_language_defined' );
		}

		$class = '';

		if ( PLL_SETTINGS ) {
			$class = 'PLL_Settings';
		} elseif ( PLL_ADMIN ) {
			$class = 'PLL_Admin';
		} elseif ( self::is_rest_request() ) {
			$class = 'PLL_REST_Request';
		} elseif ( $model->has_languages() ) {
			$class = 'PLL_Frontend';
		}

		/**
		 * Filters the class to use to instantiate the $polylang object
		 *
		 * @since 2.6
		 *
		 * @param string $class A class name.
		 */
		$class = apply_filters( 'pll_context', $class );

		if ( ! empty( $class ) ) {
			$links_model = $model->get_links_model();
			$polylang    = new $class( $links_model );

			/**
			 * Fires after the $polylang object is created and before the API is loaded
			 *
			 * @since 2.0
			 *
			 * @param object $polylang
			 */
			do_action_ref_array( 'pll_pre_init', array( &$polylang ) );

			require_once __DIR__ . '/api.php'; // Loads the API

			// Loads the modules.
			$load_scripts = glob( POLYLANG_DIR . '/modules/*/load.php', GLOB_NOSORT );
			if ( is_array( $load_scripts ) ) {
				foreach ( $load_scripts as $load_script ) {
					require_once $load_script; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
				}
			}

			$polylang->init();

			/**
			 * Fires after the $polylang object and the API is loaded
			 *
			 * @since 1.7
			 *
			 * @param object $polylang
			 */
			do_action_ref_array( 'pll_init', array( &$polylang ) );
		}
	}
}
