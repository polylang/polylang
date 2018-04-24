<?php

/**
 * Manages compatibility with 3rd party plugins ( and themes )
 * This class is available as soon as the plugin is loaded
 *
 * @since 1.0
 */
class PLL_Plugins_Compat {
	static protected $instance; // for singleton

	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	protected function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 0 );

		// WordPress Importer
		add_action( 'init', array( $this, 'maybe_wordpress_importer' ) );
		add_filter( 'wp_import_terms', array( $this, 'wp_import_terms' ) );

		// YARPP
		add_action( 'init', array( $this, 'yarpp_init' ) ); // after Polylang has registered its taxonomy in setup_theme

		// Custom field template
		add_action( 'add_meta_boxes', array( $this, 'cft_copy' ), 10, 2 );

		// Aqua Resizer
		add_filter( 'pll_home_url_black_list', array( $this, 'aq_home_url_black_list' ) );

		// Twenty Fourteen
		add_filter( 'transient_featured_content_ids', array( $this, 'twenty_fourteen_featured_content_ids' ) );
		add_filter( 'option_featured-content', array( $this, 'twenty_fourteen_option_featured_content' ) );

		// Duplicate post
		add_filter( 'option_duplicate_post_taxonomies_blacklist', array( $this, 'duplicate_post_taxonomies_blacklist' ) );

		// Jetpack
		$this->jetpack = new PLL_Jetpack(); // Must be loaded before the plugin is active

		// WP Sweep
		add_filter( 'wp_sweep_excluded_taxonomies', array( $this, 'wp_sweep_excluded_taxonomies' ) );

		// Twenty Seventeen
		add_action( 'init', array( $this, 'twenty_seventeen_init' ) );

		// No category base (works for Yoast SEO too)
		add_filter( 'get_terms_args', array( $this, 'no_category_base_get_terms_args' ), 5 ); // Before adding cache domain

		// WordPress MU Domain Mapping
		if ( function_exists( 'redirect_to_mapped_domain' ) && ! get_site_option( 'dm_no_primary_domain' ) ) {
			remove_action( 'template_redirect', 'redirect_to_mapped_domain' );
			add_action( 'template_redirect', array( $this, 'dm_redirect_to_mapped_domain' ) );
		}
	}

	/**
	 * Access to the single instance of the class
	 *
	 * @since 1.7
	 *
	 * @return object
	 */
	static public function instance() {
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
			add_action( 'pll_language_defined', array( $this->wpseo = new PLL_WPSEO(), 'init' ) );
		}

		// Cache plugins, with specific test for WP Fastest Cache which doesn't use WP_CACHE
		if ( ( defined( 'WP_CACHE' ) && WP_CACHE ) || defined( 'WPFC_MAIN_PATH' ) ) {
			add_action( 'pll_init', array( $this->cache_compat = new PLL_Cache_Compat(), 'init' ) );
		}

		// Advanced Custom Fields Pro
		// The function acf_get_value() is not defined in ACF 4
		if ( class_exists( 'acf' ) && function_exists( 'acf_get_value' ) && class_exists( 'PLL_ACF' ) ) {
			add_action( 'init', array( $this->acf = new PLL_ACF(), 'init' ) );
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
	}

	/**
	 * WordPress Importer
	 * If WordPress Importer is active, replace the wordpress_importer_init function
	 *
	 * @since 1.2
	 */
	function maybe_wordpress_importer() {
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
	function wordpress_importer_init() {
		$class = new ReflectionClass( 'WP_Import' );
		load_plugin_textdomain( 'wordpress-importer', false, basename( dirname( $class->getFileName() ) ) . '/languages' );

		$GLOBALS['wp_import'] = new PLL_WP_Import();
		register_importer( 'wordpress', 'WordPress', __( 'Import <strong>posts, pages, comments, custom fields, categories, and tags</strong> from a WordPress export file.', 'wordpress-importer' ), array( $GLOBALS['wp_import'], 'dispatch' ) ); // WPCS: spelling ok.
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
	function wp_import_terms( $terms ) {
		include PLL_SETTINGS_INC . '/languages.php';

		foreach ( $terms as $key => $term ) {
			if ( 'language' === $term['term_taxonomy'] ) {
				$description = maybe_unserialize( $term['term_description'] );
				if ( empty( $description['flag_code'] ) && isset( $languages[ $description['locale'] ] ) ) {
					$description['flag_code'] = $languages[ $description['locale'] ]['flag'];
					$terms[ $key ]['term_description'] = serialize( $description );
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
		if ( isset( $custom_field_template, $_REQUEST['from_post'], $_REQUEST['new_lang'] ) && ! empty( $post ) ) {
			$_REQUEST['post'] = $post->ID;
		}
	}

	/**
	 * Twenty Fourteen
	 * Rewrites the function Featured_Content::get_featured_post_ids()
	 *
	 * @since 1.4
	 *
	 * @param array $featured_ids featured posts ids
	 * @return array modified featured posts ids ( include all languages )
	 */
	public function twenty_fourteen_featured_content_ids( $featured_ids ) {
		if ( 'twentyfourteen' != get_template() || ! did_action( 'pll_init' ) || false !== $featured_ids ) {
			return $featured_ids;
		}

		$settings = Featured_Content::get_setting();

		if ( ! $term = wpcom_vip_get_term_by( 'name', $settings['tag-name'], 'post_tag' ) ) {
			return $featured_ids;
		}

		// Get featured tag translations
		$tags = PLL()->model->term->get_translations( $term->term_id );
		$ids = array();

		// Query for featured posts in all languages
		// One query per language to get the correct number of posts per language
		foreach ( $tags as $tag ) {
			$_ids = get_posts( array(
				'lang'        => 0, // avoid language filters
				'fields'      => 'ids',
				'numberposts' => Featured_Content::$max_posts,
				'tax_query'   => array(
					array(
						'taxonomy' => 'post_tag',
						'terms'    => (int) $tag,
					),
				),
			) );

			$ids = array_merge( $ids, $_ids );
		}

		$ids = array_map( 'absint', $ids );
		set_transient( 'featured_content_ids', $ids );

		return $ids;
	}

	/**
	 * Twenty Fourteen
	 * Translates the featured tag id in featured content settings
	 * Mainly to allow hiding it when requested in featured content options
	 * Acts only on frontend
	 *
	 * @since 1.4
	 *
	 * @param array $settings featured content settings
	 * @return array modified $settings
	 */
	public function twenty_fourteen_option_featured_content( $settings ) {
		if ( 'twentyfourteen' == get_template() && PLL() instanceof PLL_Frontend && $settings['tag-id'] && $tr = pll_get_term( $settings['tag-id'] ) ) {
			$settings['tag-id'] = $tr;
		}

		return $settings;
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
	function duplicate_post_taxonomies_blacklist( $taxonomies ) {
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
			if ( isset( $_GET['preview'] ) && 'true' === $_GET['preview'] ) {
				return;
			}

			// Don't redirect theme customizer
			if ( isset( $_POST['customize'] ) && isset( $_POST['theme'] ) && 'on' === $_POST['customize'] ) {
				return;
			}

			// If we can't associate the requested domain to a language, redirect to the default domain
			$hosts = PLL()->links_model->get_hosts();
			$lang = array_search( $_SERVER['HTTP_HOST'], $hosts );

			if ( empty( $lang ) ) {
				$status = get_site_option( 'dm_301_redirect' ) ? '301' : '302'; // Honor status redirect option
				$redirect = ( is_ssl() ? 'https://' : 'http://' ) . $hosts[ $options['default_lang'] ] . $_SERVER['REQUEST_URI'];
				wp_redirect( $redirect, $status );
				exit;
			}
		}

		// Otherwise rely on MU Domain Mapping
		redirect_to_mapped_domain();
	}
}
