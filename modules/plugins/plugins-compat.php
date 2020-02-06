<?php

/**
 * Manages compatibility with 3rd party plugins ( and themes )
 * This class is available as soon as the plugin is loaded
 *
 * @since 1.0
 */
class PLL_Plugins_Compat {
	protected static $instance; // for singleton

	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	protected function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 0 );
		add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ) );

		// WordPress Importer
		add_action( 'init', array( $this, 'maybe_wordpress_importer' ) );
		add_filter( 'wp_import_terms', array( $this, 'wp_import_terms' ) );

		// YARPP
		add_action( 'init', array( $this, 'yarpp_init' ) ); // after Polylang has registered its taxonomy in setup_theme

		// Custom field template
		add_action( 'add_meta_boxes', array( $this, 'cft_copy' ), 10, 2 );

		// Aqua Resizer
		add_filter( 'pll_home_url_black_list', array( $this, 'aq_home_url_black_list' ) );

		// Duplicate post
		add_filter( 'option_duplicate_post_taxonomies_blacklist', array( $this, 'duplicate_post_taxonomies_blacklist' ) );

		// Jetpack
		$this->jetpack = new PLL_Jetpack(); // Must be loaded before the plugin is active
		add_action( 'pll_init', array( $this->featured_content = new PLL_Featured_Content(), 'init' ) );

		// WP Sweep
		add_filter( 'wp_sweep_excluded_taxonomies', array( $this, 'wp_sweep_excluded_taxonomies' ) );

		// Twenty Seventeen
		add_action( 'init', array( $this, 'twenty_seventeen_init' ) );

		// No category base (works for Yoast SEO too)
		add_filter( 'get_terms_args', array( $this, 'no_category_base_get_terms_args' ), 5 ); // Before adding cache domain

		// WordPress MU Domain Mapping
		if ( function_exists( 'redirect_to_mapped_domain' ) ) {
			if ( ! defined( 'PLL_CACHE_HOME_URL' ) && ( $options = get_option( 'polylang' ) ) && $options['force_lang'] < 2 ) {
				define( 'PLL_CACHE_HOME_URL', false );
			}

			if ( ! get_site_option( 'dm_no_primary_domain' ) ) {
				remove_action( 'template_redirect', 'redirect_to_mapped_domain' );
				add_action( 'template_redirect', array( $this, 'dm_redirect_to_mapped_domain' ) );
			}
		}
	}

	/**
	 * Access to the single instance of the class
	 *
	 * @since 1.7
	 *
	 * @return object
	 */
	public static function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Look for active plugins and load compatibility layer if needed
	 *
	 * @since 2.3
	 */
	public function plugins_loaded() {
		// Yoast SEO
		if ( defined( 'WPSEO_VERSION' ) ) {
			add_action( 'pll_init', array( $this->wpseo = new PLL_WPSEO(), 'init' ) );
		}

		if ( pll_is_cache_active() ) {
			add_action( 'pll_init', array( $this->cache_compat = new PLL_Cache_Compat(), 'init' ) );
		}

		// Custom Post Type UI
		if ( defined( 'CPTUI_VERSION' ) && class_exists( 'PLL_CPTUI' ) ) {
			add_action( 'pll_init', array( $this->cptui = new PLL_CPTUI(), 'init' ) );
		}

		// The Event Calendar
		if ( defined( 'TRIBE_EVENTS_FILE' ) && class_exists( 'PLL_TEC' ) ) {
			add_action( 'pll_init', array( $this->tec = new PLL_TEC(), 'init' ) );
		}

		// Beaver Builder
		if ( class_exists( 'FLBuilderLoader' ) && class_exists( 'PLL_FLBuilder' ) ) {
			$this->flbuilder = new PLL_FLBuilder();
		}

		// Divi Builder
		if ( ( 'Divi' === get_template() || defined( 'ET_BUILDER_PLUGIN_VERSION' ) ) && class_exists( 'PLL_Divi_Builder' ) ) {
			$this->divi_builder = new PLL_Divi_Builder();
		}

		// WP Offload Media Lite
		if ( function_exists( 'as3cf_init' ) && class_exists( 'PLL_AS3CF' ) ) {
			add_action( 'pll_init', array( $this->as3cf = new PLL_AS3CF(), 'init' ) );
		}

		// Content Blocks (Custom Post Widget)
		if ( function_exists( 'custom_post_widget_plugin_init' ) && class_exists( 'PLL_Content_Blocks' ) ) {
			add_action( 'pll_init', array( $this->content_blocks = new PLL_Content_Blocks(), 'init' ) );
		}
	}

	/**
	 * Look for active plugins and load compatibility layer after the theme has been setup
	 *
	 * @since 2.3.8
	 */
	public function after_setup_theme() {
		// Advanced Custom Fields Pro
		if ( defined( 'ACF_VERSION' ) && version_compare( ACF_VERSION, '5.7.11', '>=' ) && class_exists( 'PLL_ACF' ) ) {
			add_action( 'init', array( $this->acf = new PLL_ACF(), 'init' ) );
		}

		// Admin Columns & Admin Columns Pro
		if ( did_action( 'pll_init' ) && ( defined( 'AC_FILE' ) || defined( 'ACP_FILE' ) ) && class_exists( 'PLL_CPAC' ) ) {
			add_action( 'admin_init', array( $this->cpac = new PLL_CPAC(), 'init' ) );
		}
	}

	/**
	 * WordPress Importer
	 * If WordPress Importer is active, replace the wordpress_importer_init function
	 *
	 * @since 1.2
	 */
	public function maybe_wordpress_importer() {
		if ( defined( 'WP_LOAD_IMPORTERS' ) && class_exists( 'WP_Import' ) ) {
			remove_action( 'admin_init', 'wordpress_importer_init' );
			add_action( 'admin_init', array( $this, 'wordpress_importer_init' ) );
		}
	}

	/**
	 * WordPress Importer
	 * Loads our child class PLL_WP_Import instead of WP_Import
	 *
	 * @since 1.2
	 */
	public function wordpress_importer_init() {
		$class = new ReflectionClass( 'WP_Import' );
		load_plugin_textdomain( 'wordpress-importer', false, basename( dirname( $class->getFileName() ) ) . '/languages' );

		$GLOBALS['wp_import'] = new PLL_WP_Import();
		register_importer( 'wordpress', 'WordPress', __( 'Import <strong>posts, pages, comments, custom fields, categories, and tags</strong> from a WordPress export file.', 'polylang' ), array( $GLOBALS['wp_import'], 'dispatch' ) ); // phpcs:ignore WordPress.WP.CapitalPDangit.Misspelled
	}

	/**
	 * WordPress Importer
	 * Backward Compatibility Polylang < 1.8
	 * Sets the flag when importing a language and the file has been exported with Polylang < 1.8
	 *
	 * @since 1.8
	 *
	 * @param array $terms an array of arrays containing terms information form the WXR file
	 * @return array
	 */
	public function wp_import_terms( $terms ) {
		$languages = include PLL_SETTINGS_INC . '/languages.php';

		foreach ( $terms as $key => $term ) {
			if ( 'language' === $term['term_taxonomy'] ) {
				$description = maybe_unserialize( $term['term_description'] );
				if ( empty( $description['flag_code'] ) && isset( $languages[ $description['locale'] ] ) ) {
					$description['flag_code'] = $languages[ $description['locale'] ]['flag'];
					$terms[ $key ]['term_description'] = maybe_serialize( $description );
				}
			}
		}
		return $terms;
	}

	/**
	 * YARPP
	 * Just makes YARPP aware of the language taxonomy ( after Polylang registered it )
	 *
	 * @since 1.0
	 */
	public function yarpp_init() {
		$GLOBALS['wp_taxonomies']['language']->yarpp_support = 1;
	}

	/**
	 * Aqua Resizer
	 *
	 * @since 1.1.5
	 *
	 * @param array $arr
	 * @return array
	 */
	public function aq_home_url_black_list( $arr ) {
		return array_merge( $arr, array( array( 'function' => 'aq_resize' ) ) );
	}

	/**
	 * Custom field template
	 * Custom field template does check $_REQUEST['post'] to populate the custom fields values
	 *
	 * @since 1.0.2
	 *
	 * @param string $post_type unused
	 * @param object $post      current post object
	 */
	public function cft_copy( $post_type, $post ) {
		global $custom_field_template;
		if ( isset( $custom_field_template, $_REQUEST['from_post'], $_REQUEST['new_lang'] ) && ! empty( $post ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$_REQUEST['post'] = $post->ID;
		}
	}

	/**
	 * Duplicate Post
	 * Avoid duplicating the 'post_translations' taxonomy
	 *
	 * @since 1.8
	 *
	 * @param array|string $taxonomies
	 * @return array
	 */
	public function duplicate_post_taxonomies_blacklist( $taxonomies ) {
		if ( empty( $taxonomies ) ) {
			$taxonomies = array(); // As we get an empty string when there is no taxonomy
		}

		$taxonomies[] = 'post_translations';
		return $taxonomies;
	}

	/**
	 * WP Sweep
	 * Add 'term_language' and 'term_translations' to excluded taxonomies otherwise terms loose their language and translation group
	 *
	 * @since 2.0
	 *
	 * @param array $excluded_taxonomies list of taxonomies excluded from sweeping
	 * @return array
	 */
	public function wp_sweep_excluded_taxonomies( $excluded_taxonomies ) {
		return array_merge( $excluded_taxonomies, array( 'term_language', 'term_translations' ) );
	}

	/**
	 * Twenty Seventeen
	 * Translates the front page panels
	 *
	 * @since 2.0.10
	 */
	public function twenty_seventeen_init() {
		if ( 'twentyseventeen' === get_template() && function_exists( 'twentyseventeen_panel_count' ) && did_action( 'pll_init' ) && PLL() instanceof PLL_Frontend ) {
			$num_sections = twentyseventeen_panel_count();
			for ( $i = 1; $i < ( 1 + $num_sections ); $i++ ) {
				add_filter( 'theme_mod_panel_' . $i, 'pll_get_post' );
			}
		}
	}

	/**
	 * Make sure No category base plugins (including Yoast SEO) get all categories when flushing rules
	 *
	 * @since 2.1
	 *
	 * @param array $args
	 * @return array
	 */
	public function no_category_base_get_terms_args( $args ) {
		if ( doing_filter( 'category_rewrite_rules' ) ) {
			$args['lang'] = '';
		}
		return $args;
	}

	/**
	 * WordPress MU Domain Mapping
	 * Fix primary domain check which forces only one domain per blog
	 * Accept only known domains/subdomains for the current blog
	 *
	 * @since 2.2
	 */
	public function dm_redirect_to_mapped_domain() {
		$options = get_option( 'polylang' );

		// The language is set from the subdomain or domain name
		if ( $options['force_lang'] > 1 ) {
			// Don't redirect the main site
			if ( is_main_site() ) {
				return;
			}

			// Don't redirect post previews
			if ( isset( $_GET['preview'] ) && 'true' === $_GET['preview'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				return;
			}

			// Don't redirect theme customizer
			if ( isset( $_POST['customize'] ) && isset( $_POST['theme'] ) && 'on' === $_POST['customize'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				return;
			}

			// If we can't associate the requested domain to a language, redirect to the default domain
			$requested_url  = pll_get_requested_url();
			$requested_host = wp_parse_url( $requested_url, PHP_URL_HOST );

			$hosts = PLL()->links_model->get_hosts();
			$lang  = array_search( $requested_host, $hosts );

			if ( empty( $lang ) ) {
				$status   = get_site_option( 'dm_301_redirect' ) ? '301' : '302'; // Honor status redirect option
				$redirect = str_replace( '://' . $requested_host, '://' . $hosts[ $options['default_lang'] ], $requested_url );
				wp_safe_redirect( $redirect, $status );
				exit;
			}
		} else {
			// Otherwise rely on MU Domain Mapping
			redirect_to_mapped_domain();
		}
	}
}
