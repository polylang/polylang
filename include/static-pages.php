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
	 * @var int|null
	 */
	public $page_on_front;

	/**
	 * Id of the page for posts.
	 *
	 * @var int|null
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
	 * @var PLL_Language|null
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

		add_filter( 'pll_static_pages', array( $this, 'set_static_pages' ), 10, 2 );

		add_action( 'pll_language_defined', array( $this, 'pll_language_defined' ) );

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
	 * Init the hooks that filter the "page on front" and "page for posts" options.
	 *
	 * @since 3.3
	 *
	 * @return void
	 */
	public function pll_language_defined() {
		// Translates page for posts and page on front.
		add_filter( 'option_page_on_front', array( $this, 'translate_page_on_front' ) );
		add_filter( 'option_page_for_posts', array( $this, 'translate_page_for_posts' ) );
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
			return $lang->get_home_url();
		}
		return $link;
	}

	/**
	 * Adds page_on_front and page_for_posts properties to language data before the object is created.
	 *
	 * @since 3.4
	 *
	 * @param array $static_pages Array of language page_on_front and page_for_posts properties.
	 * @param array $language Language data.
	 * @return array Language data with static pages ids.
	 *
	 * @phpstan-return array{page_on_front: int<0, max>, page_for_posts: int<0, max>}
	 */
	public function set_static_pages( $static_pages, $language ) {
		if ( 'page' === get_option( 'show_on_front' ) ) {
				$page_on_front_translations     = $this->model->post->get_translations_from_term( get_option( 'page_on_front' ) );
				$page_for_posts_translations    = $this->model->post->get_translations_from_term( get_option( 'page_for_posts' ) );
				$static_pages['page_on_front']  = $page_on_front_translations[ $language['slug'] ];
				$static_pages['page_for_posts'] = $page_for_posts_translations[ $language['slug'] ];
		}

		return $static_pages;
	}

	/**
	 * Translates the page on front option.
	 *
	 * @since 1.8
	 * @since 3.3 Was previously defined in PLL_Frontend_Static_Pages.
	 *
	 * @param  int $page_id ID of the page on front.
	 * @return int
	 */
	public function translate_page_on_front( $page_id ) {
		// Don't attempt to translate in a 'switch_blog' action as there is a risk to call this function while initializing the languages cache.
		return ! empty( $this->curlang->page_on_front ) && ! doing_action( 'switch_blog' ) ? $this->curlang->page_on_front : $page_id;
	}

	/**
	 * Translates the page for posts option.
	 *
	 * @since 1.8
	 *
	 * @param  int $page_id ID of the page for posts.
	 * @return int
	 */
	public function translate_page_for_posts( $page_id ) {
		// Don't attempt to translate in a 'switch_blog' action as there is a risk to call this function while initializing the languages cache.
		return ! empty( $this->curlang->page_for_posts ) && ! doing_action( 'switch_blog' ) ? $this->curlang->page_for_posts : $page_id;
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
			if ( is_string( $lang->get_home_url() ) && trailingslashit( $url ) === trailingslashit( $lang->get_home_url() ) ) {
				return (int) $lang->page_on_front;
			}
		}

		return $post_id;
	}
}
