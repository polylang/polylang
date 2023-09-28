<?php
/**
 * @package Polylang
 */

/**
 * Links model base class when using pretty permalinks.
 *
 * @since 1.6
 */
abstract class PLL_Links_Permalinks extends PLL_Links_Model {
	/**
	 * Tells this child class of PLL_Links_Model is for pretty permalinks.
	 *
	 * @var bool
	 */
	public $using_permalinks = true;

	/**
	 * The name of the index file which is the entry point to all requests.
	 * We need this before the global $wp_rewrite is created.
	 * Also hardcoded in WP_Rewrite.
	 *
	 * @var string
	 */
	protected $index = 'index.php';

	/**
	 * The prefix for all permalink structures.
	 *
	 * @var string
	 */
	protected $root;

	/**
	 * Whether to add trailing slashes.
	 *
	 * @var bool
	 */
	protected $use_trailing_slashes;

	/**
	 * The name of the rewrite rules to always modify.
	 *
	 * @var string[]
	 */
	protected $always_rewrite = array( 'date', 'root', 'comments', 'search', 'author' );

	/**
	 * Constructor.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Model $model PLL_Model instance.
	 */
	public function __construct( &$model ) {
		parent::__construct( $model );

		// Inspired by WP_Rewrite.
		$permalink_structure = get_option( 'permalink_structure' );
		$this->root = preg_match( '#^/*' . $this->index . '#', $permalink_structure ) ? $this->index . '/' : '';
		$this->use_trailing_slashes = ( '/' == substr( $permalink_structure, -1, 1 ) );
	}

	/**
	 * Initializes permalinks.
	 *
	 * @since 3.5
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

		if ( did_action( 'wp_loaded' ) ) {
			$this->do_prepare_rewrite_rules();
		} else {
			add_action( 'wp_loaded', array( $this, 'do_prepare_rewrite_rules' ), 9 ); // Just before WordPress callback `WP_Rewrite::flush_rules()`.
		}
	}

	/**
	 * Fires our own action telling Polylang plugins
	 * and third parties are able to prepare rewrite rules.
	 *
	 * @since 3.5
	 *
	 * @return void
	 */
	public function do_prepare_rewrite_rules() {
		/**
		 * Tells when Polylang is able to prepare rewrite rules filters.
		 * Action fired right after `wp_loaded` and just before WordPress `WP_Rewrite::flush_rules()` callback.
		 *
		 * @since 3.5
		 *
		 * @param PLL_Links_Permalinks $links Current links object.
		 */
		do_action( 'pll_prepare_rewrite_rules', $this );
	}

	/**
	 * Returns the link to the first page when using pretty permalinks.
	 *
	 * @since 1.2
	 *
	 * @param string $url The url to modify.
	 * @return string The modified url.
	 */
	public function remove_paged_from_link( $url ) {
		/**
		 * Filters an url after the paged part has been removed.
		 *
		 * @since 2.0.6
		 *
		 * @param string $modified_url The link to the first page.
		 * @param string $original_url The link to the original paged page.
		 */
		return apply_filters( 'pll_remove_paged_from_link', preg_replace( '#/page/[0-9]+/?#', $this->use_trailing_slashes ? '/' : '', $url ), $url );
	}

	/**
	 * Returns the link to the paged page when using pretty permalinks.
	 *
	 * @since 1.5
	 *
	 * @param string $url  The url to modify.
	 * @param int    $page The page number.
	 * @return string The modified url.
	 */
	public function add_paged_to_link( $url, $page ) {
		/**
		 * Filters an url after the paged part has been added.
		 *
		 * @since 2.0.6
		 *
		 * @param string $modified_url The link to the paged page.
		 * @param string $original_url The link to the original first page.
		 * @param int    $page         The page number.
		 */
		return apply_filters( 'pll_add_paged_to_link', user_trailingslashit( trailingslashit( $url ) . 'page/' . $page, 'paged' ), $url, $page );
	}

	/**
	 * Returns the home url in a given language.
	 *
	 * @since 1.3.1
	 * @since 3.4 Accepts now a language slug.
	 *
	 * @param PLL_Language|string $language Language object or slug.
	 * @return string
	 */
	public function home_url( $language ) {
		if ( $language instanceof PLL_Language ) {
			$language = $language->slug;
		}

		return trailingslashit( parent::home_url( $language ) );
	}

	/**
	 * Returns the static front page url.
	 *
	 * @since 1.8
	 * @since 3.4 Accepts now an array of language properties.
	 *
	 * @param PLL_Language|array $language Language object or array of language properties.
	 * @return string The static front page url.
	 */
	public function front_page_url( $language ) {
		if ( $language instanceof PLL_Language ) {
			$language = $language->to_array();
		}

		if ( $this->options['hide_default'] && $language['is_default'] ) {
			return trailingslashit( $this->home );
		}
		$url = home_url( $this->root . get_page_uri( $language['page_on_front'] ) );
		$url = $this->use_trailing_slashes ? trailingslashit( $url ) : untrailingslashit( $url );
		return $this->options['force_lang'] ? $this->add_language_to_link( $url, $language['slug'] ) : $url;
	}

	/**
	 * Prepares rewrite rules filters.
	 *
	 * @since 1.6
	 *
	 * @return string[]
	 */
	public function get_rewrite_rules_filters() {
		// Make sure that we have the right post types and taxonomies.
		$types = array_values( array_merge( $this->model->get_translated_post_types(), $this->model->get_translated_taxonomies(), $this->model->get_filtered_taxonomies() ) );
		$types = array_merge( $this->always_rewrite, $types );

		/**
		 * Filters the list of rewrite rules filters to be used by Polylang.
		 *
		 * @since 0.8.1
		 *
		 * @param array $types The list of filters (without '_rewrite_rules' at the end).
		 */
		return apply_filters( 'pll_rewrite_rules', $types );
	}

	/**
	 * Removes hooks to filter rewrite rules, called when switching blog @see {PLL_Base::switch_blog()}.
	 *
	 * @since 3.5
	 *
	 * @return void
	 */
	public function remove_filters() {
		parent::remove_filters();

		remove_all_actions( 'pll_prepare_rewrite_rules' );
	}
}
