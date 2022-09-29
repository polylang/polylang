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
	 *
	 * @param PLL_Language $lang A language object.
	 * @return string
	 */
	public function home_url( $lang ) {
		return trailingslashit( parent::home_url( $lang ) );
	}

	/**
	 * Returns the static front page url.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Language $lang The language object.
	 * @return string The static front page url.
	 */
	public function front_page_url( $lang ) {
		if ( $this->options['hide_default'] && $lang->slug == $this->options['default_lang'] ) {
			return trailingslashit( $this->home );
		}
		$url = home_url( $this->root . get_page_uri( $lang->page_on_front ) );
		$url = $this->use_trailing_slashes ? trailingslashit( $url ) : untrailingslashit( $url );
		return $this->options['force_lang'] ? $this->add_language_to_link( $url, $lang ) : $url;
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
}
