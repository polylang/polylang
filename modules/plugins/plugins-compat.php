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
		// WordPress Importer
		add_action( 'init', array( $this, 'maybe_wordpress_importer' ) );
		add_filter( 'wp_import_terms', array( $this, 'wp_import_terms' ) );

		// YARPP
		add_action( 'init', array( $this, 'yarpp_init' ) ); // after Polylang has registered its taxonomy in setup_theme

		// Yoast SEO
		add_action( 'pll_language_defined', array( $this->wpseo = new PLL_WPSEO(), 'init' ) );

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
		$this->jetpack = new PLL_Jetpack();

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

		// Cache plugins
		if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
			add_action( 'pll_init', array( $this->cache_compat = new PLL_Cache_Compat(), 'init' ) );
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
					$description['flag_code'] = $languages[ $description['locale'] ][4];
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
		$options = get_option( 'polylang' );
		if ( $options['force_lang'] > 1 ) {
			$hosts = PLL()->links_model->get_hosts();
			$lang = array_search( $_SERVER['HTTP_HOST'], $hosts );

			if ( empty( $lang ) ) {
				$status = get_site_option( 'dm_301_redirect' ) ? '301' : '302'; // Honor status redirect option
				$redirect = ( is_ssl() ? 'https://' : 'http://' ) . $hosts[ $options['default_lang'] ] . $_SERVER['REQUEST_URI'];
				wp_redirect( $redirect, $status );
				exit;
			}
		}
	}

	/**
	 * Correspondance between WordPress locales and Facebook locales
	 * @see https://translate.wordpress.org/
	 * @see https://www.facebook.com/translations/FacebookLocales.xml
	 *
	 * @since 1.8.1 Update the list of locales
	 * @since 1.6
	 *
	 * @param object $language
	 * @return bool|string Facebook locale, false if no correspondance found
	 */
	static public function get_fb_locale( $language ) {
		static $facebook_locales = array(
			'af'           => 'af_ZA',
			'ak'           => 'ak_GH',
			'am'           => 'am_ET',
			'ar'           => 'ar_AR',
			'arq'          => 'ar_AR',
			'ary'          => 'ar_AR',
			'as'           => 'as_IN',
			'az'           => 'az_AZ',
			'bel'          => 'be_BY',
			'bg_BG'        => 'bg_BG',
			'bn_BD'        => 'bn_IN',
			'bre'          => 'br_FR',
			'bs_BA'        => 'bs_BA',
			'ca'           => 'ca_ES',
			'ceb'          => 'cx_PH',
			'ckb'          => 'cb_IQ',
			'co'           => 'co_FR',
			'cs_CZ'        => 'cs_CZ',
			'cy'           => 'cy_GB',
			'da_DK'        => 'da_DK',
			'de_CH'        => 'de_DE',
			'de_DE'        => 'de_DE',
			'de_DE_formal' => 'de_DE',
			'el'           => 'el_GR',
			'en_AU'        => 'en_US',
			'en_CA'        => 'en_US',
			'en_GB'        => 'en_GB',
			'en_NZ'        => 'en_US',
			'en_US'        => 'en_US',
			'en_ZA'        => 'en_US',
			'eo'           => 'eo_EO',
			'es_AR'        => 'es_LA',
			'es_CL'        => 'es_CL',
			'es_CO'        => 'es_CO',
			'es_MX'        => 'es_MX',
			'es_PE'        => 'es_LA',
			'es_ES'        => 'es_ES',
			'es_VE'        => 'es_VE',
			'et'           => 'et_EE',
			'eu'           => 'eu_ES',
			'fa_IR'        => 'fa_IR',
			'fi'           => 'fi_FI',
			'fo'           => 'fo_FO',
			'fr_CA'        => 'fr_CA',
			'fr_FR'        => 'fr_FR',
			'fuc'          => 'ff_NG',
			'fy'           => 'fy_NL',
			'ga'           => 'ga_IE',
			'gl_ES'        => 'gl_ES',
			'gn'           => 'gn_PY',
			'gu'           => 'gu_IN',
			'he_IL'        => 'he_IL',
			'hi_IN'        => 'hi_IN',
			'hr'           => 'hr_HR',
			'hu_HU'        => 'hu_HU',
			'hy'           => 'hy_AM',
			'id_ID'        => 'id_ID',
			'is_IS'        => 'is_IS',
			'it_IT'        => 'it_IT',
			'ja'           => 'ja_JP',
			'jv_ID'        => 'jv_ID',
			'ka_GE'        => 'ka_GE',
			'kin'          => 'rw_RW',
			'kk'           => 'kk_KZ',
			'km'           => 'km_kH',
			'kn'           => 'kn_IN',
			'ko_KR'        => 'ko_KR',
			'ku'           => 'ku_TR',
			'ky_KY'        => 'ky_KG',
			'la'           => 'la_Va',
			'li'           => 'li_NL',
			'lin'          => 'ln_CD',
			'lo'           => 'lo_LA',
			'lt_LT'        => 'lt_LT',
			'lv'           => 'lv_LV',
			'mg_MG'        => 'mg_MG',
			'mk_MK'        => 'mk_MK',
			'ml_IN'        => 'ml_IN',
			'mn'           => 'mn_MN',
			'mr'           => 'mr_IN',
			'mri'          => 'mi_NZ',
			'ms_MY'        => 'ms_MY',
			'my_MM'        => 'my_MM',
			'ne_NP'        => 'ne_NP',
			'nb_NO'        => 'nb_NO',
			'nl_BE'        => 'nl_BE',
			'nl_NL'        => 'nl_NL',
			'nn_NO'        => 'nn_NO',
			'ory'          => 'or_IN',
			'pa_IN'        => 'pa_IN',
			'pl_PL'        => 'pl_PL',
			'ps'           => 'ps_AF',
			'pt_BR'        => 'pt_BR',
			'pt_PT'        => 'pt_PT',
			'ps'           => 'ps_AF',
			'ro_RO'        => 'ro_RO',
			'roh'          => 'rm_CH',
			'ru_RU'        => 'ru_RU',
			'sa_IN'        => 'sa_IN',
			'si_LK'        => 'si_LK',
			'sk_SK'        => 'sk_SK',
			'sl_SI'        => 'sl_SI',
			'so_SO'        => 'so_SO',
			'sq'           => 'sq_AL',
			'sr_RS'        => 'sr_RS',
			'srd'          => 'sc_IT',
			'sv_SE'        => 'sv_SE',
			'sw'           => 'sw_KE',
			'szl'          => 'sz_PL',
			'ta_LK'        => 'ta_IN',
			'ta_IN'        => 'ta_IN',
			'te'           => 'te_IN',
			'tg'           => 'tg_TJ',
			'th'           => 'th_TH',
			'tl'           => 'tl_PH',
			'tuk'          => 'tk_TM',
			'tr_TR'        => 'tr_TR',
			'tt_RU'        => 'tt_RU',
			'tzm'          => 'tz_MA',
			'uk'           => 'uk_UA',
			'ur'           => 'ur_PK',
			'uz_UZ'        => 'uz_UZ',
			'vi'           => 'vi_VN',
			'yor'          => 'yo_NG',
			'zh_CN'        => 'zh_CN',
			'zh_HK'        => 'zh_HK',
			'zh_TW'        => 'zh_TW',
		);

		return isset( $facebook_locales[ $language->locale ] ) ? $facebook_locales[ $language->locale ] : false;
	}
}
