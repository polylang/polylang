<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // don't access directly
};

// default directory to store user data such as custom flags
if ( ! defined( 'PLL_LOCAL_DIR' ) ) {
	define( 'PLL_LOCAL_DIR', WP_CONTENT_DIR . '/polylang' );
}

// includes local config file if exists
if ( file_exists( PLL_LOCAL_DIR . '/pll-config.php' ) ) {
	include_once( PLL_LOCAL_DIR . '/pll-config.php' );
}

/**
 * controls the plugin, as well as activation, and deactivation
 *
 * @since 0.1
 */
class Polylang {

	/**
	 * constructor
	 *
	 * @since 0.1
	 */
	public function __construct() {
		// FIXME maybe not available on every installations but widely used by WP plugins
		spl_autoload_register( array( &$this, 'autoload' ) ); // autoload classes

		$install = new PLL_Install( POLYLANG_BASENAME );

		// stopping here if we are going to deactivate the plugin ( avoids breaking rewrite rules )
		if ( $install->is_deactivation() ) {
			return;
		}

		// plugin initialization
		// take no action before all plugins are loaded
		add_action( 'plugins_loaded', array( &$this, 'init' ), 1 );

		// override load text domain waiting for the language to be defined
		// here for plugins which load text domain as soon as loaded :(
		if ( ! defined( 'PLL_OLT' ) || PLL_OLT ) {
			PLL_OLT_Manager::instance();
		}

		// extra code for compatibility with some plugins
		// loaded as soon as possible as we may need to act before other plugins are loaded
		if ( ! defined( 'PLL_PLUGINS_COMPAT' ) || PLL_PLUGINS_COMPAT ) {
			PLL_Plugins_Compat::instance();
		}
	}

	/**
	 * autoload classes
	 *
	 * @since 1.2
	 *
	 * @param string $class
	 */
	public function autoload( $class ) {
		// not a Polylang class
		if ( 0 !== strncmp( 'PLL_', $class, 4 ) ) {
			return;
		}

		$class = str_replace( '_', '-', strtolower( substr( $class, 4 ) ) );
		$to_remove = array( 'post-', 'term-', 'settings-', 'admin-', 'frontend-', '-config', '-compat', '-model', 'advanced-' );
		$dir = str_replace( $to_remove, array(), $class );

		$dirs = array(
			PLL_FRONT_INC,
			PLL_MODULES_INC,
			PLL_MODULES_INC . "/$dir",
			PLL_MODULES_INC . '/plugins',
			PLL_INSTALL_INC,
			PLL_ADMIN_INC,
			PLL_SETTINGS_INC,
			PLL_INC,
		);

		foreach ( $dirs as $dir ) {
			if ( file_exists( $file = "$dir/$class.php" ) ) {
				require_once( $file );
				return;
			}
		}
	}

	/**
	 * defines constants
	 * may be overriden by a plugin if set before plugins_loaded, 1
	 *
	 * @since 1.6
	 */
	static public function define_constants() {
		// our url. Don't use WP_PLUGIN_URL http://wordpress.org/support/topic/ssl-doesnt-work-properly
		if ( ! defined( 'POLYLANG_URL' ) ) {
			define( 'POLYLANG_URL', plugins_url( '', POLYLANG_FILE ) );
		}

		// default url to access user data such as custom flags
		if ( ! defined( 'PLL_LOCAL_URL' ) ) {
			define( 'PLL_LOCAL_URL', content_url( '/polylang' ) );
		}

		// cookie name. no cookie will be used if set to false
		if ( ! defined( 'PLL_COOKIE' ) ) {
			define( 'PLL_COOKIE', 'pll_language' );
		}

		// avoid loading polylang admin for frontend ajax requests
		// special test for plupload which does not use jquery ajax and thus does not pass our ajax prefilter
		// special test for customize_save done in frontend but for which we want to load the admin
		if ( ! defined( 'PLL_AJAX_ON_FRONT' ) ) {
			$in = isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], array( 'upload-attachment', 'customize_save' ) );
			define( 'PLL_AJAX_ON_FRONT', defined( 'DOING_AJAX' ) && DOING_AJAX && empty( $_REQUEST['pll_ajax_backend'] ) && ! $in );
		}

		// admin
		if ( ! defined( 'PLL_ADMIN' ) ) {
			define( 'PLL_ADMIN', defined( 'DOING_CRON' ) || ( is_admin() && ! PLL_AJAX_ON_FRONT ) );
		}

		// settings page whatever the tab
		if ( ! defined( 'PLL_SETTINGS' ) ) {
			define( 'PLL_SETTINGS', is_admin() && ( ( isset( $_GET['page'] ) && 'mlang' == $_GET['page'] ) || ! empty( $_REQUEST['pll_ajax_settings'] ) ) );
		}
	}

	/**
	 * Polylang initialization
	 * setups models and separate admin and frontend
	 *
	 * @since 1.2
	 */
	public function init() {
		global $polylang;

		self::define_constants();
		$options = get_option( 'polylang' );

		// plugin upgrade
		if ( $options && version_compare( $options['version'], POLYLANG_VERSION, '<' ) ) {
			$upgrade = new PLL_Upgrade( $options );
			if ( ! $upgrade->upgrade() ) { // if the version is too old
				return;
			}
		}

		/**
		 * Filter the model class to use
		 * /!\ this filter is fired *before* the $polylang object is available
		 *
		 * @since 1.5
		 *
		 * @param string $class either PLL_Model or PLL_Admin_Model
		 */
		$class = apply_filters( 'pll_model', PLL_SETTINGS ? 'PLL_Admin_Model' : 'PLL_Model' );
		$model = new $class( $options );
		$links_model = $model->get_links_model();

		add_filter( 'pll_languages_list', array( 'PLL_Static_Pages', 'pll_languages_list' ), 2, 2 ); // before PLL_Links_Model

		if ( PLL_SETTINGS ) {
			$polylang = new PLL_Settings( $links_model );
		}
		elseif ( PLL_ADMIN ) {
			$polylang = new PLL_Admin( $links_model );
		}
		// do nothing on frontend if no language is defined
		elseif ( $model->get_languages_list() && empty( $_GET['deactivate-polylang'] ) ) {
			$polylang = new PLL_Frontend( $links_model );
		}

		if ( ! $model->get_languages_list() ) {
			/**
			 * Fires when no language has been defined yet
			 * Used to load overriden textdomains
			 *
			 * @since 1.2
			 */
			do_action( 'pll_no_language_defined' );
		}

		if ( ! empty( $polylang ) ) {
			require_once( PLL_INC.'/api.php' ); // loads the API

			if ( ! defined( 'PLL_WPML_COMPAT' ) || PLL_WPML_COMPAT ) {
				PLL_WPML_Compat::instance(); // WPML API
				PLL_WPML_Config::instance(); // wpml-config.xml
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

new Polylang();
