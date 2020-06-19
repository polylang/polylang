<?php
/**
 * @package Polylang
 */

/**
 * Manages compatibility with 3rd party plugins ( and themes ).
 * This class is available as soon as the plugin is loaded.
 *
 * It handles the most simple integrations while more complex inegrations
 * are handled separately in their own classes.
 *
 * @since 1.0
 * @since 2.8 Renamed from PLL_Plugins_Compat to PLL_Integrations.
 */
class PLL_Integrations {
	protected static $instance; // for singleton

	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	protected function __construct() {
		// Loads external integrations.
		foreach ( glob( __DIR__ . '/*/load.php', GLOB_NOSORT ) as $load_script ) { // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable
			require_once $load_script; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		}

		// YARPP
		add_action( 'init', array( $this, 'yarpp_init' ) ); // after Polylang has registered its taxonomy in setup_theme

		// Custom field template
		add_action( 'add_meta_boxes', array( $this, 'cft_copy' ), 10, 2 );

		// Aqua Resizer
		add_filter( 'pll_home_url_black_list', array( $this, 'aq_home_url_black_list' ) );

		// Duplicate post
		add_filter( 'option_duplicate_post_taxonomies_blacklist', array( $this, 'duplicate_post_taxonomies_blacklist' ) );

		// WP Sweep
		add_filter( 'wp_sweep_excluded_taxonomies', array( $this, 'wp_sweep_excluded_taxonomies' ) );

		// No category base (works for Yoast SEO too)
		add_filter( 'get_terms_args', array( $this, 'no_category_base_get_terms_args' ), 5 ); // Before adding cache domain
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
}

class_alias( 'PLL_Integrations', 'PLL_Plugins_Compat' ); // For Backward compatibility.
