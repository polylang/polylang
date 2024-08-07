<?php
/**
 * @package Polylang
 */

/**
 * Base class to manage the static front page and the page for posts.
 *
 * @since 1.8
 */
class PLL_Static_Pages {
	/**
	 * Id of the page on front.
	 *
	 * @var int
	 */
	public $page_on_front = 0;

	/**
	 * Id of the page for posts.
	 *
	 * @var int
	 */
	public $page_for_posts = 0;

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
	 * Constructor: setups filters and actions.
	 *
	 * @since 1.8
	 *
	 * @param object $polylang The Polylang object.
	 */
	public function __construct( &$polylang ) {
		$this->model   = &$polylang->model;
		$this->curlang = &$polylang->curlang;

		$this->init();

		add_filter( 'pll_additional_language_data', array( $this, 'set_static_pages' ), 5, 2 ); // Before PLL_Links_Model.

		// Clean the languages cache when editing page of front, page for posts.
		add_action( 'update_option_show_on_front', array( $this, 'clean_cache' ) );
		add_action( 'update_option_page_on_front', array( $this, 'clean_cache' ) );
		add_action( 'update_option_page_for_posts', array( $this, 'clean_cache' ) );

		// Refresh rewrite rules when the page on front is modified.
		add_action( 'update_option_page_on_front', 'flush_rewrite_rules' );

		// Add option filters when the current language is defined
		add_action( 'pll_language_defined', array( $this, 'pll_language_defined' ) );

		// Modifies the page link in case the front page is not in the default language.
		add_filter( 'page_link', array( $this, 'page_link' ), 20, 2 );

		// OEmbed.
		add_filter( 'oembed_request_post_id', array( $this, 'oembed_request_post_id' ), 10, 2 );
	}

	/**
	 * Stores the page on front and page for posts ids.
	 *
	 * @since 1.8
	 *
	 * @return void
	 */
	public function init() {
		$this->page_on_front  = 0;
		$this->page_for_posts = 0;

		if ( 'page' !== get_option( 'show_on_front' ) ) {
			return;
		}

		$page_on_front = get_option( 'page_on_front' );
		if ( is_numeric( $page_on_front ) ) {
			$this->page_on_front = (int) $page_on_front;
		}

		$page_for_posts = get_option( 'page_for_posts' );
		if ( is_numeric( $page_for_posts ) ) {
			$this->page_for_posts = (int) $page_for_posts;
		}
	}

	/**
	 * Returns the ID of the static page translation.
	 *
	 * @since 3.4
	 *
	 * @param string $static_page Static page option name; `page_on_front` or `page_for_posts`.
	 * @param array  $language    Language data.
	 * @return int
	 */
	protected function get_translation( $static_page, $language ) {
		$translations = $this->model->post->get_raw_translations( $this->$static_page );

		// When the current static page doesn't have any translation, we must return itself for its language.
		if ( empty( $translations ) ) {
			$page_lang = $this->model->post->get_object_term( $this->$static_page, $this->model->post->get_tax_language() );

			if ( ! empty( $page_lang ) && $page_lang->slug === $language['slug'] ) {
				return $this->$static_page;
			}
		}

		if ( ! isset( $translations[ $language['slug'] ] ) ) {
			return 0;
		}

		return $translations[ $language['slug'] ];
	}

	/**
	 * Adds `page_on_front` and `page_for_posts` properties to language data before the object is created.
	 *
	 * @since 3.4
	 *
	 * @param array $additional_data Array of language additional data.
	 * @param array $language        Language data.
	 * @return array Language data with additional `page_on_front` and `page_for_posts` options added.
	 */
	public function set_static_pages( $additional_data, $language ) {
		$additional_data['page_on_front']  = $this->get_translation( 'page_on_front', $language );
		$additional_data['page_for_posts'] = $this->get_translation( 'page_for_posts', $language );

		return $additional_data;
	}

	/**
	 * Cleans the language cache and resets the internal properties when options are updated.
	 *
	 * @since 3.4
	 *
	 * @return void
	 */
	public function clean_cache() {
		$this->model->clean_languages_cache();
		$this->init();
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
		add_filter( 'option_page_on_front', array( $this, 'translate_page_id' ), 10, 2 );
		add_filter( 'option_page_for_posts', array( $this, 'translate_page_id' ), 10, 2 );
	}

	/**
	 * Translates the page on front or page for posts option.
	 *
	 * @since 3.6 Replaces `translate_page_on_front()` and `translate_page_for_posts()` methods.
	 *
	 * @param  int    $page_id ID of the page on front or page for posts.
	 * @param  string $option Option name: `page_on_front` or `page_for_posts`.
	 * @return int
	 */
	public function translate_page_id( $page_id, $option ) {

		if ( empty( $this->curlang->{$option} ) ) {
			return $page_id;
		}

		if ( doing_action( "update_option_{$option}" ) || doing_action( 'switch_blog' ) || doing_action( 'before_delete_post' ) || doing_action( 'wp_trash_post' ) ) {
			/*
			 * Don't attempt to translate in a 'switch_blog' action as there is a risk to call this function while initializing the languages cache.
			 * Don't translate while deleting a post or it will mess up `_reset_front_page_settings_for_post()`.
			 * Don't translate while updating the option itself.
			 */
			return $page_id;
		}

		return $this->curlang->{$option};
	}

	/**
	 * Modifies the page link in case the front page is not in the default language.
	 *
	 * @since 0.7.2
	 *
	 * @param string $link The link to the page.
	 * @param int    $id   The post ID of the page.
	 * @return string Modified link.
	 */
	public function page_link( $link, $id ) {
		$lang = $this->model->post->get_language( $id );

		if ( $lang && $id == $lang->page_on_front ) {
			return $lang->get_home_url();
		}
		return $link;
	}

	/**
	 * Fixes the oembed for the translated static front page
	 * when the language page is redirected to the front page.
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
