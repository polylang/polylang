<?php
/**
 * @package Polylang
 */

/**
 * Base class to manage the static front page and the page for posts
 *
 * @since 1.8
 */
class PLL_Static_Pages {
	/**
	 * Id of the page on front.
	 *
	 * @var int
	 */
	public $page_on_front;

	/**
	 * Id of the page for posts.
	 *
	 * @var int
	 */
	public $page_for_posts;

	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * @var PLL_Model
	 */
	protected $model;

	/**
	 * Current language.
	 *
	 * @var PLL_Language
	 */
	protected $curlang;

	/**
	 * Constructor: setups filters and actions
	 *
	 * @since 1.8
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->model   = &$polylang->model;
		$this->options = &$polylang->options;
		$this->curlang = &$polylang->curlang;

		$this->init();

		// Modifies the page link in case the front page is not in the default language
		add_filter( 'page_link', array( $this, 'page_link' ), 20, 2 );

		// Clean the languages cache when editing page of front, page for posts
		add_action( 'update_option_show_on_front', array( $this->model, 'clean_languages_cache' ) );
		add_action( 'update_option_page_on_front', array( $this->model, 'clean_languages_cache' ) );
		add_action( 'update_option_page_for_posts', array( $this->model, 'clean_languages_cache' ) );

		// Refresh rewrite rules when the page on front is modified
		add_action( 'update_option_page_on_front', 'flush_rewrite_rules' );

		// OEmbed
		add_filter( 'oembed_request_post_id', array( $this, 'oembed_request_post_id' ), 10, 2 );
	}

	/**
	 * Stores the page on front and page for posts ids
	 *
	 * @since 1.8
	 *
	 * @return void
	 */
	public function init() {
		if ( 'page' == get_option( 'show_on_front' ) ) {
			$this->page_on_front = get_option( 'page_on_front' );
			$this->page_for_posts = get_option( 'page_for_posts' );
		} else {
			$this->page_on_front = 0;
			$this->page_for_posts = 0;
		}
	}

	/**
	 * Modifies the page link in case the front page is not in the default language
	 *
	 * @since 0.7.2
	 *
	 * @param string $link link to the page
	 * @param int    $id   post id of the page
	 * @return string modified link
	 */
	public function page_link( $link, $id ) {
		$lang = $this->model->post->get_language( $id );

		if ( $lang && $id == $lang->page_on_front ) {
			return $lang->home_url;
		}
		return $link;
	}

	/**
	 * Adds page_on_front and page_for_posts properties to the language objects.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Language[] $languages The list of languages.
	 * @param PLL_Model      $model     The instance of PLL_Model.
	 * @return PLL_Language[]
	 */
	public static function pll_languages_list( $languages, $model ) {
		if ( 'page' === get_option( 'show_on_front' ) ) {
			foreach ( $languages as $k => $language ) {
				$languages[ $k ]->page_on_front = $model->post->get( get_option( 'page_on_front' ), $language );
				$languages[ $k ]->page_for_posts = $model->post->get( get_option( 'page_for_posts' ), $language );
			}
		}

		return $languages;
	}

	/**
	 * Translates page for posts
	 *
	 * @since 1.8
	 *
	 * @param int $v page for posts page id
	 * @return int
	 */
	public function translate_page_for_posts( $v ) {
		// Don't attempt to translate in a 'switch_blog' action as there is a risk to call this function while initializing the languages cache
		return isset( $this->curlang->page_for_posts ) && ( $this->curlang->page_for_posts ) && ! doing_action( 'switch_blog' ) ? $this->curlang->page_for_posts : $v;
	}

	/**
	 * Fixes the oembed for the translated static front page
	 * when the language page is redirected to the front page
	 *
	 * @since 2.6
	 *
	 * @param int    $post_id The post ID.
	 * @param string $url     The requested URL.
	 * @return int
	 */
	public function oembed_request_post_id( $post_id, $url ) {
		foreach ( $this->model->get_languages_list() as $lang ) {
			if ( trailingslashit( $url ) === trailingslashit( $lang->home_url ) ) {
				$post_id = $lang->page_on_front;
			}
		}
		return $post_id;
	}
}
